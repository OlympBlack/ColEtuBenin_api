<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionCredit;
use App\Models\AchatForfait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ControleurPortefeuille extends Controller
{
    /**
     * Voir l'état du portefeuille et l'historique
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $service = new \App\Services\ServicePortefeuillePublications();
        $resume = $service->obtenirResumePortefeuille($user);

        $transactions = TransactionCredit::where('user_id', $user->id)
            ->latest()
            ->take(30)
            ->get();

        $achats = AchatForfait::with('forfait')
            ->where('user_id', $user->id)
            ->latest()
            ->take(15)
            ->get();

        return response()->json([
            'portefeuille' => $resume,
            'transactions' => $transactions,
            'achats' => $achats,
        ]);
    }
}
