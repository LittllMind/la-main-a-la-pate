<?php

use App\Http\Controllers\PostPublicController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| ROUTES PUBLIQUES (sans inscription)
|---------------------------------------------------------------------------
*/
Route::get('/', [ContactController::class, 'seraphotheque'])->name('home');
// Route::get('/', [PostPublicController::class, 'index'])->name('home');
Route::get('/actu/{slug}', [PostPublicController::class, 'show'])->name('posts.show');

Route::get('/seraphotheque', [ContactController::class, 'seraphotheque'])->name('seraphotheque');
Route::get('/a-propos', [ContactController::class, 'about'])->name('about');
Route::get('/contact', [ContactController::class, 'contactForm'])->name('contact');
Route::post('/contact', [ContactController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/mentions-legales', [ContactController::class, 'legal'])->name('legal');
Route::get('/confidentialite', [ContactController::class, 'privacy'])->name('privacy');

/*
|---------------------------------------------------------------------------
| ROUTES AUTHENTIFICATION (Breeze)
|---------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|---------------------------------------------------------------------------
| ROUTES COMMUNAUTE (membres authentifies)
|---------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('communaute')->name('community.')->group(function () {
    Route::get('/', [CommunityController::class, 'index'])->name('index');
    Route::get('/{slug}', [CommunityController::class, 'show'])->name('show');
    Route::post('/{slug}/topics', [CommunityController::class, 'storeTopic'])->name('topic.store');
    Route::get('/t/{spaceSlug}/{topicSlug}', [CommunityController::class, 'showTopic'])->name('topic.show');
    Route::post('/t/{spaceSlug}/{topicSlug}/reply', [CommunityController::class, 'storeReply'])->name('reply.store');
});

/*
|---------------------------------------------------------------------------
| ROUTES ADMIN (admin uniquement)
|---------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/posts', [PostPublicController::class, 'adminIndex'])->name('posts.index');
    Route::get('/posts/create', [PostPublicController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostPublicController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostPublicController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostPublicController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostPublicController::class, 'destroy'])->name('posts.destroy');
    Route::patch('/posts/{post}/publish', [PostPublicController::class, 'publish'])->name('posts.publish');
});

require __DIR__.'/auth.php';
