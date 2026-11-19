<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWeatherTest extends TestCase
{
    private const WEATHER_URL = 'https://api.weather.gov/stations/KVEL/observations/latest';

    public function setUp(): void
    {
        parent::setUp();

        Cache::forget('admin.weather.kvel.latest.v1');
        Cache::forget('admin.weather.kvel.last_good.v1');
    }

    private function stationObservation(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'timestamp' => now()->subMinutes(20)->toIso8601String(),
                'temperature' => ['value' => 5, 'unitCode' => 'wmoUnit:degC'],
                'windSpeed' => ['value' => 16, 'unitCode' => 'wmoUnit:km_h-1'],
                'windDirection' => ['value' => 270, 'unitCode' => 'wmoUnit:degree_(angle)'],
                'relativeHumidity' => ['value' => 67, 'unitCode' => 'wmoUnit:percent'],
                'textDescription' => 'Mostly Cloudy',
            ],
        ];
    }

    public function test_admin_weather_uses_nws_station_measurements_and_caches_success(): void
    {
        Http::fake([
            self::WEATHER_URL => Http::response($this->stationObservation(), 200),
        ]);
        $this->loginAsAdmin();

        $this->getJson(route('admin.weather.current'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('station', 'Vernal Regional Airport (KVEL)')
            ->assertJsonPath('temperature_f', 41)
            ->assertJsonPath('wind_mph', 9.9)
            ->assertJsonPath('wind_direction', 'W')
            ->assertJsonPath('humidity_percent', 67)
            ->assertJsonPath('conditions', 'Mostly Cloudy')
            ->assertJsonPath('stale', false)
            ->assertHeader('Cache-Control', 'private, no-store');

        $this->getJson(route('admin.weather.current'))->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === self::WEATHER_URL
            && $request->hasHeader('User-Agent')
            && $request->hasHeader('Accept', 'application/geo+json'));
    }

    public function test_nws_outage_returns_an_unavailable_state_instead_of_fake_weather(): void
    {
        Http::fake([
            self::WEATHER_URL => Http::response(['detail' => 'Service unavailable'], 503),
        ]);
        $this->loginAsAdmin();

        $this->getJson(route('admin.weather.current'))
            ->assertStatus(503)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('temperature_f', null)
            ->assertJsonPath('wind_mph', null)
            ->assertJsonPath('conditions', null)
            ->assertJsonPath('message', 'Weather observations are temporarily unavailable.');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('adminWeatherTemperature')
            ->assertSee('adminOzoneChart');
    }

    public function test_recent_known_good_weather_is_marked_stale_when_nws_fails(): void
    {
        Http::fakeSequence()
            ->push($this->stationObservation(), 200)
            ->push(['detail' => 'Service unavailable'], 503);

        $this->loginAsAdmin();

        $this->getJson(route('admin.weather.current'))
            ->assertOk()->assertJsonPath('stale', false);

        Cache::forget('admin.weather.kvel.latest.v1');

        $this->getJson(route('admin.weather.current'))
            ->assertOk()
            ->assertJsonPath('temperature_f', 41)
            ->assertJsonPath('stale', true)
            ->assertJsonPath('message', 'Weather service is unavailable; showing the last successful observation.');

        Http::assertSentCount(2);
    }

    public function test_missing_nws_fields_are_not_misrepresented_as_zero(): void
    {
        $report = $this->stationObservation();
        $report['properties']['windSpeed']['value'] = null;
        $report['properties']['windDirection']['value'] = null;
        $report['properties']['relativeHumidity']['value'] = null;
        $report['properties']['textDescription'] = '';

        Http::fake([self::WEATHER_URL => Http::response($report, 200)]);
        $this->loginAsAdmin();

        $this->getJson(route('admin.weather.current'))
            ->assertOk()
            ->assertJsonPath('temperature_f', 41)
            ->assertJsonPath('wind_mph', null)
            ->assertJsonPath('wind_direction', null)
            ->assertJsonPath('humidity_percent', null)
            ->assertJsonPath('conditions', null);
    }

    public function test_guests_and_regular_users_cannot_view_admin_weather_endpoint(): void
    {
        Http::preventStrayRequests();

        $this->get(route('admin.weather.current'))->assertRedirect('/login');

        $this->actingAs(User::factory()->user()->create());
        $this->getJson(route('admin.weather.current'))
            ->assertRedirect(route('frontend.index'));

        Http::assertNothingSent();
    }
}
