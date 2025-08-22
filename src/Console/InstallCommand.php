<?php

namespace Yahlox\Rbac\Console;

use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Spatie\Permission\PermissionRegistrar;
use Yahlox\Rbac\Support\RoleBuilder;

class InstallCommand extends Command
{
    protected $signature = 'rbac:install
                            {--guard= : Guard name to use}
                            {--force : Overwrite existing files when publishing}';

    protected $description = 'Publish Spatie Permission + RBAC config, run migrations, seed Super Admin.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Publishing Spatie Permission assets...');
        $this->call('vendor:publish', array_filter([
            '--provider' => PermissionServiceProvider::class,
            '--tag'      => 'permission-config',
            '--force'    => $force,
        ]));
        $this->call('vendor:publish', array_filter([
            '--provider' => PermissionServiceProvider::class,
            '--tag'      => 'permission-migrations',
            '--force'    => $force,
        ]));

        $this->info('Publishing Yahlox RBAC config...');
        $this->call('vendor:publish', array_filter([
            '--tag'   => 'yahlox-rbac-config',
            '--force' => $force,
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Running migrations...');
        try {
            $this->call('migrate', ['--force' => true]);
        } catch (QueryException $queryException) {
            $this->error('Migration failed: '.$queryException->getMessage());
            $this->warn('Check your DB connection and run: php artisan migrate');
            return self::FAILURE;
        }

        $guard = $this->option('guard') ?? config('auth.defaults.guard');
        $roleName = (string) config('rbac.super_admin_role', 'Super Admin');
        RoleBuilder::create($roleName)
            ->withGuard($guard)
            ->build();

        $this->info('RBAC installed successfully. Guard: ' . $guard);
        $this->line('You can now use middleware like: permission:view users / role:Super Admin');
        return self::SUCCESS;
    }
}
