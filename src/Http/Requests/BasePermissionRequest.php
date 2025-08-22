<?php

declare(strict_types=1);

namespace Yahlox\Rbac\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BasePermissionRequest
 *
 * Permission-first authorization base for FormRequests.
 *
 * Evaluation order:
 *  0) (Optional) Global bypass for actors with the "Super Admin" role
 *     (role name from config: rbac.super_admin_role; default: "Super Admin").
 *  1) Permission check: if `$requiredPermission` is non-empty, the actor must have it.
 *  2) Optional role bypass: if `$rolesBypassPermission === true` and `$allowedRoles` is non-empty,
 *     having any of those roles allows access even without the permission.
 *  3) Target checks (only if `$targetParam` is set):
 *     - Target is REQUIRED (if missing or empty, deny).
 *     - If target has the Super Admin role, deny unless actor is also Super Admin.
 *
 * Extend this class and override the protected properties as needed.
 */
abstract class BasePermissionRequest extends FormRequest
{
    /**
     * Permission the actor must have to pass (leave empty '' to skip permission check).
     *
     * @var non-empty-string|string
     */
    protected string $requiredPermission = '';

    /**
     * Roles that may allow access when `$rolesBypassPermission === true`.
     * Empty array means the role bypass is effectively disabled.
     *
     * @var list<non-empty-string>
     */
    protected array $allowedRoles = [];

    /**
     * When true, any role listed in `$allowedRoles` can bypass the missing permission.
     * Default is false (strict permission-first behavior).
     */
    protected bool $rolesBypassPermission = false;

    /**
     * Name of the route parameter that holds the target model/value.
     * When non-null, the target is REQUIRED (null/empty => deny).
     * Set to null when the page does not rely on a bound model (default).
     */
    protected ?string $targetParam = null;

    /**
     * If true, block actions on a target that has the Super Admin role unless
     * the actor also has that role. (Role name from config `rbac.super_admin_role`.)
     */
    protected bool $protectTargetSuperAdmin = true;

    /**
     * If true, any actor with the Super Admin role automatically passes authorization.
     * Disable in child requests by setting `$superAdminBypass = false` when needed.
     */
    protected bool $superAdminBypass = true;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if authorized; false otherwise.
     */
    public function authorize(): bool
    {
        $actor = $this->user();
        if (!$actor) {
            return false;
        }

        $superAdmin = (string) config('rbac.super_admin_role', 'Super Admin');

        if ($this->superAdminBypass && method_exists($actor, 'hasRole') && $actor->hasRole($superAdmin)) {
            return true;
        }

        $hasPermission = ($this->requiredPermission === '') || $actor->can($this->requiredPermission);

        $hasAllowedRole = $this->allowedRoles !== [] && method_exists($actor, 'hasAnyRole')
            ? $actor->hasAnyRole($this->allowedRoles)
            : false;

        if (!$hasPermission && !($this->rolesBypassPermission && $hasAllowedRole)) {
            return false;
        }

        if ($this->targetParam !== null) {
            $target = $this->route($this->targetParam);

            if ($target === null || $target === '') {
                return false;
            }

            if (
                $this->protectTargetSuperAdmin
                && method_exists($target, 'hasRole')
                && $target->hasRole($superAdmin)
                && (!method_exists($actor, 'hasRole') || !$actor->hasRole($superAdmin))
            ) {
                return false;
            }
        }

        return true;
    }
}
