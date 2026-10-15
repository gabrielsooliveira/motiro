<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail pessoal para login.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe uma conta cadastrada com este e-mail. Por favor, acesse a opção "Já tenho uma conta" ou utilize a recuperação de senha.',
            'password.required' => 'Defina uma senha para sua conta.',
            'password.min' => 'A senha deve conter pelo menos 6 caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
            'terms.accepted' => 'É necessário concordar com os Termos de Uso e Política de Privacidade para continuar.',
        ];
    }
}
