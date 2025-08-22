<?php

namespace Yahlox\Rbac\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PasswordRequestMakeCommand extends Command
{
    protected $signature = 'rbac:password-request
                            {name : Request class name, e.g., DeletePostRequest}
                            {--permission= : Required permission string (default: "permission")}
                            {--role=* : Allowed role(s); repeatable (default: ["Role"])}
                            {--target=user : Route parameter for the target model (default: "user")}
                            {--password-field= : Override password input name (default: "password")}
                            {--guard= : Guard for current_password rule (defaults to auth.default)}
                            {--no-protect : Disable Super Admin protection}
                            {--force : Overwrite if the file already exists}';

    protected $description = 'Generate a FormRequest extending PasswordRequiredRequest with RBAC defaults';

    public function handle(): int
    {
        $class      = Str::studly($this->argument('name'));
        $namespace  = rtrim($this->laravel->getNamespace(), '\\') . '\\Http\\Requests';
        $dir        = app_path('Http/Requests');
        $path       = $dir . sprintf('/%s.php', $class);

        if (is_file($path) && !$this->option('force')) {
            $this->error($path . ' already exists. Use --force to overwrite.');
            return self::FAILURE;
        }

        @mkdir($dir, 0777, true);

        $permission = $this->option('permission') ?? 'permission';
        $roles      = $this->option('role') ?: ['Role'];
        implode(', ', array_map(fn ($r): string => "'" . addslashes((string) $r) . "'", $roles));
        $target     = $this->option('target') ?? 'user';
        $protect    = $this->option('no-protect') ? 'false' : 'true';

        $passwordField = $this->option('password-field');
        $guard         = $this->option('guard');

        // Optional lines only if options were provided
        $passwordFieldLine = $passwordField
            ? "    protected string \$passwordField = '" . addslashes($passwordField) . "';\n"
            : '';
        $passwordGuardLine = $guard !== null
            ? "    protected ?string \$passwordGuard = '" . addslashes($guard) . "';\n"
            : '';

        $contents = <<<PHP
<?php

namespace {$namespace};

use Yahlox\\Rbac\\Http\\Requests\\PasswordRequiredRequest;

class {$class} extends PasswordRequiredRequest
{
    protected string \$requiredPermission = '{$permission}';
    protected array  \$allowedRoles = [];
    protected string \$targetParam = '{$target}';
    protected bool   \$rolesBypassPermission = false;
    protected bool   \$protectTargetSuperAdmin = {$protect};
{$passwordFieldLine}{$passwordGuardLine}
    protected function payloadRules(): array
    {
        return [
            // e.g., 'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

PHP;

        file_put_contents($path, $contents);
        $this->info('Created: ' . $path);
        return self::SUCCESS;
    }
}
