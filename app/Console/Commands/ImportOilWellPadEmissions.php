<?php

namespace App\Console\Commands;

use App\Models\OilWellPadEmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportOilWellPadEmissions extends Command
{
    protected $signature = 'data:import-oil-well-pad-emissions
                            {file : Path to HiFloUB_Summr2019_AllData_anon.xlsx}
                            {--fresh : Delete existing oil-well-pad records before importing}';

    protected $description = 'Import the anonymized Duchesne County oil-well-pad high-flow emissions dataset';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            OilWellPadEmission::query()->delete();
        }

        $sheet = IOFactory::load($path)->getSheet(0);
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $highestRow = $sheet->getHighestDataRow();

        $rowLabels = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $label = trim((string) $sheet->getCell([1, $row])->getFormattedValue());
            if ($label !== '') {
                $rowLabels[$row] = $label;
            }
        }

        $coreMap = [
            'methane' => 'methane_g_hr',
            'carbon dioxide' => 'carbon_dioxide_g_hr',
            'total non-methane hydrocarbons (no alcohols)' => 'tnmhc_g_hr',
            'total alkanes' => 'alkanes_g_hr',
            'total alkenes + alkyne' => 'alkenes_alkyne_g_hr',
            'total aromatics' => 'aromatics_g_hr',
            'total alcohols' => 'alcohols_g_hr',
            'total carbonyls' => 'carbonyls_g_hr',
        ];

        $records = [];

        for ($column = 2; $column <= $highestColumnIndex; $column++) {
            $sampleType = trim((string) $sheet->getCell([$column, 2])->getFormattedValue());

            if ($sampleType === '') {
                continue;
            }

            $record = [
                'sample_number' => $column - 1,
                'sample_type' => $sampleType,
                'source_file' => basename($path),
                'compound_emissions' => [],
            ];

            foreach ($rowLabels as $row => $label) {
                if ($row <= 2) {
                    continue;
                }

                $normalizedLabel = Str::lower(trim($label));
                $formatted = trim((string) $sheet->getCell([$column, $row])->getFormattedValue());

                if ($normalizedLabel === 'notes') {
                    $record['notes'] = $formatted !== '' ? $formatted : null;
                    continue;
                }

                $value = $this->numericOrNull($sheet->getCell([$column, $row])->getCalculatedValue(), $formatted);

                if (isset($coreMap[$normalizedLabel])) {
                    $record[$coreMap[$normalizedLabel]] = $value;
                    continue;
                }

                $record['compound_emissions'][$label] = $value;
            }

            $record['total_organic_compounds_g_hr'] = $this->sumNullable([
                $record['methane_g_hr'] ?? null,
                $record['tnmhc_g_hr'] ?? null,
                $record['alcohols_g_hr'] ?? null,
                $record['carbonyls_g_hr'] ?? null,
            ]);

            $record['compound_emissions'] = json_encode(
                $record['compound_emissions'],
                JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
            );

            $record['created_at'] = now();
            $record['updated_at'] = now();
            $records[] = $record;
        }

        DB::transaction(function () use ($records) {
            foreach (array_chunk($records, 200) as $chunk) {
                OilWellPadEmission::query()->upsert(
                    $chunk,
                    ['sample_number'],
                    [
                        'sample_type',
                        'methane_g_hr',
                        'carbon_dioxide_g_hr',
                        'tnmhc_g_hr',
                        'alkanes_g_hr',
                        'alkenes_alkyne_g_hr',
                        'aromatics_g_hr',
                        'alcohols_g_hr',
                        'carbonyls_g_hr',
                        'total_organic_compounds_g_hr',
                        'notes',
                        'compound_emissions',
                        'source_file',
                        'updated_at',
                    ]
                );
            }
        });

        $this->info(count($records).' oil-well-pad emission measurements imported successfully.');

        return self::SUCCESS;
    }

    private function numericOrNull($rawValue, string $formattedValue): ?float
    {
        $text = trim($formattedValue);

        if ($text === '' || in_array(Str::upper($text), ['N.D.', 'ND', 'N/A', 'NA', '--'], true)) {
            return null;
        }

        if (is_numeric($rawValue)) {
            return (float) $rawValue;
        }

        if (is_numeric(str_replace(',', '', $text))) {
            return (float) str_replace(',', '', $text);
        }

        return null;
    }

    private function sumNullable(array $values): ?float
    {
        $numeric = array_values(array_filter($values, fn ($value) => $value !== null));

        return count($numeric) ? array_sum($numeric) : null;
    }
}
