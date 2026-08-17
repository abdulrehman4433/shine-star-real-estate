<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Frontend\AccountDashboardController;
use App\Http\Controllers\Frontend\AgentDashboardController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\ChatController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\ProjectsController;
use App\Http\Controllers\Frontend\PropertiesController;
use App\Livewire\Admin\Blog\Posts\Form as BlogPostForm;
use App\Livewire\Admin\Blog\Posts\Manager as BlogPostManager;
use App\Livewire\Admin\Chat\Manager as AdminChatManager;
use App\Livewire\Admin\ContactMessages\Manager as AdminContactMessagesManager;
use App\Livewire\Admin\Footer\Manager as FooterManager;
use App\Livewire\Admin\Leads\Manager as AdminLeadManager;
use App\Livewire\Admin\Menus\Manager as MenuManager;
use App\Livewire\Admin\Pages\Builder as PageBuilder;
use App\Livewire\Admin\Pages\Manager as PageManager;
use App\Livewire\Admin\Properties\Manager as AdminPropertyManager;
use App\Livewire\Admin\Properties\Show as AdminPropertyShow;
use App\Livewire\Admin\Headers\Manager as HeaderTemplateManager;
use App\Livewire\Admin\Projects\Form as ProjectForm;
use App\Livewire\Admin\Projects\Manager as ProjectManager;
use App\Livewire\Admin\Projects\Show as ProjectShow;
use App\Livewire\Admin\Reviews\Manager as ReviewManager;
use App\Livewire\Admin\Settings\BackupManager;
use App\Livewire\Admin\Settings\GeneralManager;
use App\Livewire\Admin\Settings\SeoManager;
use App\Livewire\Admin\Settings\SystemCheckManager;
use App\Livewire\Admin\Cdn\Manager as CdnManager;
use App\Livewire\Frontend\Blog\Listing as BlogListing;
use App\Models\Setting;
use App\Livewire\Frontend\Favorites\MyFavorites;
use App\Livewire\Frontend\Leads\MyLeads;
use App\Livewire\Frontend\Leads\ShowLead;
use App\Livewire\Frontend\Properties\Listing as PropertyListing;
use App\Livewire\Frontend\Properties\MyListings;
use App\Livewire\Frontend\Properties\MyProperties;
use App\Livewire\Frontend\Properties\PropertyForm;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/properties', PropertyListing::class)->name('properties.index');
Route::get('/properties/{property}', [PropertiesController::class, 'show'])->name('properties.show');
Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectsController::class, 'show'])->name('projects.show');
Route::get('/blog', BlogListing::class)->name('blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/contact-us', [ContactController::class, 'submit'])->name('contact.submit');

// "My Properties" hub — any logged-in user (guest, user, agent, agency, admin) can browse all
// active properties and manage their own listings. Deliberately NOT behind the 'verified'
// middleware so self-registered guest users can use it immediately after registration.
Route::middleware(['auth'])->group(function () {
    Route::get('/my/properties', MyProperties::class)->name('my.properties.index');
    Route::get('/my/properties/create', PropertyForm::class)->name('my.properties.create');
    Route::get('/my/properties/{property}/edit', PropertyForm::class)->name('my.properties.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/favorites', MyFavorites::class)->name('favorites.index');
    Route::get('/leads/{lead}', ShowLead::class)->name('leads.show');
    Route::get('/chat/{conversation?}', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/properties/{property}/chat', [ChatController::class, 'start'])->name('chat.start');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:super-admin|admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/properties', AdminPropertyManager::class)->name('properties.index');
        Route::get('/properties/create', PropertyForm::class)->name('properties.create');
        Route::get('/properties/{property}/edit', PropertyForm::class)->name('properties.edit');
        Route::get('/properties/{property}', AdminPropertyShow::class)->name('properties.show');
        Route::get('/projects', ProjectManager::class)->name('projects.index');
        Route::get('/projects/create', ProjectForm::class)->name('projects.create');
        Route::get('/projects/{project:id}/edit', ProjectForm::class)->name('projects.edit');
        Route::get('/projects/{project:id}', ProjectShow::class)->name('projects.show');
        Route::get('/leads', AdminLeadManager::class)->name('leads.index');
        Route::get('/chat', AdminChatManager::class)->name('chat.index');
        Route::get('/contact-messages', AdminContactMessagesManager::class)->name('contact-messages.index');
        Route::get('/pages', PageManager::class)->name('pages.index');
        Route::get('/pages/{page:id}/edit', PageBuilder::class)->name('pages.edit');
        Route::get('/menus', MenuManager::class)->name('menus.index');
        Route::get('/headers', HeaderTemplateManager::class)->name('headers.index');
        Route::get('/footer', FooterManager::class)->name('footer.index');
        Route::get('/cdn', CdnManager::class)->name('cdn.index');
        Route::get('/reviews', ReviewManager::class)->name('reviews.index');
        Route::get('/settings', GeneralManager::class)->name('settings.index');
        Route::get('/settings/seo', SeoManager::class)->name('settings.seo');
        Route::get('/settings/backup', BackupManager::class)->name('settings.backup');
        Route::get('/settings/system-check', SystemCheckManager::class)->name('settings.system-check');

        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('/posts', BlogPostManager::class)->name('posts.index');
            Route::get('/posts/create', BlogPostForm::class)->name('posts.create');
            Route::get('/posts/{post:id}/edit', BlogPostForm::class)->name('posts.edit');
        });
    });

Route::prefix('agent')
    ->name('agent.')
    ->middleware(['auth', 'verified', 'role:agent|agency'])
    ->group(function () {
        Route::get('/', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/listings', MyListings::class)->name('listings.index');
        Route::get('/listings/create', PropertyForm::class)->name('listings.create');
        Route::get('/listings/{property}/edit', PropertyForm::class)->name('listings.edit');
        Route::get('/leads', MyLeads::class)->name('leads.index');
    });

Route::prefix('account')
    ->name('account.')
    ->middleware(['auth', 'verified', 'role:user'])
    ->group(function () {
        Route::get('/', [AccountDashboardController::class, 'index'])->name('dashboard');
    });

Route::get('/robots.txt', function () {
    return response(Setting::get('seo_robots_txt', "User-agent: *\nAllow: /"), 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

// Catch-all CMS page route — must stay LAST so it never shadows any route registered above it.
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
