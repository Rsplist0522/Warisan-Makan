<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Session Expired') }} - Warisan Makan</title>
    @fonts
    <style>
        :root { color-scheme: light; --background: #f7f1ea; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: var(--background); }
    </style>
</head>
<body>
    @include('partials.session-expired-modal', ['loginUrl' => $loginUrl, 'visible' => true])
</body>
</html>
