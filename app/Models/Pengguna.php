<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable implements CanResetPassword
{
    use HasFactory;

    protected $table = 'pengguna';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $hidden = ['kata_sandi', 'remember_token'];

    protected $fillable = [
        'nama',
        'surel',
        'kata_sandi',
        'peran',
        'aktif',
        'masuk_terakhir_pada',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'masuk_terakhir_pada' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['role_label'];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->nama;
    }

    /**
     * Get the user's role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return UserRole::tryFrom($this->peran)?->label() ?? $this->peran;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|UserRole $role): bool
    {
        return $this->peran === ($role instanceof UserRole ? $role->value : $role);
    }

    /**
     * Check if user is owner.
     */
    public function isOwner(): bool
    {
        return $this->peran === UserRole::OWNER->value;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->peran === UserRole::ADMIN->value;
    }

    /**
     * Check if user is staff.
     */
    public function isStaff(): bool
    {
        return UserRole::tryFrom($this->peran)?->isStaff() ?? false;
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdminPanel(): bool
    {
        return UserRole::tryFrom($this->peran)?->canAccessAdminPanel() ?? false;
    }

    /**
     * Check if user can manage catalog.
     */
    public function canManageCatalog(): bool
    {
        return UserRole::tryFrom($this->peran)?->canManageCatalog() ?? false;
    }

    /**
     * Check if user can manage settings.
     */
    public function canManageSettings(): bool
    {
        return UserRole::tryFrom($this->peran)?->canManageSettings() ?? false;
    }

    /**
     * Check if user can view reports.
     */
    public function canViewReports(): bool
    {
        return UserRole::tryFrom($this->peran)?->canViewReports() ?? false;
    }

    /**
     * Check if user can view activity logs.
     */
    public function canViewActivityLogs(): bool
    {
        return UserRole::tryFrom($this->peran)?->canViewActivityLogs() ?? false;
    }

    /**
     * Check if user can input manual order.
     */
    public function canInputManualOrder(): bool
    {
        return UserRole::tryFrom($this->peran)?->canInputManualOrder() ?? false;
    }

    /**
     * Check if user can verify payment.
     */
    public function canVerifyPayment(): bool
    {
        return UserRole::tryFrom($this->peran)?->canVerifyPayment() ?? false;
    }

    /**
     * Check if user can record payment.
     */
    public function canRecordPayment(): bool
    {
        return UserRole::tryFrom($this->peran)?->canRecordPayment() ?? false;
    }

    /**
     * Check if user can manage calendar blocks.
     */
    public function canManageCalendarBlocks(): bool
    {
        return UserRole::tryFrom($this->peran)?->canManageCalendarBlocks() ?? false;
    }

    /**
     * Get the user's role label for display.
     */
    public function getDisplayNameAttribute(): string
    {
        return match ($this->peran) {
            UserRole::OWNER->value => 'Owner '.$this->nama,
            UserRole::ADMIN->value => 'Admin '.$this->nama,
            UserRole::STAF_PRODUKSI->value => 'Staf Produksi '.$this->nama,
            UserRole::STAF_KURIR->value => 'Staf Kurir '.$this->nama,
            default => $this->nama,
        };
    }

    /**
     * Scope: hanya user aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope: hanya user dengan peran tertentu.
     */
    public function scopeWithRole($query, string|UserRole $role)
    {
        return $query->where('peran', $role instanceof UserRole ? $role->value : $role);
    }

    /**
     * Scope: user staf (produksi + kurir).
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('peran', [
            UserRole::STAF_PRODUKSI->value,
            UserRole::STAF_KURIR->value,
        ]);
    }

    /**
     * Scope: user owner + admin.
     */
    public function scopeOwnerAdmin($query)
    {
        return $query->whereIn('peran', [
            UserRole::OWNER->value,
            UserRole::ADMIN->value,
        ]);
    }

    /**
     * Get the identifier that will be stored in the cookie.
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the remember token.
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getAuthPassword()
    {
        return $this->kata_sandi;
    }

    /**
     * Get the identifier name for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'surel';
    }
}
