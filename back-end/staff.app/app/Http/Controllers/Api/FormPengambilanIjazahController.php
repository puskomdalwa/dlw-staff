<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncFormPengambilanIjazahToGoogleSheet;
use App\Jobs\UploudSuratToDrive;
use App\Models\FormPengambilanIjazah;
use App\Services\PengambilanIjazahGenderFixService;
use App\Services\PengambilanIjazahSignatureService;
use App\Services\SignatureStepFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FormPengambilanIjazahController extends Controller
{
    public function __construct(private PengambilanIjazahSignatureService $signatureService)
    {
    }

    /**
     * Menampilkan daftar data formulir pengambilan ijazah.
     */
    public function index(Request $request)
    {
        Log::info('FormPengambilanIjazah index request', $request->all());

        $data = FormPengambilanIjazah::with('signatures');

        try {
            $explicitGender = SignatureStepFilter::resolveGenderFilter($request);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($explicitGender !== null) {
            $data->where('jenis_kelamin', $explicitGender);
        } else {
            $authGender = Auth::user()?->jenis_kelamin;
            if (in_array($authGender, ['L', 'P'], true)) {
                $data->where(function ($q) use ($authGender) {
                    $q->where('jenis_kelamin', $authGender)
                        ->orWhereNull('jenis_kelamin');
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->where('nama_mhs', 'LIKE', "%{$search}%")
                    ->orWhere('nim', 'LIKE', "%{$search}%")
                    ->orWhere('prodi', 'LIKE', "%{$search}%")
                    ->orWhere('dosen_pembimbing', 'LIKE', "%{$search}%")
                    ->orWhere('judul_skripsi', 'LIKE', "%{$search}%")
                    ->orWhere('tanggal_sidang', 'LIKE', "%{$search}%")
                    ->orWhere('no_hp', 'LIKE', "%{$search}%");
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $paginated = $data->paginate($request->input('limit', 10));
        $paginated->getCollection()->transform(function (FormPengambilanIjazah $item) {
            $steps = $this->signatureService->formatTimeline($item, false);
            $signedCount = collect($steps)->where('status', 'approved')->count();
            // Clear Eloquent relation cache so JSON serializes the formatted timeline array instead of raw models
            $item->unsetRelation('signatures');
            $item->setAttribute('signed_count', $signedCount);
            $item->setAttribute('total_steps', count($steps));
            $item->setAttribute('is_completed', $signedCount === count($steps));
            $item->setAttribute('signatures', $steps);
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $paginated,
            'message' => 'Data berhasil diambil'
        ]);
    }

    /**
     * Menyimpan data formulir baru, generate PDF, dan upload ke Google Drive.
     */
    public function store(Request $request)
    {
        Log::info('FormPengambilanIjazah store request', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'nama_mhs'          => 'required|string|max:255',
                'nim'               => 'nullable|string|max:50',
                'prodi'             => 'required|string|max:255',
                'jenis_kelamin'     => 'nullable|in:L,P',
                'dosen_pembimbing'  => 'required|string|max:255',
                'judul_skripsi'     => 'required|string',
                'tanggal_sidang'    => 'required|date',
                'no_hp'             => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['jenis_kelamin'] = $validated['jenis_kelamin'] ?? Auth::user()?->jenis_kelamin;
            $validated['user_id'] = Auth::id();
            $validated['status'] = 'pending';

            $form = FormPengambilanIjazah::create($validated);

            // Inisialisasi 4 step timeline TTD QR
            $this->signatureService->ensureStepsForForm($form);

            // Generate dan simpan file PDF (Tanpa TTD) secara lokal
            $this->generateAndSavePdf($form->id);
            $form->refresh();

            // Dispatch job untuk upload ke Google Drive: folder BAAK -> Form Pengambilan Ijazah
            $nameTable = 'Form Pengambilan Ijazah';
            $prodiName = 'BAAK';
            UploudSuratToDrive::dispatch($form->id, $nameTable, $prodiName, FormPengambilanIjazah::class);

            // Sync ke Google Spreadsheet secara langsung agar data langsung tersimpan tanpa harus menunggu queue worker
            try {
                SyncFormPengambilanIjazahToGoogleSheet::dispatchSync($form->id, 'store');
            } catch (\Throwable $sheetEx) {
                Log::error("Google Sheet sync error on store: " . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $form,
                'message' => 'Data berhasil ditambahkan dan PDF berhasil digenerate'
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Error store FormPengambilanIjazah: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail data formulir berdasarkan ID.
     */
    public function show($id)
    {
        $form = FormPengambilanIjazah::find($id);

        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $form,
            'message' => 'Data berhasil diambil'
        ]);
    }

    /**
     * Memperbarui data formulir berdasarkan ID, regenerate PDF, dan re-upload ke Google Drive.
     */
    public function update(Request $request, $id)
    {
        Log::info("FormPengambilanIjazah update request (id: {$id})", $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'nama_mhs'          => 'required|string|max:255',
                'nim'               => 'nullable|string|max:50',
                'prodi'             => 'required|string|max:255',
                'jenis_kelamin'     => 'nullable|in:L,P',
                'dosen_pembimbing'  => 'required|string|max:255',
                'judul_skripsi'     => 'required|string',
                'tanggal_sidang'    => 'required|date',
                'no_hp'             => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $form = FormPengambilanIjazah::find($id);

            if (!$form) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validated = $validator->validated();
            if (empty($validated['jenis_kelamin'])) {
                $validated['jenis_kelamin'] = $form->jenis_kelamin ?? Auth::user()?->jenis_kelamin;
            }
            $form->update($validated);
            $this->signatureService->ensureStepsForForm($form);

            // Re-generate file PDF (Tanpa TTD)
            $this->generateAndSavePdf($form->id);
            $form->refresh();

            // Re-upload ke Google Drive (Tanpa TTD): folder BAAK -> Form Pengambilan Ijazah
            $nameTable = 'Form Pengambilan Ijazah';
            $prodiName = 'BAAK';
            UploudSuratToDrive::dispatch($form->id, $nameTable, $prodiName, FormPengambilanIjazah::class);

            // Jika seluruh 4/4 TTD QR sudah lengkap, regenerate PDF Ber-TTD & antrekan upload Drive versi Ber-TTD
            if ($this->signatureService->isCompleted($form)) {
                $this->signatureService->regenerateSignedPdf($form);
                $this->signatureService->queueCompleteDriveUpload($form->fresh());
            }

            // Dispatch update sync ke Google Spreadsheet secara langsung
            try {
                SyncFormPengambilanIjazahToGoogleSheet::dispatchSync($form->id, 'update');
            } catch (\Throwable $sheetEx) {
                Log::error("Google Sheet sync error on update: " . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $form,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error update FormPengambilanIjazah: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus data formulir beserta file PDF lokalnya.
     */
    public function destroy($id)
    {
        try {
            $form = FormPengambilanIjazah::find($id);

            if (!$form) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if (!empty($form->local_path) && file_exists($form->local_path)) {
                try {
                    unlink($form->local_path);
                } catch (\Throwable $e) {
                    Log::warning("Gagal hapus file lokal {$form->local_path}: " . $e->getMessage());
                }
            }

            if (!empty($form->signed_local_path) && file_exists($form->signed_local_path)) {
                try {
                    unlink($form->signed_local_path);
                } catch (\Throwable $e) {
                    Log::warning("Gagal hapus file signed lokal {$form->signed_local_path}: " . $e->getMessage());
                }
            }

            $this->signatureService->clearOldSignedDriveFile($form, keepLocalFile: true);

            $form->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error destroy FormPengambilanIjazah: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Data gagal dihapus: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Download / preview file PDF formulir pengambilan ijazah (khusus jika seluruh 4/4 TTD QR lengkap).
     */
    public function downloadPdf($id)
    {
        try {
            $data = FormPengambilanIjazah::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            if (!$this->signatureService->isCompleted($data)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanda tangan QR belum lengkap (wajib 4/4 terkonfirmasi). Silakan lengkapi di menu Timeline TTD.',
                ], 422);
            }

            $viewPath = resource_path('views/pdf/form_pengambilan_ijazah.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($data->signed_local_path) && file_exists($data->signed_local_path))
                ? filemtime($data->signed_local_path)
                : 0;
            $latestSignedAt = $data->signatures()->max('signed_at');
            $latestSignedTs = $latestSignedAt ? strtotime((string) $latestSignedAt) : 0;
            $latestUpdatedAt = $data->signatures()->max('updated_at');
            $latestUpdatedTs = $latestUpdatedAt ? strtotime((string) $latestUpdatedAt) : 0;

            if (empty($data->signed_local_path) || !file_exists($data->signed_local_path) || $viewMtime > $pdfMtime || $latestSignedTs > $pdfMtime || $latestUpdatedTs > $pdfMtime) {
                $this->signatureService->regenerateSignedPdf($data);
                $data->refresh();
            }

            if (empty($data->signed_local_path) || !file_exists($data->signed_local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF bertanda tangan tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->signed_local_path);

            return response()->file($data->signed_local_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error('Download PDF error: ' . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Batch backfill kolom jenis_kelamin (Putra/Putri) yang masih kosong secara bertahap (anti-timeout).
     */
    public function fixGenderBatch(Request $request, PengambilanIjazahGenderFixService $genderFixService)
    {
        @set_time_limit(120);

        $validator = Validator::make($request->all(), [
            'after_id' => 'nullable|integer|min:0',
            'batch_size' => 'nullable|integer|min:1|max:25',
            'include_simkeu' => 'nullable|boolean',
            'overwrite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi parameter batch gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $genderFixService->processBatch(
            (int) $request->input('after_id', 0),
            (int) $request->input('batch_size', 5),
            $request->boolean('include_simkeu', true),
            $request->boolean('overwrite', false)
        );

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => 'Batch perbaikan Putra/Putri selesai diproses',
        ]);
    }

    /**
     * Generate file PDF (Tanpa TTD QR) dari blade template dan simpan ke local_path server.
     */
    public static function generateAndSavePdf($id)
    {
        $data = FormPengambilanIjazah::find($id);

        if (!$data) {
            return null;
        }

        $pdfData = app(PengambilanIjazahSignatureService::class)->buildPdfData($data, false);

        // Hapus file lama jika ada sebelum generate yang baru
        if (!empty($data->local_path) && file_exists($data->local_path)) {
            @unlink($data->local_path);
        }

        // Folder penyimpanan di server: public_html/pdf/BAAK/Form Pengambilan Ijazah/
        $directory = base_path('../public_html/pdf/BAAK/Form Pengambilan Ijazah/');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = 'form_bebas_tanggungan_' . ($data->nim ?: $data->id) . '_' . uniqid() . '.pdf';
        $path = $directory . $fileName;

        $pdf = Pdf::loadView('pdf.form_pengambilan_ijazah', $pdfData)->setPaper('a4', 'portrait');
        $pdf->save($path);

        $data->update(['local_path' => $path]);

        return $path;
    }
}
