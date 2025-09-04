<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FeedPostController;
use App\Http\Controllers\API\GoalController;
use App\Http\Controllers\API\SkillsController;
use App\Http\Controllers\API\StoreController;
use App\Http\Controllers\API\UserProfileController;

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
    Route::post('/reset-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/countries', [UserProfileController::class, 'countries']);
    Route::get('/qualification', [UserProfileController::class, 'qualifications']);
    Route::get('/employment-status', [UserProfileController::class, 'employmentStatuses']);
    Route::get('/work-style', [UserProfileController::class, 'workStyles']);
    Route::get('/categories', [UserProfileController::class, 'categories']);
    Route::get('/sub-categories', [UserProfileController::class, 'subCategories']);

    Route::get('/posts', [FeedPostController::class, 'allPost']);
    Route::get('/user/posts', [FeedPostController::class, 'userPost']);
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

    });

});
