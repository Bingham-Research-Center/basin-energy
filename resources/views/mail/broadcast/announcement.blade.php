<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $announcement->title }}</title></head>
<body style="font:16px/1.6 Arial,sans-serif;color:#193047;background:#f5f7fa;padding:24px">
    <main style="background:#fff;padding:30px;max-width:660px;margin:auto;border-radius:10px">
        <h1 style="color:#133452;margin-top:0">BasinEnergy Announcement</h1>
        <h2>{{ $announcement->title }}</h2>
        <div>{!! app(\App\Services\BroadcastRichText::class)->render($announcement->body, $announcement->body_format) !!}</div>
        <hr style="border:0;border-top:1px solid #d9e0e9;margin-top:28px">
        <p style="font-size:13px;color:#66768a">This announcement was sent to your BasinEnergy account group.</p>
        <p><a href="{{ route('frontend.broadcast.announcements') }}">View announcements in your dashboard</a></p>
    </main>
</body></html>
