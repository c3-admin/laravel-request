<?php

namespace Yahlox\Rbac\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Contracts\Permission as PermissionContract;

final class RoleBuilder
{
    private ?string $guard = null;
    
    private array $permissions = [];
    
    private bool $sync = false;

    private function __construct(private readonly string $name) {}

    public static function create(string $name): self   { return new self($name); }

    public static function named(string $name): self    { return new self($name); }

    public function guard(?string $guardName): self     { $this->guard = $guardName; return $this; }
    
    public function permission(string $name): self      { $this->permissions[] = $name; return $this; }
    
    public function permissions(array $names): self     { foreach ($names as $name) $this->permission($name); return $this; }
    
    public function sync(bool $state = true): self      { $this->sync = $state; return $this; }

    public function withGuard(?string $guardName): self { return $this->guard($guardName); }
    
    public function withPermission(string $name): self  { return $this->permission($name); }
    
    public function withPermissions(array $names): self { return $this->permissions($names); }
    
    public function strict(bool $state = true): self    { return $this->sync($state); }

    public function build(): Role
    {
        return DB::transaction(function (): Role {
            $guard = $this->guard
                ?? config('rbac.default_guard')
                ?? config('auth.defaults.guard');

            $role = Role::findOrCreate($this->name, $guard);

            $perms = collect($this->permissions)
                ->filter()
                ->unique()
                ->map(fn ($p): PermissionContract => Permission::findOrCreate($p, $guard))
                ->all();

            $this->sync
                ? $role->syncPermissions($perms)
                : $role->givePermissionTo($perms);

            return $role;
        });
    }
}
