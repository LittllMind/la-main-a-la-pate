<?php

use App\Http\Controllers\Auth\FirstTimeSetupController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingSectionController;
use App\Http\Controllers\PostPublicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteMapController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectCollaboratorController;
use App\Http\Controllers\SubjectDocumentController;
use App\Http\Controllers\SubjectImageController;
use App\Http\Controllers\SubjectImportController;
use App\Http\Controllers\SubjectPdfController;
use App\Http\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| ROUTES PUBLIQUES (sans inscription)
|---------------------------------------------------------------------------
*/
Route::get('/', [ContactController::class, 'seraphotheque'])->name('home');
Route::get('/seraphotheque', [ContactController::class, 'seraphotheque'])->name('seraphotheque');

Route::middleware(['auth'])->group(function () {
    Route::get('/a-propos', [ContactController::class, 'about'])->name('about');
    Route::get('/contact', [ContactController::class, 'contactForm'])->name('contact');
    Route::post('/contact', [ContactController::class, 'contactSubmit'])->name('contact.submit');
    Route::get('/mentions-legales', [ContactController::class, 'legal'])->name('legal');
    Route::get('/confidentialite', [ContactController::class, 'privacy'])->name('privacy');
    Route::get('/plan-du-site', [SiteMapController::class, 'index'])->name('site.map');
});

/*
|---------------------------------------------------------------------------
| HALL / ACTUALITÉS (membres authentifiés)
|---------------------------------------------------------------------------
*/
Route::get('/hall', [PostPublicController::class, 'index'])->middleware('auth')->name('hall');
// TEMPORAIREMENT OUVERT POUR REVISION VISUELLE DU POST
Route::get('/actu/{slug}', [PostPublicController::class, 'show'])->name('posts.show');

/*
|---------------------------------------------------------------------------
| ESPACE SUJETS (membres authentifies)
|---------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('sujets')->name('subjects.tree.')->group(function () {
    Route::get('/arbre', [\App\Http\Controllers\DocumentTreeController::class, 'subjectsIndex'])->name('index');
    Route::get('/arbre-data', [\App\Http\Controllers\DocumentTreeController::class, 'subjectsData'])->name('data');
});

Route::middleware(['auth'])->prefix('sujets')->name('subjects.')->group(function () {
    Route::get('/', [SubjectController::class, 'index'])->name('index');
    Route::get('/creer', [SubjectController::class, 'create'])->name('create');
    Route::post('/', [SubjectController::class, 'store'])->name('store');
    Route::get('/importer', [SubjectImportController::class, 'create'])->name('import.create');
    Route::post('/importer', [SubjectImportController::class, 'store'])->name('import.store');
    Route::post('/{subject:slug}/commentaires', [SubjectController::class, 'storeComment'])->name('comments.store');
    Route::get('/{subject:slug}/modifier', [SubjectController::class, 'edit'])->name('edit');
    Route::put('/{subject:slug}', [SubjectController::class, 'update'])->name('update');
    Route::delete('/{subject:slug}', [SubjectController::class, 'destroy'])->name('destroy');
    Route::patch('/{subject:slug}/publier', [SubjectController::class, 'publish'])->name('publish');

    // Collaboration et votes de publication
    Route::post('/{subject:slug}/collaborateurs', [SubjectCollaboratorController::class, 'store'])->name('collaborators.store');
    Route::delete('/{subject:slug}/collaborateurs/{user}', [SubjectCollaboratorController::class, 'destroy'])->name('collaborators.destroy');
    Route::post('/{subject:slug}/vote-publication', [SubjectCollaboratorController::class, 'startVote'])->name('collaborators.startVote');
    Route::post('/{subject:slug}/voter', [SubjectCollaboratorController::class, 'vote'])->name('collaborators.vote');

    Route::get('/{subject:slug}/images', [SubjectImageController::class, 'index'])->name('images.index');
    Route::post('/{subject:slug}/images', [SubjectImageController::class, 'store'])->name('images.store');
    Route::post('/{subject:slug}/upload-image', [SubjectImageController::class, 'uploadInline'])->name('images.upload');
    Route::patch('/{subject:slug}/images/{image}', [SubjectImageController::class, 'update'])->name('images.update');
    Route::delete('/{subject:slug}/images/{image}', [SubjectImageController::class, 'destroy'])->name('images.destroy');

    // Documents attachés au sujet (PDF, OCR, sources, audio...)
    Route::get('/{subject:slug}/documents', [SubjectDocumentController::class, 'index'])->name('documents.index');
    Route::post('/{subject:slug}/documents', [SubjectDocumentController::class, 'store'])->name('documents.store');
    Route::post('/{subject:slug}/documents/markdown-pdf', [SubjectDocumentController::class, 'storeMarkdownPdf'])->name('documents.markdown-pdf');
    Route::patch('/{subject:slug}/documents/{document}', [SubjectDocumentController::class, 'update'])->name('documents.update');
    Route::delete('/{subject:slug}/documents/{document}', [SubjectDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/{subject:slug}/documents/{document}/telecharger', [SubjectDocumentController::class, 'download'])->name('documents.download');

    Route::get('/export-pdf', [SubjectPdfController::class, 'index'])->name('pdf.index');
    Route::get('/{subject:slug}/pdf', [SubjectPdfController::class, 'show'])->name('pdf.show');
    Route::get('/{subject:slug}/pdf-telecharger', [SubjectPdfController::class, 'download'])->name('pdf.download');

    Route::get('/{subject:slug}', [SubjectController::class, 'show'])->name('show');
});

/*
|---------------------------------------------------------------------------
| ARBRE DOCUMENTAIRE (membres authentifies)
|---------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('/arbre', [\App\Http\Controllers\DocumentTreeController::class, 'documentsIndex'])->name('tree.documents');
    Route::get('/arbre-data', [\App\Http\Controllers\DocumentTreeController::class, 'documentsData'])->name('tree.documents.data');
});

Route::middleware(['auth'])->prefix('sujets')->name('subjects.tree.')->group(function () {
    Route::get('/arbre', [\App\Http\Controllers\DocumentTreeController::class, 'subjectsIndex'])->name('index');
    Route::get('/arbre-data', [\App\Http\Controllers\DocumentTreeController::class, 'subjectsData'])->name('data');
});

/*
|---------------------------------------------------------------------------
| ROUTES AUTHENTIFICATION (Breeze)
|---------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::get('/', [AdminController::class, 'index'])->name('panel');
    Route::get('/routes', [AdminController::class, 'routes'])->name('routes');
    Route::get('/posts', [PostPublicController::class, 'adminIndex'])->name('posts.index');
    Route::get('/posts/create', [PostPublicController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostPublicController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostPublicController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostPublicController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostPublicController::class, 'destroy'])->name('posts.destroy');
    Route::patch('/posts/{post}/publish', [PostPublicController::class, 'publish'])->name('posts.publish');

    Route::get('/sections', [LandingSectionController::class, 'index'])->name('sections.index');
    Route::get('/sections/create', [LandingSectionController::class, 'create'])->name('sections.create');
    Route::post('/sections', [LandingSectionController::class, 'store'])->name('sections.store');
    Route::get('/sections/{section}/edit', [LandingSectionController::class, 'edit'])->name('sections.edit');
    Route::put('/sections/{section}', [LandingSectionController::class, 'update'])->name('sections.update');
    Route::patch('/sections/{section}/toggle', [LandingSectionController::class, 'toggle'])->name('sections.toggle');
    Route::delete('/sections/{section}', [LandingSectionController::class, 'destroy'])->name('sections.destroy');

    Route::get('/utilisateurs', [UserAdminController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/creer', [UserAdminController::class, 'create'])->name('users.create');
    Route::post('/utilisateurs', [UserAdminController::class, 'store'])->name('users.store');
    Route::get('/utilisateurs/{user}/modifier', [UserAdminController::class, 'edit'])->name('users.edit');
    Route::put('/utilisateurs/{user}', [UserAdminController::class, 'update'])->name('users.update');
    Route::delete('/utilisateurs/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');

    Route::get('/activite', [ActivityLogController::class, 'index'])->name('activity');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/first-setup', FirstTimeSetupController::class)->name('first-setup');
    Route::post('/first-setup', [FirstTimeSetupController::class, 'store'])->name('first-setup.store');
});

require __DIR__.'/auth.php';

Route::redirect('/register', '/login')->name('register');
