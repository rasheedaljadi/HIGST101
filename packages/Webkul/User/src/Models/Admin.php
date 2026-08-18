<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Database\Factories\AdminFactory;

class Admin extends Authenticatable implements AdminContract
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'api_token',
        'role_id',
        'status',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_backup_codes',
        'two_factor_verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'two_factor_backup_codes' => 'array',
        'two_factor_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    /**
     * Get image url for the product image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the product image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the role that owns the admin.
     *
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * Checks if admin has permission to perform certain action.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (
            $this->role->permission_type == 'custom'
            && ! $this->role->permissions
        ) {
            return false;
        }

        $permissions = $this->role->permissions;
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        if (in_array($permission, $permissions)) {
            return true;
        }

        // Support dot notation hierarchy (e.g. 'delivery' grants 'delivery.assigned', etc.)
        foreach ($permissions as $p) {
            if (str_starts_with($permission, $p.'.')) {
                return true;
            }
        }

        if (
            in_array($this->role?->name, ['Courier', 'PointAgent'])
            && ($permission === 'delivery' || str_starts_with($permission, 'delivery.') || str_starts_with($permission, 'delivery_agent'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AdminFactory::new();
    }
}
