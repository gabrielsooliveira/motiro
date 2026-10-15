<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_name' => [$this->has('organization_name') ? 'required' : 'nullable', 'string', 'max:255'],
            'name' => [!$this->has('organization_name') ? 'required' : 'nullable', 'string', 'max:255'],
            'organization_type' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'organization_email' => ['nullable', 'email', 'max:255'],
            'institutional_email' => ['nullable', 'email', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_name.required' => 'Informe o nome da organização.',
            'name.required' => 'Informe o nome da organização.',
            'website.url' => 'Informe uma URL válida para o website (ex: https://exemplo.com).',
            'organization_email.email' => 'Informe um endereço de e-mail institucional válido.',
            'institutional_email.email' => 'Informe um endereço de e-mail institucional válido.',
            'email.email' => 'Informe um endereço de e-mail válido.',
        ];
    }

    /**
     * Helper to obtain the resolved organization name
     */
    public function resolvedOrganizationName(): string
    {
        return trim($this->input('organization_name') ?: $this->input('name', ''));
    }

    /**
     * Helper to obtain the resolved organization type
     */
    public function resolvedOrganizationType(): string
    {
        return trim($this->input('organization_type') ?: $this->input('type') ?: 'Empresa');
    }

    /**
     * Helper to obtain the resolved organization institutional contact email
     */
    public function resolvedInstitutionalEmail(): ?string
    {
        if ($this->filled('organization_email')) {
            return strtolower(trim($this->input('organization_email')));
        }

        if ($this->filled('institutional_email')) {
            return strtolower(trim($this->input('institutional_email')));
        }

        $user = $this->user();
        if ($this->filled('email') && (!$user || $this->input('email') !== $user->email)) {
            return strtolower(trim($this->input('email')));
        }

        return null;
    }
}
