<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePubliciteBoutiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre_promo' => ['required', 'string', 'max:255'],
            'prix' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'lien_action' => ['nullable', 'string', 'max:1000'],
            'quartier_cible' => ['nullable', 'string', 'max:120', Rule::exists('quartiers', 'nom')],
            'banniere' => ['required', 'image', 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
