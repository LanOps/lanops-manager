<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\BigScreenController;
use App\Http\Controllers\Events;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\PollsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

/**
 * Login & Register
 */
Route::get('/register/{method}', [Auth\AuthController::class, 'showRegister']);
Route::post('/register/{method}', [Auth\AuthController::class, 'register']);

Route::get('/login', [Auth\AuthController::class, 'prompt'])->name('login');
Route::get('/login/steam', [Auth\SteamController::class, 'login']);

Route::post('/login/standard', [Auth\LoginController::class, 'login']);
Route::get('/register/email/verify', [Auth\VerificationController::class, 'show'])->name('verification.notice');
Route::get('/register/email/verify/{id}/{hash}', [Auth\VerificationController::class, 'verify'])->name('verification.verify');
Route::get('/register/email/resend', [Auth\VerificationController::class, 'resend'])->name('verification.resend');

Route::get('/login/forgot', [Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/login/forgot', [Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');

Route::post('/login/reset/password', [Auth\ResetPasswordController::class, 'reset'])->name('password.update');
Route::get('/login/reset/{token}', [Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');

Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::get('/account', [AccountController::class, 'index'])->name('accounts');
    Route::get('/account/tickets/{participant}', [AccountController::class, 'showTicket']);
    Route::post('/account', [AccountController::class, 'update']);
    Route::get('/account/discord/callback', [AccountController::class, 'linkDiscord']);
    Route::post('/account/discord/unlink', [AccountController::class, 'unlinkDiscord']);
});

Route::group(['middleware' => ['auth']], function () {
    Route::post('/logout', [Auth\AuthController::class, 'logout']);
});

/**
 * Index Page
 */
Route::get('/', [HomeController::class, 'index']);

/**
 * News Pages
 */
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{newsArticle}', [NewsController::class, 'show']);
Route::group(['middleware' => ['auth', 'banned', 'verified', 'throttle:10,1']], function () {
    Route::post('/news/{newsArticle}/comments', [NewsController::class, 'storeComment']);
    Route::post('/news/{newsArticle}/comments/{newsComment}', [NewsController::class, 'editComment']);
    Route::post('/news/{newsArticle}/comments/{newsComment}/report', [NewsController::class, 'reportComment']);
    Route::post('/news/{newsArticle}/comments/{newsComment}/delete', [NewsController::class, 'destroyComment']);
});
Route::get('/news/tags/{newsTag}', [NewsController::class, 'showTag']);

/**
 * Events
 */
Route::get('/events', [Events\EventsController::class, 'index']);
Route::get('/events/{event}', [Events\EventsController::class, 'show']);
// Secret link to a Private event
Route::get('/e/{key}', [Events\EventsController::class, 'showPrivate'])->middleware('throttle:30,1');

/**
 * Misc Pages
 */
Route::get('/about', [HomeController::class, 'about']);
Route::get('/contact', [HomeController::class, 'contact']);
Route::get('/terms', [HomeController::class, 'terms']);
Route::get('/info', [HomeController::class, 'info']);
Route::get('/sitemap', [SitemapController::class, 'renderSitemap']);

/**
 * Tickets
 */
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::get('/tickets/retrieve/{participant:qr_token}', [Events\TicketsController::class, 'retrieve']);
    Route::post('/tickets/purchase/{ticket}', [Events\TicketsController::class, 'purchase']);
});

/**
 * Gifts
 */
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::get('/gift/accept', [Events\ParticipantsController::class, 'acceptGift'])->middleware('throttle:10,1');
    Route::post('/gift/{participant}', [Events\ParticipantsController::class, 'gift']);
    Route::post('/gift/{participant}/revoke', [Events\ParticipantsController::class, 'revokeGift']);
});

/**
 * Galleries
 */
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/gallery/{album}', [GalleryController::class, 'show']);

/**
 * Payments
 */
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::get('/payment/checkout', [PaymentsController::class, 'showCheckout']);
    Route::post('/payment/checkout/code', [PaymentsController::class, 'applyDiscountCode']);
    Route::get('/payment/review/{paymentGateway}', [PaymentsController::class, 'showReview']);
    Route::get('/payment/details/{paymentGateway}', [PaymentsController::class, 'showDetails']);
    Route::get('/payment/callback', [PaymentsController::class, 'processCallback']);
    Route::post('/payment/post', [PaymentsController::class, 'postPayment']);
    Route::get('/payment/failed', [PaymentsController::class, 'showFailed']);
    Route::get('/payment/cancelled', [PaymentsController::class, 'showCancelled']);
    Route::get('/payment/successful/{purchase}', [PaymentsController::class, 'showSuccessful']);
});

/**
 * Seating
 */
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::post('/events/{event}/seating/{seatingPlan}', [Events\SeatingController::class, 'update']);
    Route::delete('/events/{event}/seating/{seatingPlan}', [Events\SeatingController::class, 'destroy']);
});

/**
 * Polls
 */
Route::get('/polls', [PollsController::class, 'index']);
Route::get('/polls/{poll}', [PollsController::class, 'show']);
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::post('/polls/{poll}/options', [PollsController::class, 'storeOption']);
    Route::post('/polls/{poll}/options/{option}/vote', [PollsController::class, 'vote']);
    Route::post('/polls/{poll}/options/{option}/abstain', [PollsController::class, 'abstain']);
});

/**
 * Big Screen
 */
Route::get('/bigscreen/timetable', [BigScreenController::class, 'timetable']);
Route::get('/bigscreen/seating', [BigScreenController::class, 'seating']);

/**
 * Tournaments
 */
Route::get('/tournaments', [TournamentsController::class, 'index']);
Route::get('/tournaments/{tournament}', [TournamentsController::class, 'show']);
Route::group(['middleware' => ['auth', 'banned', 'verified']], function () {
    Route::post('/tournaments/{tournament}/register', [TournamentsController::class, 'register']);
    Route::post('/tournaments/{tournament}/unregister', [TournamentsController::class, 'unregister']);
    Route::post('/tournaments/{tournament}/registerTeam', [TournamentsController::class, 'registerTeam']);
});

/**
 * Admin
 */
Route::group(['middleware' => ['admin', 'banned']], function () {

    /**
     * Index Page
     */
    Route::get('/admin', [Admin\AdminController::class, 'index']);

    /**
     * Events
     */
    Route::get('/admin/events', [Admin\Events\EventsController::class, 'index']);
    Route::post('/admin/events', [Admin\Events\EventsController::class, 'store']);
    Route::get('/admin/events/{event}', [Admin\Events\EventsController::class, 'show']);
    Route::post('/admin/events/{event}', [Admin\Events\EventsController::class, 'update']);
    Route::delete('/admin/events/{event}', [Admin\Events\EventsController::class, 'destroy']);
    Route::post('/admin/events/{event}/information', [Admin\Events\InformationController::class, 'store']);
    Route::post('/admin/information/{information}', [Admin\Events\InformationController::class, 'update']);
    Route::delete('/admin/information/{information}', [Admin\Events\InformationController::class, 'destroy']);
    Route::post('/admin/events/{event}/discord/link', [Admin\Events\EventsController::class, 'linkDiscord']);
    Route::post('/admin/events/{event}/discord/unlink', [Admin\Events\EventsController::class, 'unlinkDiscord']);
    Route::post('/admin/events/{event}/duplicate', [Admin\Events\EventsController::class, 'duplicate']);
    Route::post('/admin/events/{event}/private-link', [Admin\Events\EventsController::class, 'regeneratePrivateLink']);

    /**
     * Seating
     */
    Route::get('/admin/events/{event}/seating', [Admin\Events\SeatingController::class, 'index']);
    Route::post('/admin/events/{event}/seating', [Admin\Events\SeatingController::class, 'store']);
    Route::get('/admin/events/{event}/seating/{seatingPlan}', [Admin\Events\SeatingController::class, 'show']);
    Route::post('/admin/events/{event}/seating/{seatingPlan}', [Admin\Events\SeatingController::class, 'update']);
    Route::delete('/admin/events/{event}/seating/{seatingPlan}', [Admin\Events\SeatingController::class, 'destroy']);
    Route::post('/admin/events/{event}/seating/{seatingPlan}/seat', [Admin\Events\SeatingController::class, 'updateSeat']);
    Route::delete('/admin/events/{event}/seating/{seatingPlan}/seat', [Admin\Events\SeatingController::class, 'clearSeat']);
    Route::post('/admin/events/{event}/seating/{seatingPlan}/seat/disable', [Admin\Events\SeatingController::class, 'disableSeat']);

    /**
     * Timetables
     */
    Route::get('/admin/events/{event}/timetables', [Admin\Events\TimetablesController::class, 'index']);
    Route::post('/admin/events/{event}/timetables', [Admin\Events\TimetablesController::class, 'store']);
    Route::get('/admin/events/{event}/timetables/{timetable}', [Admin\Events\TimetablesController::class, 'show']);
    Route::post('/admin/events/{event}/timetables/{timetable}', [Admin\Events\TimetablesController::class, 'update']);
    Route::delete('/admin/events/{event}/timetables/{timetable}', [Admin\Events\TimetablesController::class, 'destroy']);
    Route::post('/admin/events/{event}/timetables/{timetable}/data', [Admin\Events\TimetableDataController::class, 'store']);
    Route::post(
        '/admin/events/{event}/timetables/{timetable}/data/{data}',
        [Admin\Events\TimetableDataController::class, 'update']
    );
    Route::delete(
        '/admin/events/{event}/timetables/{timetable}/data/{data}',
        [Admin\Events\TimetableDataController::class, 'destroy']
    );

    /**
     * Participants
     */
    Route::get('/admin/events/{event}/participants', [Admin\Events\ParticipantsController::class, 'index']);
    Route::get('/admin/events/{event}/participants/{participant}', [Admin\Events\ParticipantsController::class, 'show']);
    Route::post(
        '/admin/events/{event}/participants/{participant}/signin',
        [Admin\Events\ParticipantsController::class, 'signIn']
    );
    Route::post(
        '/admin/events/{event}/participants/{participant}/transfer',
        [Admin\Events\ParticipantsController::class, 'transfer']
    );

    /**
     * Tickets
     */
    Route::get('/admin/events/{event}/tickets', [Admin\Events\TicketsController::class, 'index']);
    Route::post('/admin/events/{event}/tickets', [Admin\Events\TicketsController::class, 'store']);
    Route::get('/admin/events/{event}/tickets/{ticket}', [Admin\Events\TicketsController::class, 'show']);
    Route::post('/admin/events/{event}/tickets/{ticket}', [Admin\Events\TicketsController::class, 'update']);
    Route::delete('/admin/events/{event}/tickets/{ticket}', [Admin\Events\TicketsController::class, 'destroy']);

    /**
     * Gifts
     */
    Route::post('/admin/events/{event}/freebies/admin', [Admin\Events\EventsController::class, 'freeStaff']);
    Route::post('/admin/events/{event}/freebies/gift', [Admin\Events\EventsController::class, 'freeGift']);

    /**
     * Venues
     */
    Route::get('/admin/venues', [Admin\Events\VenuesController::class, 'index']);
    Route::post('/admin/venues', [Admin\Events\VenuesController::class, 'store']);
    Route::get('/admin/venues/{venue}', [Admin\Events\VenuesController::class, 'show']);
    Route::post('/admin/venues/{venue}', [Admin\Events\VenuesController::class, 'update']);
    Route::delete('/admin/venues/{venue}', [Admin\Events\VenuesController::class, 'destroy']);
    Route::delete('/admin/venues/{venue}/{image}', [Admin\Events\VenuesController::class, 'destroyImage']);

    /**
     * Galleries
     */
    Route::get('/admin/gallery', [Admin\GalleryController::class, 'index']);
    Route::post('/admin/gallery', [Admin\GalleryController::class, 'store']);
    Route::get('/admin/gallery/{album}', [Admin\GalleryController::class, 'show']);
    Route::post('/admin/gallery/{album}', [Admin\GalleryController::class, 'update']);
    Route::delete('/admin/gallery/{album}', [Admin\GalleryController::class, 'destroy']);
    Route::post('/admin/gallery/{album}/ingest', [Admin\GalleryController::class, 'ingestImages']);
    Route::post('/admin/gallery/{album}/upload', [Admin\GalleryController::class, 'uploadImage']);
    Route::delete('/admin/gallery/{album}/{image}', [Admin\GalleryController::class, 'destroyImage']);

    /**
     * Users
     */
    Route::get('/admin/users', [Admin\UsersController::class, 'index']);
    Route::post('/admin/users/referralcodes', [Admin\UsersController::class, 'generalReferralCodes']);
    Route::get('/admin/users/{user}', [Admin\UsersController::class, 'show']);
    Route::post('/admin/users/{user}', [Admin\UsersController::class, 'update']);
    Route::post('/admin/users/{user}/email', [Admin\UsersController::class, 'updateEmail']);
    Route::post('/admin/users/{user}/admin', [Admin\UsersController::class, 'grantAdmin']);
    Route::delete('/admin/users/{user}/admin', [Admin\UsersController::class, 'removeAdmin']);
    Route::post('/admin/users/{user}/ban', [Admin\UsersController::class, 'ban']);
    Route::post('/admin/users/{user}/unban', [Admin\UsersController::class, 'unban']);

    /**
     * News
     */
    Route::get('/admin/news', [Admin\NewsController::class, 'index']);
    Route::post('/admin/news', [Admin\NewsController::class, 'store']);
    Route::get('/admin/news/{newsArticle}', [Admin\NewsController::class, 'show']);
    Route::post('/admin/news/{newsArticle}', [Admin\NewsController::class, 'update']);
    Route::delete('/admin/news/{newsArticle}', [Admin\NewsController::class, 'destroy']);
    Route::post('/admin/news/{newsArticle}/comments/{newsComment}/delete', [Admin\NewsController::class, 'destroyComment']);
    Route::post('/admin/news/{newsArticle}/comments/{newsComment}/approve', [Admin\NewsController::class, 'approveComment']);
    Route::post('/admin/news/{newsArticle}/comments/{newsComment}/reject', [Admin\NewsController::class, 'rejectComment']);
    Route::post(
        '/admin/news/{newsArticle}/comments/{newsComment}/reports/{newsCommentReport}/delete',
        [Admin\NewsController::class, 'destroyReport']
    );

    /**
     * Polls
     */
    Route::get('/admin/polls', [Admin\PollsController::class, 'index']);
    Route::post('/admin/polls', [Admin\PollsController::class, 'store']);
    Route::get('/admin/polls/{poll}', [Admin\PollsController::class, 'show']);
    Route::post('/admin/polls/{poll}', [Admin\PollsController::class, 'update']);
    Route::post('/admin/polls/{poll}/end', [Admin\PollsController::class, 'endPoll']);
    Route::delete('/admin/polls/{poll}', [Admin\PollsController::class, 'destroy']);
    Route::post('/admin/polls/{poll}/options', [Admin\PollsController::class, 'storeOption']);
    Route::delete('/admin/polls/{poll}/options/{option}', [Admin\PollsController::class, 'destroyOption']);

    /**
     * Purchases
     */
    Route::get('/admin/purchases', [Admin\PurchasesController::class, 'index']);
    Route::get('/admin/purchases/{purchase}', [Admin\PurchasesController::class, 'show']);
    Route::post('/admin/purchases/{purchase}/refund', [Admin\PurchasesController::class, 'refund']);

    /**
     * Tournaments
     */
    Route::get('/admin/tournaments', [Admin\TournamentsController::class, 'index']);
    Route::post('/admin/tournaments', [Admin\TournamentsController::class, 'store']);
    Route::get('/admin/tournaments/{tournament}', [Admin\TournamentsController::class, 'show']);
    Route::post('/admin/tournaments/{tournament}', [Admin\TournamentsController::class, 'update']);
    Route::delete('/admin/tournaments/{tournament}', [Admin\TournamentsController::class, 'destroy']);
});
