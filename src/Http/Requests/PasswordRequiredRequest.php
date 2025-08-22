<?php

namespace Yahlox\Rbac\Http\Requests;

abstract class PasswordRequiredRequest extends BasePermissionRequest
{
    protected string $passwordField = 'password';
    
    protected ?string $passwordGuard = null;

    public function rules(): array
    {
        return [
            $this->passwordField => $this->currentPasswordRules(),
        ] + $this->payloadRules();
    }

    abstract protected function payloadRules(): array;

    protected function currentPasswordRules(): array
    {
        $guard = $this->passwordGuard
            ?? config('rbac.default_guard')
            ?? config('auth.defaults.guard');

        return ['required', 'string', 'current_password:' . $guard];
    }
}
