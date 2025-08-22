<?php

namespace Yahlox\Rbac\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RequestMakeCommand extends Command
{
    protected $signature = 'rbac:request
                            {name : Request class name, e.g., UsersRequest}
                            {--permission= : Required permission string (default: "permission")}
                            {--target= : Route parameter for the target model (optional; omitting keeps it null)}
                            {--no-protect : Disable Super Admin target protection}
                            {--force : Overwrite if the file already exists}';

    protected $description = 'Generate a FormRequest extending BasePermissionRequest with minimal sensible defaults';

    public function handle(): int
    {
        $class     = Str::studly($this->argument('name'));
        $namespace = rtrim($this->laravel->getNamespace(), '\\') . '\\Http\\Requests';
        $dir       = app_path('Http/Requests');
        $path      = $dir . '/' . $class . '.php';

        if (is_file($path) && !$this->option('force')) {
            $this->error($path . ' already exists. Use --force to overwrite.');
            return self::FAILURE;
        }

        @mkdir($dir, 0777, true);

        $permission = (string) ($this->option('permission') ?? 'permission');
        $target     = $this->option('target'); // may be null
        $noProtect  = (bool) $this->option('no-protect');

        $targetParamLine = ($target !== null && $target !== '')
            ? "    protected ?string \$targetParam = '" . addslashes((string) $target) . "';\n"
            : '';

        $protectLine = $noProtect
            ? "    protected bool   \$protectTargetSuperAdmin = false;\n"
            : '';

        $contents = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Yahlox\\Rbac\\Http\\Requests\\BasePermissionRequest;

class {$class} extends BasePermissionRequest
{
    protected string \$requiredPermission = '{$permission}';
{$targetParamLine}{$protectLine}
    public function rules(): array
    {
        return [
            //
        ];
    }
}

PHP;

        file_put_contents($path, $contents);
        $this->info('Created: ' . $path);
        return self::SUCCESS;
    }
}
