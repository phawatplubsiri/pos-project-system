<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>BoardGame POS</title>
        
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

        @if(app()->environment('production'))
            @php
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            @endphp
            <link rel="stylesheet" href="/build/{{ $manifest['resources/css/app.css']['file'] }}">
            @foreach($manifest['resources/js/app.js']['css'] ?? [] as $css)
                <link rel="stylesheet" href="/build/{{ $css }}">
            @endforeach
            <script type="module" src="/build/{{ $manifest['resources/js/app.js']['file'] }}"></script>
        @else
            <script type="module" src="http://localhost:5173/@vite/client"></script>
            <link rel="stylesheet" href="http://localhost:5173/resources/css/app.css">
            <script type="module" src="http://localhost:5173/resources/js/app.js"></script>
        @endif
    </head>
    <body>
        <div id="app"></div>

        <!-- jQuery and DataTables JS -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    </body>
</html>