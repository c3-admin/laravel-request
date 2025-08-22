<?php

namespace Yahlox\Rbac\Providers;

use Yahlox\Rbac\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;
use Yahlox\Rbac\Console\PasswordRequestMakeCommand;
use Yahlox\Rbac\Console\RequestMakeCommand;

class RBACServiceProvider extends ServiceProvider
{
    /** Absolute path to the package config file */
    protected function packageConfigPath(): string
    {
        // from src/Providers → up 2 dirs → /config/rbac.php
        return dirname(__DIR__, 2) . '/config/rbac.php';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->packageConfigPath(), 'rbac');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/rbac.php' => config_path('rbac.php'),
        ], 'yahlox-rbac-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                RequestMakeCommand::class,
                PasswordRequestMakeCommand::class,
            ]);
        }
    }
}


/** "yahlox/rbac": "dev-main" */