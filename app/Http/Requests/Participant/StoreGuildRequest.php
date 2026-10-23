<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'area_of_focus' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
            'logo' => ['nullable'],
            'invite_emails' => ['nullable', 'string', 'max:1000'],
            'set_as_leader' => ['nullable', 'boolean'],
            'leader_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da guild é obrigatório.',
            'name.max' => 'O nome da guild não pode ultrapassar 255 caracteres.',
            'description.max' => 'A descrição não pode ultrapassar 2000 caracteres.',
            'image.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'image.mimes' => 'A imagem deve estar nos formatos JPEG, PNG, JPG, WEBP ou GIF.',
            'image.max' => 'A imagem não pode ultrapassar 2MB.',
        ];
    }
}
