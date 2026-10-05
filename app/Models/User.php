<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'pemilik' => 'Pemilik',
        'admin' => 'Admin',
        'kepala_dapur' => 'Kepala dapur',
        'keuangan' => 'Keuangan',
        'kurir' => 'Kurir',
    ];

    /**
     * Hak akses per peran, sesuai matriks di halaman Pengguna & Akses.
     */
    public const PERMISSIONS = [
        'dashboard' => ['label' => 'Lihat dashboard', 'roles' => ['pemilik', 'admin', 'kepala_dapur', 'keuangan']],
        'orders' => ['label' => 'Kelola pesanan', 'roles' => ['pemilik', 'admin']],
        'production' => ['label' => 'Rekap produksi & stok', 'roles' => ['pemilik', 'admin', 'kepala_dapur']],
        'recipes' => ['label' => 'Ubah resep & HPP', 'roles' => ['pemilik', 'kepala_dapur']],
        'billing' => ['label' => 'Invoice & pembayaran', 'roles' => ['pemilik', 'keuangan']],
        'reports' => ['label' => 'Laporan keuangan', 'roles' => ['pemilik', 'keuangan']],
        'courier' => ['label' => 'Aplikasi kurir', 'roles' => ['pemilik', 'admin', 'kurir']],
        'users' => ['label' => 'Atur pengguna', 'roles' => ['pemilik']],
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(DeliveryRoute::class, 'courier_id');
    }

    public function canAccess(string $permission): bool
    {
        return in_array($this->role, self::PERMISSIONS[$permission]['roles'] ?? [], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }
}
