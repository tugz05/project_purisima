<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Resident\OnboardingController;
use App\Http\Controllers\Resident\AccountController;

// Load broadcasting channels
require __DIR__.'/channels.php';

// Broadcasting authentication routes
Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastingController::class, 'authenticate']);
Route::get('/broadcasting/auth', [\App\Http\Controllers\BroadcastingController::class, 'authenticate']);

// Broadcasting test routes (remove in production)
Route::middleware('auth')->group(function () {
    Route::get('/test-broadcasting', [\App\Http\Controllers\BroadcastingTestController::class, 'testBroadcasting'])->name('test-broadcasting');
    Route::get('/test-typing', [\App\Http\Controllers\BroadcastingTestController::class, 'testTyping'])->name('test-typing');
    Route::get('/test-reverb', function () {
        $conversation = \App\Models\Conversation::first();
        if (!$conversation) {
            return response()->json(['error' => 'No conversations found']);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated']);
        }

        // Test broadcasting
        $testMessage = new \App\Models\Message([
            'id' => 999,
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message from Reverb',
            'type' => 'text',
            'is_read' => false,
            'is_edited' => false,
            'created_at' => now(),
        ]);

        // Load sender relationship
        $testMessage->sender = $user;

        broadcast(new \App\Events\MessageSent($testMessage, $conversation));

        return response()->json([
            'success' => true,
            'message' => 'Test message broadcasted via Reverb',
            'conversation_id' => $conversation->id,
            'channel' => 'conversation.' . $conversation->id
        ]);
    })->name('test-reverb');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Quick route to create test users (remove after testing)
Route::get('/create-users', function () {
    $staff = \App\Models\User::firstOrCreate(
        ['email' => 'staff@test.com'],
        [
            'name' => 'Test Staff',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'staff',
        ]
    );

    $resident = \App\Models\User::firstOrCreate(
        ['email' => 'resident@test.com'],
        [
            'name' => 'Test Resident',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'resident',
        ]
    );

    return response()->json([
        'staff' => $staff,
        'resident' => $resident,
        'message' => 'Test users created! Use resident@test.com / password to test'
    ]);
});

Route::get('/debug-users', function () {
    $staff = \App\Models\User::whereIn('role', ['staff', 'admin'])->get();
    $residents = \App\Models\User::where('role', 'resident')->get();

    return response()->json([
        'staff_count' => $staff->count(),
        'staff_users' => $staff->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
        'resident_count' => $residents->count(),
        'resident_users' => $residents->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
        'total_users' => \App\Models\User::count()
    ]);
});

Route::get('/test-conversation', function () {
    $staff = \App\Models\User::whereIn('role', ['staff', 'admin'])->first();
    $resident = \App\Models\User::where('role', 'resident')->first();

    if (!$staff) {
        return response()->json(['error' => 'No staff members found'], 404);
    }

    if (!$resident) {
        return response()->json(['error' => 'No residents found'], 404);
    }

    try {
        $service = app(\App\Services\MessagingService::class);
        $conversation = $service->getOrCreateConversation($resident, $staff, 'Test Conversation');

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'staff' => $staff->name,
            'resident' => $resident->name
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Public API routes for landing page
Route::get('/api/barangay-officials', function () {
    try {
        $officials = \App\Models\BarangayOfficial::active()
            ->ordered()
            ->select(['first_name', 'last_name', 'middle_name', 'suffix', 'position', 'photo', 'term_start', 'term_end'])
            ->limit(6)
            ->get()
            ->map(function ($official) {
                return [
                    'first_name' => $official->first_name,
                    'last_name' => $official->last_name,
                    'middle_name' => $official->middle_name,
                    'suffix' => $official->suffix,
                    'position' => $official->position,
                    'photo' => $official->photo,
                    'term_start' => $official->term_start,
                    'term_end' => $official->term_end,
                    'is_active' => $official->is_active,
                ];
            });

        return response()->json($officials);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.barangay-officials');

// Public API route for announcements
Route::get('/api/announcements', function () {
    try {
        $announcements = \App\Models\Announcement::published()
            ->ordered()
            ->limit(6)
            ->get(['id', 'title', 'content', 'type', 'priority', 'is_featured', 'published_at', 'expires_at', 'image_path', 'author_name', 'author_position']);

        return response()->json($announcements);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.announcements');

// Image upload route for editor
Route::post('/api/upload-image', function (\Illuminate\Http\Request $request) {
    try {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $image = $request->file('image');
        $filename = time() . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('announcements/images', $filename, 'public');

        return response()->json([
            'success' => true,
            'image_url' => '/storage/' . $path,
            'filename' => $filename
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.upload-image');



Route::get('dashboard', function () {
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'staff' => redirect()->route('staff.dashboard'),
        'enforcer' => redirect()->route('enforcer.dashboard'),
        'resident' => redirect()->route('resident.dashboard'),
        default => Inertia::render('Dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// Role-based portals
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('admin/Dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Staff/Dashboard');
    })->name('dashboard');

    // Transaction management routes
    Route::get('transactions', [\App\Http\Controllers\Staff\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [\App\Http\Controllers\Staff\TransactionController::class, 'show'])->name('transactions.show');
    Route::put('transactions/{transaction}', [\App\Http\Controllers\Staff\TransactionController::class, 'update'])->name('transactions.update');
    Route::post('transactions/{transaction}/assign', [\App\Http\Controllers\Staff\TransactionController::class, 'assign'])->name('transactions.assign');
    Route::post('transactions/{transaction}/load-template', [\App\Http\Controllers\Staff\TransactionController::class, 'loadTemplate'])->name('transactions.load-template');
    Route::post('transactions/{transaction}/generate-ai', [\App\Http\Controllers\Staff\TransactionController::class, 'generateWithAI'])->name('transactions.generate-ai');
    Route::get('transactions/{transaction}/print-certificate', [\App\Http\Controllers\Staff\TransactionController::class, 'printCertificate'])->name('transactions.print-certificate');

    // Payment processing routes
    Route::get('transactions/{transaction}/payment', [\App\Http\Controllers\Staff\PaymentController::class, 'show'])->name('payments.show');
    Route::post('transactions/{transaction}/payment', [\App\Http\Controllers\Staff\PaymentController::class, 'process'])->name('payments.process');
    Route::post('transactions/{transaction}/payment/failed', [\App\Http\Controllers\Staff\PaymentController::class, 'markFailed'])->name('payments.mark-failed');
    Route::post('transactions/{transaction}/payment/refund', [\App\Http\Controllers\Staff\PaymentController::class, 'refund'])->name('payments.refund');
    Route::post('transactions/{transaction}/payment/reset', [\App\Http\Controllers\Staff\PaymentController::class, 'reset'])->name('payments.reset');
    Route::get('payments/statistics', [\App\Http\Controllers\Staff\PaymentController::class, 'statistics'])->name('payments.statistics');

    // Document Type management routes
    Route::resource('document-types', \App\Http\Controllers\Staff\DocumentTypeController::class);
    
    // Certificate Template management routes (nested under document-types)
    Route::prefix('document-types/{documentType}')->name('document-types.')->group(function () {
        Route::get('certificate-templates', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'index'])->name('certificate-templates.index');
        Route::get('certificate-templates/create', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'create'])->name('certificate-templates.create');
        Route::post('certificate-templates', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'store'])->name('certificate-templates.store');
        Route::get('certificate-templates/{certificateTemplate}', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'show'])->name('certificate-templates.show');
        Route::get('certificate-templates/{certificateTemplate}/edit', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'edit'])->name('certificate-templates.edit');
        Route::put('certificate-templates/{certificateTemplate}', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'update'])->name('certificate-templates.update');
        Route::delete('certificate-templates/{certificateTemplate}', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'destroy'])->name('certificate-templates.destroy');
        Route::post('certificate-templates/{certificateTemplate}/set-default', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'setDefault'])->name('certificate-templates.set-default');
        Route::post('certificate-templates/{certificateTemplate}/preview', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'preview'])->name('certificate-templates.preview');
        Route::get('certificate-templates/{certificateTemplate}/fields', [\App\Http\Controllers\Staff\CertificateTemplateController::class, 'getFields'])->name('certificate-templates.fields');
    });

    // Document Request processing routes (individual requests from residents)
    Route::resource('document-requests', \App\Http\Controllers\Staff\DocumentRequestController::class);

    // Barangay Officials management routes
    Route::resource('barangay-officials', \App\Http\Controllers\Staff\BarangayOfficialController::class);

    // Announcement management routes
    Route::resource('announcements', \App\Http\Controllers\Staff\AnnouncementController::class);
    Route::post('announcements/{announcement}/toggle-publication', [\App\Http\Controllers\Staff\AnnouncementController::class, 'togglePublication'])->name('announcements.toggle-publication');
    Route::post('announcements/{announcement}/toggle-featured', [\App\Http\Controllers\Staff\AnnouncementController::class, 'toggleFeatured'])->name('announcements.toggle-featured');
    Route::delete('announcements/{announcement}/attachments', [\App\Http\Controllers\Staff\AnnouncementController::class, 'deleteAttachment'])->name('announcements.delete-attachment');

    // Resident management routes
    Route::get('residents', [\App\Http\Controllers\Staff\ResidentController::class, 'index'])->name('residents.index');
    
    // Calamity Report management routes
    Route::get('calamity', [\App\Http\Controllers\Staff\CalamityReportController::class, 'index'])->name('calamity.index');
    Route::get('calamity/map', [\App\Http\Controllers\Staff\CalamityReportController::class, 'map'])->name('calamity.map');
    Route::get('calamity/active-reports', [\App\Http\Controllers\Staff\CalamityReportController::class, 'getActiveReports'])->name('calamity.active-reports');
    
    // Location tracking routes
    Route::post('location/update', [\App\Http\Controllers\Staff\LocationController::class, 'update'])->name('location.update');
    Route::get('location/residents', [\App\Http\Controllers\Staff\LocationController::class, 'getResidentLocations'])->name('location.residents');
    Route::get('calamity/{calamityReport}', [\App\Http\Controllers\Staff\CalamityReportController::class, 'show'])->name('calamity.show');
    Route::put('calamity/{calamityReport}', [\App\Http\Controllers\Staff\CalamityReportController::class, 'update'])->name('calamity.update');
    
    Route::post('announcements/reorder', [\App\Http\Controllers\Staff\AnnouncementController::class, 'reorder'])->name('announcements.reorder');

    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [\App\Http\Controllers\Staff\NotificationController::class, 'unread'])->name('unread');
        Route::get('/count', [\App\Http\Controllers\Staff\NotificationController::class, 'count'])->name('count');
        Route::post('/mark-read/{notification}', [\App\Http\Controllers\Staff\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-multiple-read', [\App\Http\Controllers\Staff\NotificationController::class, 'markMultipleAsRead'])->name('mark-multiple-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Staff\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [\App\Http\Controllers\Staff\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/delete-multiple', [\App\Http\Controllers\Staff\NotificationController::class, 'deleteMultiple'])->name('delete-multiple');
        Route::delete('/delete-all', [\App\Http\Controllers\Staff\NotificationController::class, 'deleteAll'])->name('delete-all');
    });

        // Messaging routes
        Route::prefix('messaging')->name('messaging.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\MessagingController::class, 'index'])->name('index');
            Route::get('/conversations/{conversation}', [\App\Http\Controllers\Staff\MessagingController::class, 'show'])->name('show');
            Route::get('/conversations/{conversation}/json', [\App\Http\Controllers\Staff\MessagingController::class, 'showAsJson'])->name('show-json');
            Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Staff\MessagingController::class, 'sendMessage'])->name('send-message');
            Route::post('/conversations/{conversation}/mark-read', [\App\Http\Controllers\Staff\MessagingController::class, 'markAsRead'])->name('mark-read');
            Route::post('/conversations/{conversation}/typing/start', [\App\Http\Controllers\Staff\MessagingController::class, 'startTyping'])->name('start-typing');
            Route::post('/conversations/{conversation}/typing/stop', [\App\Http\Controllers\Staff\MessagingController::class, 'stopTyping'])->name('stop-typing');
            Route::get('/conversations/{conversation}/typing', [\App\Http\Controllers\Staff\MessagingController::class, 'getTypingIndicators'])->name('typing-indicators');
            Route::post('/conversations/{conversation}/archive', [\App\Http\Controllers\Staff\MessagingController::class, 'archive'])->name('archive');
            Route::post('/conversations/{conversation}/restore', [\App\Http\Controllers\Staff\MessagingController::class, 'restore'])->name('restore');
            Route::get('/unread-count', [\App\Http\Controllers\Staff\MessagingController::class, 'getUnreadCount'])->name('unread-count');
        });
});

Route::middleware(['auth', 'role:enforcer'])->prefix('enforcer')->name('enforcer.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('enforcer/Dashboard');
    })->name('dashboard');
});

// Onboarding and account routes (excluded from profile completion check)
Route::middleware(['auth', 'role:resident'])->prefix('resident')->name('resident.')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    
    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('account', [AccountController::class, 'update'])->name('account.update');
});

// All other resident routes (require profile completion)
Route::middleware(['auth', 'role:resident', 'profile.completed'])->prefix('resident')->name('resident.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('resident/Dashboard');
    })->name('dashboard');

    // Household Members routes
    Route::resource('household-members', \App\Http\Controllers\Resident\HouseholdMemberController::class);

    // Transaction routes
    Route::resource('transactions', \App\Http\Controllers\Resident\TransactionController::class);

    // Calamity Report routes
    // Map route must be defined BEFORE resource route to avoid route conflict
    Route::get('calamity/map', [\App\Http\Controllers\Resident\LocationController::class, 'map'])->name('calamity.map');
    Route::resource('calamity', \App\Http\Controllers\Resident\CalamityReportController::class)->except(['create', 'edit', 'destroy']);
    Route::post('calamity/{calamityReport}/update-location', [\App\Http\Controllers\Resident\CalamityReportController::class, 'updateLocation'])->name('calamity.update-location');
    
    // Location tracking routes
    Route::post('location/update', [\App\Http\Controllers\Resident\LocationController::class, 'update'])->name('location.update');

        // Messaging routes
        Route::prefix('messaging')->name('messaging.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Resident\MessagingController::class, 'index'])->name('index');
            Route::post('/conversations/create', [\App\Http\Controllers\Resident\MessagingController::class, 'createConversation'])->name('conversations.create');
            Route::post('/conversations/create-general', [\App\Http\Controllers\Resident\MessagingController::class, 'createGeneralConversation'])->name('conversations.create-general');
            Route::post('/', [\App\Http\Controllers\Resident\MessagingController::class, 'store'])->name('store');
            Route::get('/conversations/{conversation}', [\App\Http\Controllers\Resident\MessagingController::class, 'show'])->name('show');
            Route::get('/conversations/{conversation}/json', [\App\Http\Controllers\Resident\MessagingController::class, 'showAsJson'])->name('show-json');
            Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Resident\MessagingController::class, 'sendMessage'])->name('send-message');
            Route::post('/conversations/{conversation}/typing/start', [\App\Http\Controllers\Resident\MessagingController::class, 'startTyping'])->name('start-typing');
            Route::post('/conversations/{conversation}/typing/stop', [\App\Http\Controllers\Resident\MessagingController::class, 'stopTyping'])->name('stop-typing');
            Route::get('/conversations/{conversation}/typing', [\App\Http\Controllers\Resident\MessagingController::class, 'getTypingIndicators'])->name('typing-indicators');
            Route::post('/conversations/{conversation}/archive', [\App\Http\Controllers\Resident\MessagingController::class, 'archive'])->name('archive');
            Route::get('/unread-count', [\App\Http\Controllers\Resident\MessagingController::class, 'getUnreadCount'])->name('unread-count');
        });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
