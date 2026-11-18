<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $newsletter->title }}</title></head>
<body style="font:16px/1.6 Arial,sans-serif;color:#193047;background:#f5f7fa;padding:24px">
    <main style="background:#fff;padding:30px;max-width:660px;margin:auto;border-radius:10px">
        <h1 style="color:#133452;margin-top:0">BasinEnergy</h1>
        <p style="color:#64748b">{{ $newsletter->issue_month->format('F Y') }} Monthly Newsletter</p>
        <h2>{{ $newsletter->title }}</h2>
        <div>{!! nl2br(e($newsletter->body)) !!}</div>
        <hr style="border:0;border-top:1px solid #d9e0e9;margin-top:28px">
        <p style="font-size:13px;color:#66768a">You received this because you subscribed on BasinEnergy.
        <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>.</p>
    </main>
</body></html>
