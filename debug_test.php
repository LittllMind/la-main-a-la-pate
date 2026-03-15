<?php
// debug_test.php - Script pour déboguer le test

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::connection()->statement('DROP DATABASE IF EXISTS vinyles_test');
DB::connection()->statement('CREATE DATABASE IF NOT EXISTS vinyles_test');

// Recréer les tables
Artisan::call('migrate:fresh');

// Créer un admin
$admin = User::factory()->admin()->create(['id' => 1]);
echo "Admin created: " . $admin->id . "\n";

// Tester la route
try {
    $response = Route::dispatch(
        Request::create('/admin/reports/artists', 'GET', [], [], [], ['HTTP_REFERER' => ''])
    );
    echo "Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    if (str_contains($content, 'Error') || str_contains($content, 'Exception')) {
        echo "Content:\n" . substr($content, 0, 2000) . "\n";
    }
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "In: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
