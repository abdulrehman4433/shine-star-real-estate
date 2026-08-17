<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'agency_name',
        'agency_license_no',
        'agency_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'favorites')->withTimestamps();
    }

    public function toggleFavorite(Property $property): bool
    {
        $attached = $this->favorites()->toggle($property->id);

        return ! empty($attached['attached']);
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function conversations(): Builder
    {
        return Conversation::forUser($this->id);
    }

    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->whereIn('conversation_id', Conversation::forUser($this->id)->pluck('id'))
            ->where(fn ($q) => $q->whereNull('sender_id')->orWhere('sender_id', '!=', $this->id))
            ->whereNull('read_at')
            ->count();
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->hasRole('super-admin'), $this->hasRole('admin') => route('admin.dashboard'),
            $this->hasRole('agent'), $this->hasRole('agency') => route('agent.dashboard'),
            $this->hasRole('guest') => route('my.properties.index'),
            default => route('account.dashboard'),
        };
    }
}
