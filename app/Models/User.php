<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'access_level',
        'zone',
        'center',
        'must_change_password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // ── Access level helpers ──────────────────────────────────────────────────

    public function isCityWide(): bool
    {
        return $this->access_level === 'city_wide';
    }

    public function isZoneLevel(): bool
    {
        return $this->access_level === 'zone';
    }

    public function isCenterLevel(): bool
    {
        return $this->access_level === 'center';
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isExecutiveBoard(): bool
    {
        return $this->role === 'executive_board';
    }

    public function isZoneDirector(): bool
    {
        return $this->role === 'zone_director';
    }

    public function isAssociateDirector(): bool
    {
        return $this->role === 'associate_director';
    }

    // ── Display label ─────────────────────────────────────────────────────────

    public function roleLabel(): string
    {
        if ($this->isExecutiveBoard()) {
            return 'Executive Board';
        }

        if ($this->isZoneDirector()) {
            return ($this->zone ?? '') . ' Zone Director';
        }

        if ($this->isAssociateDirector()) {
            return 'Associate Director' . ($this->center ? ' – ' . $this->center : '');
        }

        return ucwords(str_replace('_', ' ', $this->role ?? ''));
    }
}
