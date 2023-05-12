<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int id
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property string $name
 * @property string $spotify_token
 * @property int $spotify_expiration
 * @property string $spotify_refresh_token
 * @property string $spotify_email
 * @property string $spotify_user_id
 * @property string $apple_music_token
 * @property string apple_music_storefront
 * @property string $tidal_token
 * @property string $tidal_refresh_token
 * @property int $tidal_expiration
 * @property string $tidal_email
 * @property string $tidal_user_id
 * @property string $tidal_username
 * @property string $ios_device_tokens
 * @property bool $ios_notifications_enabled
 * @property bool $is_running
 * @property string $android_device_tokens
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        "name",
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'spotify_token',
        'spotify_refresh_token',
        'apple_music_token',
        'apple_music_storefront'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_running' => 'boolean'
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubscription(): Subscription|HasMany|null
    {
        if ($this->subscriptions()->count() < 1) return null;

        $subscription = $this->subscriptions()->latest()->first();

        if (strtotime($subscription->end_date) < time()) {
            return null;
        }

        return $subscription;
    }

    public function isSubscribed(): bool
    {
        if ($this->subscriptions()->count() > 0) {
            $sub = $this->getSubscription();

            if (is_null($sub)) return false;

            if (strtotime($sub->end_date) >= time()) {
                return true;
            }
        }

        return false;
    }

    public function hasSpotify(): bool
    {
        return isset($this->spotify_token);
    }

    public function hasAppleMusic(): bool
    {
        return isset($this->apple_music_token);
    }

    public function hasTidal(): bool
    {
        return isset($this->tidal_token);
    }

    public function iosNotificationsEnabled(): bool
    {
        $tokens = $this->iosDeviceTokens();

        if (!$tokens || count($tokens) < 1) {
            return false;
        }

        return true;
    }

    public function iosDeviceTokens(): ?array
    {
        if ($this->ios_device_tokens) {
            return json_decode($this->ios_device_tokens);
        }

        return null;
    }

    public function addIosDeviceToken(string $token): void
    {
        $tokens = $this->iosDeviceTokens();

        if ($tokens && in_array($token, $tokens)) {
            return;
        }

        $tokens[] = $token;

        $this->ios_device_tokens = json_encode($tokens);
        $this->save();
    }

    public function removeIosDeviceToken(string $token): void
    {
        $tokens = $this->iosDeviceTokens();

        $key = array_search($token, $tokens);

        if ($key !== false) {
            unset($tokens[$key]);
        }

        $this->ios_device_tokens = json_encode($tokens);
        $this->save();
    }

    public function androidNotificationsEnabled(): bool
    {
        $tokens = $this->androidDeviceTokens();

        if (!$tokens || count($tokens) < 1) {
            return false;
        }

        return true;
    }

    public function androidDeviceTokens(): ?array
    {
        if ($this->android_device_tokens) {
            return json_decode($this->android_device_tokens);
        }

        return null;
    }

    public function addAndroidDeviceToken(string $token): void
    {
        $tokens = $this->iosDeviceTokens();

        if ($tokens && in_array($token, $tokens)) {
            return;
        }

        $tokens[] = $token;

        $this->android_device_tokens = json_encode($tokens);
        $this->save();
    }

    public function removeAndroidDeviceToken(string $token): void
    {
        $tokens = $this->iosDeviceTokens();

        $key = array_search($token, $tokens);

        if ($key !== false) {
            unset($tokens[$key]);
        }

        $this->android_device_tokens = json_encode($tokens);
        $this->save();
    }

    public function routeNotificationForApn()
    {
        return $this->iosDeviceTokens();
    }

    public function setIsRunning($status)
    {
        $this->is_running = $status;
        $this->save();
    }

    public function swaps(): HasMany
    {
        return $this->hasMany(Swap::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, "user_id", "id");
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, "user_id", "id");
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, "user_id", "id");
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(Sync::class);
    }
}
