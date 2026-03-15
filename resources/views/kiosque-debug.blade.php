{{-- Debug view --}}
<!DOCTYPE html>
<html>
<head>
    <title>Debug Kiosque</title>
</head>
<body>
    <h1>Debug Kiosque</h1>
    <p>Vinyles count: {{ count($vinylesData) }}</p>
    <pre>{{ json_encode($vinylesData, JSON_PRETTY_PRINT) }}</pre>
</body>
</html>
