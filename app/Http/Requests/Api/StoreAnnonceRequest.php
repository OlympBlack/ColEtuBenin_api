<?php

namespace App\Http\Requests\Api;

use App\Models\Universite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'universite_id' => ['nullable', 'integer', 'exists:universites,id'],
            'universite' => ['nullable', 'string', 'max:20', 'exists:universites,code'],
            'quartier' => ['required', 'string', 'max:120', Rule::exists('quartiers', 'nom')],
            'loyer' => ['required', 'integer', 'min:0'],
            'caution' => ['required', 'numeric', 'min:0'],
            'source_eau' => ['required', 'in:SONEB,Forage'],
            'type_electricite' => ['required', 'in:SBEE_CARTE,SBEE_CONVENTIONNEL'],
            'lien_google_maps' => ['nullable', 'url'],
            'type_annonce' => ['required', 'in:CHAMBRE_VIDE,PLACE_LIBRE_HOTE,RECHERCHE_BINOME'],
            'type_chambre' => ['nullable', 'string', 'max:50'],
            'nombre_places' => ['nullable', 'integer', 'min:1', 'max:50'],
            'statut_disponibilite' => ['nullable', 'in:available,taken,pending'],
            'medias' => ['nullable', 'array', 'max:7'],
            'medias.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:51200'],
            'media_order' => ['nullable', 'array', 'max:10'],
            'media_order.*' => ['string'],
            'media_order_empty' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->input('universite_id') && !$this->input('universite')) {
                $validator->errors()->add('universite', 'Indiquez l\'université (code ou identifiant).');

                return;
            }

            $universite = $this->resolveUniversite();
            if ($universite && $quartier = $this->input('quartier')) {
                $existe = $universite->quartiers()->where('nom', $quartier)->exists();
                if (!$existe) {
                    $validator->errors()->add('quartier', 'Ce quartier n\'appartient pas à l\'université indiquée.');
                }
            }

            if ($this->input('type_annonce') === 'RECHERCHE_BINOME' && $this->hasFile('medias')) {
                foreach ($this->file('medias', []) as $file) {
                    if (str_ends_with(strtolower($file->getClientOriginalName() ?? ''), '.mp4')) {
                        $validator->errors()->add('medias', 'Une recherche de binôme ne peut pas contenir de vidéo.');
                        break;
                    }
                }
            }
        });
    }

    public function resolveUniversite(): ?Universite
    {
        if ($id = $this->input('universite_id')) {
            return Universite::find($id);
        }

        if ($code = $this->input('universite')) {
            return Universite::where('code', strtoupper($code))->first();
        }

        return null;
    }

    public function universiteId(): ?int
    {
        return $this->resolveUniversite()?->id;
    }
}
