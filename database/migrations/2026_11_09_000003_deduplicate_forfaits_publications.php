<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $forfaits = DB::table('forfaits_publications')
            ->orderBy('id', 'asc')
            ->get();

        $seen = [];
        $duplicateIds = [];

        foreach ($forfaits as $forfait) {
            $key = trim(mb_strtolower($forfait->titre));
            if (isset($seen[$key])) {
                $duplicateIds[] = $forfait->id;
            } else {
                $seen[$key] = $forfait->id;
            }
        }

        if (!empty($duplicateIds)) {
            DB::table('forfaits_publications')
                ->whereIn('id', $duplicateIds)
                ->update(['est_actif' => false]);
        }
    }

    public function down(): void
    {
        //
    }
};
