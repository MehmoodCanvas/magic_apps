<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\FeedPostController;
use App\Http\Controllers\API\GoalController;
use App\Http\Controllers\API\IdeaController;
use App\Http\Controllers\API\SkillsController;
use App\Http\Controllers\API\StoreController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\CoachingSessionController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\AcademicPlanningController;
use App\Http\Controllers\API\ThawaniTestController;
use App\Http\Controllers\API\GrowthRoadmapController;
use App\Http\Controllers\API\StoryController;
use App\Http\Controllers\API\UserConnectionController;
use App\Http\Controllers\API\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::fallback(function(){
    return response()->json([
        'status' => false,
        'message' => 'API route not found.',
    ], 404);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-password', [AuthController::class, 'verifyAndReset']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [UserProfileController::class, 'update']);
    Route::post('/user/profile-picture', [UserProfileController::class, 'updateProfilePicture']);
    Route::post('/user/profile-picture/delete', [UserProfileController::class, 'deleteProfilePicture']);
    Route::post('/user/cover-image', [UserProfileController::class, 'updateCoverImage']);
    Route::post('/user/cover-image/delete', [UserProfileController::class, 'deleteCoverImage']);
    Route::get('/user/search', [UserProfileController::class, 'searchUsers']);
    Route::post('/reset-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/countries', [UserProfileController::class, 'countries']);
    Route::get('/qualification', [UserProfileController::class, 'qualifications']);
    Route::get('/employment-status', [UserProfileController::class, 'employmentStatuses']);
    Route::get('/work-style', [UserProfileController::class, 'workStyles']);
    Route::get('/categories', [UserProfileController::class, 'categories']);
    Route::get('/sub-categories', [UserProfileController::class, 'subCategories']);

    // Connection system
    Route::post('/user/connect/{id}', [UserConnectionController::class, 'sendRequest']);
    Route::post('/user/connect/accept/{id}', [UserConnectionController::class, 'acceptRequest']);
    Route::post('/user/connect/reject/{id}', [UserConnectionController::class, 'rejectRequest']);
    Route::post('/user/connect/unconnect/{id}', [UserConnectionController::class, 'unconnect']);
    Route::get('/user/connections', [UserConnectionController::class, 'myConnections']);
    Route::get('/user/connect/requests', [UserConnectionController::class, 'pendingRequests']);

    // Block & Report
    Route::post('/user/block/{id}', [UserConnectionController::class, 'blockUser']);
    Route::post('/user/unblock/{id}', [UserConnectionController::class, 'unblockUser']);
    Route::post('/user/report/{id}', [UserConnectionController::class, 'reportUser']);

    // Follows & Profile Details
    Route::post('/user/follow/{id}', [UserConnectionController::class, 'followUser']);
    Route::post('/user/unfollow/{id}', [UserConnectionController::class, 'unfollowUser']);
    Route::get('/user/followers/{id?}', [UserConnectionController::class, 'followersList']);
    Route::get('/user/followings/{id?}', [UserConnectionController::class, 'followingList']);
    Route::get('/user/profile/{id}', [UserConnectionController::class, 'getProfileDetails']);

    // Chat & Group Management System
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/list', [ChatController::class, 'getChatList']);
    Route::get('/chat/direct/{userId}', [ChatController::class, 'openDirectChat']);
    Route::post('/chat/group/create', [ChatController::class, 'createGroup']);
    Route::get('/chat/conversation/{conversationId}', [ChatController::class, 'getMessages']);
    Route::post('/chat/group/{conversationId}/update', [ChatController::class, 'updateGroup']);
    Route::post('/chat/group/{conversationId}/members/add', [ChatController::class, 'addMembers']);
    Route::post('/chat/group/{conversationId}/members/remove', [ChatController::class, 'removeMembers']);

    // Growth Roadmap
    Route::get('/growth-roadmap', [GrowthRoadmapController::class, 'index']);

    Route::get('/posts', [FeedPostController::class, 'allPost']);
    Route::get('/user/posts', [FeedPostController::class, 'userPost']);
    Route::get('/user/posts/{id}', [FeedPostController::class, 'userPostById']);
    Route::get('/user/share/posts', [FeedPostController::class, 'userSharePost']);
    Route::get('/user/post/attachments', [FeedPostController::class, 'userPostAttachments']);
    Route::get('/post/{id}', [FeedPostController::class, 'show']);

    Route::post('/create/post', [FeedPostController::class, 'store']);
    Route::post('/update/post/{post_id}', [FeedPostController::class, 'update']);
    Route::post('/delete/post/{id}', [FeedPostController::class, 'destroy']);

    // Route::post('/posts/{id}/attachments', [FeedPostController::class, 'addAttachment']);
    Route::post('/delete/attachments/{id}', [FeedPostController::class, 'deleteAttachment']);
    Route::post('/post/like/{id}', [FeedPostController::class, 'like']);
    Route::post('/post/comment/{id}', [FeedPostController::class, 'comment']);
    Route::post('/post/comment/like/{comment_id}', [FeedPostController::class, 'commentLike']); // Like/unlike
    Route::post('/post/share/{id}', [FeedPostController::class, 'share']);

    Route::get('/post/like/{id}', [FeedPostController::class, 'getLike']);
    Route::get('/post/comment/{id}', [FeedPostController::class, 'getComment']);
    Route::get('/post/share/{id}', [FeedPostController::class, 'share']);

    // Stories
    Route::get('/stories', [StoryController::class, 'index']);
    Route::post('story/create', [StoryController::class, 'store']);
    Route::post('story/update/{id}', [StoryController::class, 'update']);
    Route::post('story/delete/{id}', [StoryController::class, 'destroy']);
    Route::post('story/delete-attachment/{id}', [StoryController::class, 'deleteAttachment']);


    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/create/goals', [GoalController::class, 'store']);
    Route::post('/update/goals/{id}', [GoalController::class, 'update']);
    Route::post('/update-status/goals/{id}', [GoalController::class, 'updateStatus']);
    Route::post('/delete/goals/{id}', [GoalController::class, 'destroy']);


    Route::prefix('skills')->group(function () {
        Route::get('/types', [SkillsController::class, 'getTypes']);
        Route::post('/create-type', [SkillsController::class, 'createType']);

        Route::get('/', [SkillsController::class, 'index']);
        Route::get('/{id}', [SkillsController::class, 'show']);
        Route::post('/create', [SkillsController::class, 'store']);
        Route::post('/update/{id}', [SkillsController::class, 'update']);
        Route::post('/delete/{id}', [SkillsController::class, 'destroy']);

        Route::post('/add/attachments/{skill_id}', [SkillsController::class, 'uploadAttachments']);
        Route::post('/remove/attachment/{id}', [SkillsController::class, 'deleteAttachment']);
    });

    Route::prefix('store')->group(function () {
        Route::get('/categories', [StoreController::class, 'Categories']);
        Route::post('/categoryDetails', [StoreController::class, 'categoryDetails']);

        Route::get('/products', [StoreController::class, 'product']);
        Route::get('/product/{slug}', [StoreController::class, 'producedetails']);
        Route::post('/checkout', [StoreController::class, 'orders']);
        Route::get('/orders/history', [StoreController::class, 'orderHistory']);
        Route::get('/order/{order_id}', [StoreController::class, 'orderDetails']);

        // Mobile app calls this after closing WebView to get verified status (needs auth)
        Route::get('/payment/verify/{order_id}', [StoreController::class, 'verifyPayment']);
    });

    Route::prefix('consultation')->group(function () {
         // Category CRUD if needed
        Route::get('/categories', [ConsultationController::class,'categoriesList']);

        Route::post('/create', [ConsultationController::class,'CreateConsultations']);
        Route::post('/update/{id}', [ConsultationController::class,'updateConsultations']);
        Route::post('/delete/{id}', [ConsultationController::class,'destroyConsultations']);

        Route::get('/', [ConsultationController::class,'allConsultations']);
        Route::get('/my-consultation', [ConsultationController::class,'myConsultations']);
        Route::get('/single/{id}', [ConsultationController::class,'singleConsultations']);

        Route::post('/request/{consultationId}', [ConsultationController::class,'StoreConsultationRequest']);

        // Tabs for user
        Route::get('/send-request', [ConsultationController::class,'myRequests']);
        Route::get('/received-requests', [ConsultationController::class,'receivedRequests']);
        Route::get('/single-request/{requestId}', [ConsultationController::class,'singleRequest']);

        Route::post('/update-request/status', [ConsultationController::class,'updateRequest']);
    });


    Route::prefix('ideas')->group(function () {
        Route::post('/create', [IdeaController::class, 'store']);
        Route::post('/update/{id}', [IdeaController::class, 'update']);
        Route::post('/add/attachments/{idea_id}', [IdeaController::class, 'uploadAttachments']);
        Route::post('/remove/attachment/{id}', [IdeaController::class, 'deleteAttachment']);

        Route::post('/delete/{id}', [IdeaController::class, 'destroy']);

        Route::get('/my-ideas', [IdeaController::class, 'myIdeas']);
        Route::get('/single/{id}', [IdeaController::class, 'singleIdea']);
        Route::get('/all-ideas', [IdeaController::class, 'allIdeas']);
    });

    Route::prefix('coaching-sessions')->group(function () {
        Route::get('/', [CoachingSessionController::class, 'index']);
        Route::get('/{id}', [CoachingSessionController::class, 'show']);
        Route::get('/{id}/slots', [CoachingSessionController::class, 'getSlots']);
    });

    Route::prefix('my-coaching-sessions')->group(function () {
        Route::get('/', [CoachingSessionController::class, 'mySessions']);
        Route::get('/bookings', [BookingController::class, 'mySessionBookings']);
        Route::post('/create', [CoachingSessionController::class, 'store']);
        Route::post('/update/{id}', [CoachingSessionController::class, 'update']);
        Route::post('/delete/{id}', [CoachingSessionController::class, 'destroy']);
    });

    Route::prefix('session-bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/{id}', [BookingController::class, 'getBookings']);
        Route::post('/create', [BookingController::class, 'store']);
    });

    Route::prefix('academic-subjects')->group(function () {
        Route::get('/', [AcademicPlanningController::class, 'allSubjects']);
    });

    Route::prefix('academic-plannings')->group(function () {
        Route::get('/', [AcademicPlanningController::class, 'index']);
        Route::get('/{id}', [AcademicPlanningController::class, 'show']);
        Route::post('/create', [AcademicPlanningController::class, 'store']);
        Route::post('/update/{id}', [AcademicPlanningController::class, 'update']);
        Route::post('/delete/{id}', [AcademicPlanningController::class, 'destroy']);
        Route::post('/add/attachments/{id}', [AcademicPlanningController::class, 'uploadAttachments']);
        Route::post('/remove/attachment/{id}', [AcademicPlanningController::class, 'deleteAttachment']);
    });

    // Admin Badge Management Routes
    // (Removed - Migrating to Web UI)

    // User Badge Progress Route
    Route::get('/badges-progress', [\App\Http\Controllers\API\BadgeController::class, 'userBadges']);

    // User Achievements Summary (Trophies + Badges)
    Route::get('/achievements', [\App\Http\Controllers\API\BadgeController::class, 'userAchievements']);

    // Thawani Test Routes
    
});

Route::prefix('thawani-test')->group(function () {
    Route::get('/debug', [ThawaniTestController::class, 'debugCheck']);
    Route::get('/checkout', [ThawaniTestController::class, 'testCheckout'])->name('thawani.checkout');
    Route::get('/success', [ThawaniTestController::class, 'success'])->name('thawani.success');
    Route::get('/cancel', [ThawaniTestController::class, 'cancel'])->name('thawani.cancel');
});

// Thawani payment callbacks (outside auth - Thawani redirects browser here without token)
Route::prefix('store/payment')->group(function () {
    Route::get('/success', [StoreController::class, 'paymentSuccess']);
    Route::get('/cancel', [StoreController::class, 'paymentCancel']);
});

// email=wahjsgffdjur@mailinator.com
// password=wajur@
// 196|q7WxDSaQHANin51k41HKpJ0HNXPJeNr2tgn5WiaO625e6138
// 197|HT68qYm8OedvDIafVqtOp89tvYJS0fiiKFXCIosS7b25a533
