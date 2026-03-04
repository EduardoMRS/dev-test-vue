<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fa;margin:0;padding:24px}
        .email{max-width:680px;margin:0 auto;background:#ffffff;padding:20px;border:1px solid #e6eaf0;border-radius:6px}
        .content{color:#333;font-size:15px;line-height:1.6}
        .footer{font-size:13px;color:#8a8f98;margin-top:20px;text-align:center}
    </style>
</head>
<body>
<div class="email">
    @isset($title)
        <h1 style="font-size:20px;margin:0 0 12px;color:#111">{{ $title }}</h1>
    @endisset

    <div class="content">
        {!! $body ?? '' !!}
    </div>

    <div class="footer">
        <p>Atenciosamente,<br>{{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
