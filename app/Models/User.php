<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'can_manage_sessions',
        'is_active',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'can_manage_sessions' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // Connections sent by this user
    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'sender_id');
    }

    // Connections received by this user
    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    // Users this user has blocked
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_id')->withTimestamps();
    }

    // Users who have blocked this user
    public function blockers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'user_id')->withTimestamps();
    }

    // Get all accepted connections as a query builder
    public function connections()
    {
        $userId = $this->id;
        return User::whereIn('id', function($query) use ($userId) {
            $query->select('receiver_id')
                ->from('connections')
                ->where('sender_id', $userId)
                ->where('status', 'accepted')
                ->union(
                    $query->newQuery()
                        ->select('sender_id')
                        ->from('connections')
                        ->where('receiver_id', $userId)
                        ->where('status', 'accepted')
                );
        });
    }

    // Helper to check if connected with a specific user
    public function isConnectedWith($userId)
    {
        return Connection::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('sender_id', $this->id)->where('receiver_id', $userId);
                })->orWhere(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)->where('receiver_id', $this->id);
                });
            })->exists();
    }

    // Helper to check if a pending request exists either way
    public function hasPendingRequestWith($userId)
    {
        return Connection::where('status', 'pending')
            ->where(function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('sender_id', $this->id)->where('receiver_id', $userId);
                })->orWhere(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)->where('receiver_id', $this->id);
                });
            })->exists();
    }

    // Helper to check if this user blocked someone
    public function hasBlocked($userId)
    {
        return $this->blockedUsers()->where('blocked_id', $userId)->exists();
    }

    // Helper to check if this user is blocked by someone
    public function isBlockedBy($userId)
    {
        return $this->blockers()->where('user_id', $userId)->exists();
    }

    // Users who follow this user
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    // Users this user is following
    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    // Helper to check if this user follows another user
    public function isFollowing($userId)
    {
        return $this->followings()->where('following_id', $userId)->exists();
    }

    // Helper to check if this user is followed by another user
    public function isFollowedBy($userId)
    {
        return $this->followers()->where('follower_id', $userId)->exists();
    }

    // Conversations this user is part of
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_members', 'user_id', 'conversation_id')->withTimestamps();
    }
}
