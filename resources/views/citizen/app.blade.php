<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Citizen</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/citizen/main.jsx'])
</head>
<body>
    <div id="citizen-app"></div>
</body>
</html>
