<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'permissions',
        'role',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Check if user has permission
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    // Grant permission
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    // Revoke permission
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);
    }

    // Get role label
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'owner' => 'Proprietar',
            'admin' => 'Administrator',
            'member' => 'Membru',
            'viewer' => 'Vizualizator',
            default => 'Necunoscut',
        };
    }

    // Available permissions
    public static function availablePermissions(): array
    {
        return [
            'contracts.view' => 'Vizualizare contracte',
            'contracts.create' => 'Creare contracte',
            'contracts.edit' => 'Editare contracte',
            'contracts.delete' => 'Ștergere contracte',
            'contracts.sign' => 'Semnare contracte',
            'invoices.view' => 'Vizualizare facturi',
            'invoices.create' => 'Creare facturi',
            'invoices.edit' => 'Editare facturi',
            'invoices.delete' => 'Ștergere facturi',
            'reports.view' => 'Vizualizare rapoarte',
            'reports.generate' => 'Generare rapoarte',
            'company.manage' => 'Administrare companie',
            'users.manage' => 'Administrare utilizatori',
        ];
    }
}
