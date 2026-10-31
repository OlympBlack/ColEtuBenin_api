<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametrePlateforme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParametrePlateformeController extends Controller
{
    /**
     * Lecture publique des paramètres de la plateforme
     */
    public function show(): JsonResponse
    {
        $settings = ParametrePlateforme::getSettings();

        return response()->json([
            'success' => true,
            'data' => $settings->toPublicArray(),
        ]);
    }

    /**
     * Mise à jour des paramètres (Admin uniquement)
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom_plateforme' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'file', 'image'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'campus_localisation' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email_contact' => ['nullable', 'email', 'max:255'],
            'email_support' => ['nullable', 'email', 'max:255'],
            'signature_auteur' => ['nullable', 'string', 'max:255'],
            'signature_url' => ['nullable', 'string', 'max:500'],
            'signature_texte' => ['nullable', 'string', 'max:255'],
            'copyright_texte' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = ParametrePlateforme::getSettings();

        // Gestion de l'upload du logo
        if ($request->hasFile('logo')) {
            // Si un ancien logo existe dans storage/logos ou storage/platform, on peut le supprimer s'il est personnalisé
            if ($settings->logo_url && !str_starts_with($settings->logo_url, '/') && !str_starts_with($settings->logo_url, 'http')) {
                Storage::disk('public')->delete($settings->logo_url);
            }
            $path = $request->file('logo')->store('platform', 'public');
            $settings->logo_url = $path;
        } elseif ($request->filled('logo_url')) {
            $settings->logo_url = $request->input('logo_url');
        }

        // Mise à jour des autres champs textuels
        $fields = [
            'nom_plateforme',
            'slogan',
            'description',
            'campus_localisation',
            'telephone',
            'whatsapp',
            'email_contact',
            'email_support',
            'signature_auteur',
            'signature_url',
            'signature_texte',
            'copyright_texte',
        ];

        foreach ($fields as $field) {
            if ($request->has($field) && $request->input($field) !== null) {
                $settings->{$field} = trim($request->input($field));
            }
        }

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Paramètres de la plateforme mis à jour avec succès.',
            'data' => $settings->toPublicArray(),
        ]);
    }

    /**
     * Rétablissement des paramètres par défaut
     */
    public function reset(): JsonResponse
    {
        $settings = ParametrePlateforme::getSettings();

        $settings->update([
            'nom_plateforme' => 'ColEtuBenin',
            'slogan' => 'La colocation pour les étudiants du Bénin',
            'description' => 'ColEtuBenin met en relation étudiants, bailleurs et démarcheurs autour des grandes universités publiques du Bénin : UAC, UNA, UNSTIM et UP. Trouvez ou proposez facilement une chambre ou une colocation près de votre campus.',
            'logo_url' => '/assets/images/logo_colcampus.png',
            'campus_localisation' => 'Campus UAC, UNA, UNSTIM & UP • Bénin',
            'telephone' => '+229 01 54 47 00 02',
            'whatsapp' => '+229 01 54 47 00 02',
            'email_contact' => 'contact@coletubenin.lavoixdabouloussi.org',
            'email_support' => 'contact@coletubenin.lavoixdabouloussi.org',
            'signature_auteur' => 'GBASSI Jules-christ',
            'signature_url' => 'https://juleschrist-portfolio.vercel.app/',
            'signature_texte' => 'Conçu & développé par',
            'copyright_texte' => 'ColEtuBenin. Tous droits réservés.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Les paramètres par défaut ont été restaurés.',
            'data' => $settings->toPublicArray(),
        ]);
    }
}
