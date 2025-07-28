<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostAttachment;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\FeedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeedPostController extends Controller
{
    public function allPost()
    {
        try {
            // $posts = FeedPost::where('is_published', 1)->with(['user', 'attachments', 'sharedPosts.user', 'sharedPosts.attachments'])
            // ->withCount(['likes', 'comments', 'shares',]) // 👈 only count
            // ->latest()
            // ->paginate(10); // 👈 10 per page (adjust as needed)

            // // Add 'is_liked' for each post
            // $posts->getCollection()->transform(function ($post) {
            //     $post->append('is_liked');
            //     return $post;
            // });

            $userId = auth()->id();
            $posts = FeedPost::select('feed_posts.*')
                ->with(['user', 'attachments', 'sharedPosts.user', 'sharedPosts.attachments'])
                ->withCount(['likes', 'comments', 'shares'])
                ->leftJoin('post_likes as pl', function ($join) use ($userId) {
                    $join->on('feed_posts.id', '=', 'pl.post_id')
                        ->where('pl.user_id', '=', $userId);
                })
                ->addSelect(DB::raw('IF(pl.id IS NULL, false, true) as is_user_liked'))
                ->where('feed_posts.is_published', 1)
                ->orderByDesc('feed_posts.created_at')
                ->paginate(10);

            return response()->json([
                'message' => '',
                'status' => true,
                'data' => $posts,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function userPost()
    {
        try{
            $posts = FeedPost::where('user_id', auth()->id())->with(['user', 'attachments', 'sharedPosts.user', 'sharedPosts.attachments'])
            ->withCount(['likes', 'comments', 'shares']) // 👈 only count
            ->latest()
            ->paginate(10);

            return response()->json([
                'message' => '',
                'status' => true,
                'data' => $posts,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function userSharePost()
    {
        try {
            $posts = FeedPost::where('user_id', auth()->id())->where('is_shared', 1)->with(['user', 'attachments', 'sharedPosts.user', 'sharedPosts.attachments'])
            ->withCount(['likes', 'comments', 'shares']) // 👈 only count
            ->latest()
            ->paginate(10);

            return response()->json([
                'status' => true,
                'data' => $posts,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function userPostAttachments()
    {
        try {
            $posts = PostAttachment::where('user_id', auth()->id())->latest()->paginate(10);
            return response()->json([
                'status' => true,
                'data' => $posts,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'attachments.*' => 'file|mimetypes:image/*,video/*',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (empty($request->content) && !$request->hasFile('attachments')) {
                $validator->errors()->add('content', 'Content or at least one attachment is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = $request->user();
            $data = $request->only(['content', 'is_published', 'scheduled_at']);
            $data['user_id'] = $user->id;

            $post = FeedPost::create($data);

            // ✅ Upload & Save Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // $path = $file->store('attachments');
                    $filename = time() . $file->getClientOriginalName();
                    $file->move(public_path('attachment'), $filename);
                    $path = '/attachment/'.$filename;
                    $mime = $file->getClientMimeType();

                    PostAttachment::create([
                        'post_id' => $post->id,
                        'user_id'   => $user->id,
                        'attachment_url' => $path,
                        'mime_type' => $mime,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Post created successfully.',
                'status' => true,
                'data' => $post->load('attachments'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {

            $post = FeedPost::with('user', 'attachments','sharedPosts.user', 'sharedPosts.attachments')->withCount(['likes', 'comments', 'shares'])->findOrFail($id);
            return response()->json(['status' => true, 'data' => $post]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $post_id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'attachments.*' => 'file|mimetypes:image/*,video/*',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (empty($request->content) && !$request->hasFile('attachments')) {
                $validator->errors()->add('content', 'Content or at least one attachment is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = $request->user();
            $post = FeedPost::findOrFail($post_id);
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => 'You are not authorized to modify this post.', 'status' => false], 422);
            }

            $post->update($request->only(['content', 'is_published', 'scheduled_at']));

            // ✅ Upload & Save Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // $path = $file->store('attachments');
                    $filename = time() . $file->getClientOriginalName();
                    $file->move(public_path('attachment'), $filename);
                    $path = '/attachment/'.$filename;
                    $mime = $file->getClientMimeType();

                    PostAttachment::create([
                        'post_id' => $post_id,
                        'user_id'   => $user->id,
                        'attachment_url' => $path,
                        'mime_type' => $mime,
                    ]);
                }
            }

            return response()->json(['message' => '', 'status' => true, 'data' => $post->load('attachments')]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $post = FeedPost::with('attachments')->findOrFail($id);
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => 'You are not authorized to modify this post.', 'status' => false], 422);
            }

            // ✅ Delete all physical files
            foreach ($post->attachments as $attachment) {
                $filePath = public_path($attachment->file_path);
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $post->delete();
            return response()->json(['message' => 'Post deleted', 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function comment(Request $request, $id)
    {
        try {
            FeedPost::findOrFail($id);
            $data = $request->validate(['comment' => 'required']);
            $comment = PostComment::create([
                'post_id' => $id,
                'user_id' => $request->user()->id,
                'comment' => $data['comment'],
            ]);

            return response()->json([
                'status' => true,
                'data' => $comment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function like($id)
    {
        try {
            FeedPost::findOrFail($id);
            $user = auth()->user();
            $like = PostLike::where('post_id', $id)->where('user_id', $user->id)->first();
            if ($like) {
                $like->delete();
                $status = 'unliked';
            } else {
                PostLike::create(['post_id' => $id,'user_id' => $user->id]);
                $status = 'liked';
            }

            $likeCount = PostLike::where('post_id', $id)->count();

            return response()->json([
                'status' => $status,
                'like_count' => $likeCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

    }

    public function getLike($id)
    {
        try{
            $like = PostLike::where('post_id', $id)->with('user')->get();

            return response()->json([
                'status' => true,
                'data' => $like,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getComment($id)
    {
        try {

            $comment = PostComment::where('post_id', $id)->with('user')->latest()->paginate(20);
            return response()->json([
                'status' => true,
                'data' => $comment,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function share(Request $request, $id)
    {
        try {
            $original = FeedPost::findOrFail($id);
            $shared = FeedPost::create([
                'user_id' => auth()->id(),
                'is_shared' => true,
                'original_post_id' => $original->id,
            ]);

            return response()->json([
                'message' => 'Post shared successfully.',
                'status' => true,
                'data' => $shared,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteAttachment($id)
    {
        try {
            $attachment = PostAttachment::findOrFail($id);

            // Optional: check if user owns the parent post
            if ($attachment->feedPost->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized', 'status' => false], 403);
            }

            // Delete file from storage
            $filePath = public_path($attachment->attachment_url);
                if (File::exists($filePath)) {
                File::delete($filePath);
            }

            // Delete DB record
            $attachment->delete();

            return response()->json(['message' => 'Attachment deleted successfully', 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
