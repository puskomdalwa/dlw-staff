<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const NEW_ORDER = [
        'kaprodi' => 1,
        'kkn' => 2,
        'keamanan' => 3,
        'baku_kabag' => 4,
        'baku_aupk' => 5,
        'idaroh_syuun' => 6,
    ];

    private const PREVIOUS_ORDER = [
        'kaprodi' => 1,
        'kkn' => 2,
        'keamanan' => 3,
        'baku' => 4,
        'idaroh_syuun' => 5,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Update step_order on existing signer defaults & signatures for shifted steps
        foreach (['kaprodi' => 1, 'kkn' => 2, 'keamanan' => 3, 'idaroh_syuun' => 6] as $key => $order) {
            DB::table('sk6_signer_defaults')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);

            DB::table('sk6_signatures')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);
        }

        // 2. Migrate legacy 'baku' default -> 'baku_aupk' and seed 'baku_kabag' default if needed
        $hasAupkDefault = DB::table('sk6_signer_defaults')->where('step_key', 'baku_aupk')->exists();
        $legacyBakuDefault = DB::table('sk6_signer_defaults')->where('step_key', 'baku')->first();

        if ($legacyBakuDefault) {
            if (!$hasAupkDefault) {
                $newTitle = $legacyBakuDefault->title === 'Kepala Biro Administrasi Keuangan'
                    ? 'Kepala Biro AUPK'
                    : $legacyBakuDefault->title;

                DB::table('sk6_signer_defaults')
                    ->where('id', $legacyBakuDefault->id)
                    ->update([
                        'step_key' => 'baku_aupk',
                        'step_order' => 5,
                        'title' => $newTitle,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('sk6_signer_defaults')->where('id', $legacyBakuDefault->id)->delete();
            }
        }

        if (DB::table('sk6_signer_defaults')->exists() && !DB::table('sk6_signer_defaults')->where('step_key', 'baku_kabag')->exists()) {
            DB::table('sk6_signer_defaults')->insert([
                'step_order' => 4,
                'step_key' => 'baku_kabag',
                'title' => 'Kabag BAKU',
                'signer_name' => 'Kabag BAKU',
                'signer_nidn' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Migrate legacy 'baku' signature rows -> 'baku_aupk' and insert missing 'baku_kabag' rows
        $kabagDefault = DB::table('sk6_signer_defaults')->where('step_key', 'baku_kabag')->first();
        $kabagTitle = $kabagDefault?->title ?: 'Kabag BAKU';
        $kabagSigner = $kabagDefault?->signer_name ?: 'Kabag BAKU';
        $kabagNidn = $kabagDefault?->signer_nidn;

        $sk6Ids = DB::table('sk6_signatures')->distinct()->pluck('surat_keterangan_6_id');
        foreach ($sk6Ids as $sk6Id) {
            $hasAupk = DB::table('sk6_signatures')
                ->where('surat_keterangan_6_id', $sk6Id)
                ->where('step_key', 'baku_aupk')
                ->exists();

            $legacyBaku = DB::table('sk6_signatures')
                ->where('surat_keterangan_6_id', $sk6Id)
                ->where('step_key', 'baku')
                ->first();

            if ($legacyBaku) {
                if (!$hasAupk) {
                    $newTitle = $legacyBaku->title === 'Kepala Biro Administrasi Keuangan'
                        ? 'Kepala Biro AUPK'
                        : $legacyBaku->title;

                    DB::table('sk6_signatures')
                        ->where('id', $legacyBaku->id)
                        ->update([
                            'step_key' => 'baku_aupk',
                            'step_order' => 5,
                            'title' => $newTitle,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('sk6_signatures')->where('id', $legacyBaku->id)->delete();
                }
            }

            $hasKabag = DB::table('sk6_signatures')
                ->where('surat_keterangan_6_id', $sk6Id)
                ->where('step_key', 'baku_kabag')
                ->exists();

            if (!$hasKabag) {
                DB::table('sk6_signatures')->insert([
                    'surat_keterangan_6_id' => $sk6Id,
                    'step_order' => 4,
                    'step_key' => 'baku_kabag',
                    'title' => $kabagTitle,
                    'signer_name' => $kabagSigner,
                    'signer_nidn' => $kabagNidn,
                    'status' => 'pending',
                    'signature_token' => Str::random(40),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = now();

        DB::table('sk6_signatures')->where('step_key', 'baku_kabag')->delete();
        DB::table('sk6_signer_defaults')->where('step_key', 'baku_kabag')->delete();

        DB::table('sk6_signer_defaults')
            ->where('step_key', 'baku_aupk')
            ->update([
                'step_key' => 'baku',
                'step_order' => self::PREVIOUS_ORDER['baku'],
                'updated_at' => $now,
            ]);

        DB::table('sk6_signatures')
            ->where('step_key', 'baku_aupk')
            ->update([
                'step_key' => 'baku',
                'step_order' => self::PREVIOUS_ORDER['baku'],
                'updated_at' => $now,
            ]);

        foreach (self::PREVIOUS_ORDER as $key => $order) {
            DB::table('sk6_signer_defaults')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);

            DB::table('sk6_signatures')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);
        }
    }
};
