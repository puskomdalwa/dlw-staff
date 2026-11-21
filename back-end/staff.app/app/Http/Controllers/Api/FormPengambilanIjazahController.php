<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncFormPengambilanIjazahToGoogleSheet;
use App\Jobs\UploudSuratToDrive;
use App\Models\FormPengambilanIjazah;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FormPengambilanIjazahController extends Controller
{
    /**
     * Menampilkan daftar data formulir pengambilan ijazah.
     */
    public function index(Request $request)
    {
        Log::info('FormPengambilanIjazah index request', $request->all());

        $data = FormPengambilanIjazah::query();

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

        $data = $data->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
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
            $validated['user_id'] = Auth::id();
            $validated['status'] = 'pending';

            $form = FormPengambilanIjazah::create($validated);

            // Generate dan simpan file PDF secara lokal
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
            $form->update($validated);

            // Re-generate file PDF
            $this->generateAndSavePdf($form->id);
            $form->refresh();

            // Re-upload ke Google Drive: folder BAAK -> Form Pengambilan Ijazah
            $nameTable = 'Form Pengambilan Ijazah';
            $prodiName = 'BAAK';
            UploudSuratToDrive::dispatch($form->id, $nameTable, $prodiName, FormPengambilanIjazah::class);

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
     * Download / preview file PDF formulir pengambilan ijazah.
     */
    public function downloadPdf($id)
    {
        try {
            $data = FormPengambilanIjazah::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/form_pengambilan_ijazah.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($data->local_path) && file_exists($data->local_path)) ? filemtime($data->local_path) : 0;

            // Generate ulang jika file belum ada atau jika template blade telah diedit
            if (empty($data->local_path) || !file_exists($data->local_path) || $viewMtime > $pdfMtime) {
                $this->generateAndSavePdf($data->id);
                $data->refresh();
            }

            if (empty($data->local_path) || !file_exists($data->local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->local_path);

            return response()->file($data->local_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error('Download PDF error: ' . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Generate file PDF dari blade template dan simpan ke folder server.
     */
    public static function generateAndSavePdf($id)
    {
        $data = FormPengambilanIjazah::find($id);

        if (!$data) {
            return null;
        }

        // Kop Surat Base64
        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        // Format tanggal sidang
        $tanggalSidangFormatted = !empty($data->tanggal_sidang)
            ? SuratService::formatTanggalIndonesian($data->tanggal_sidang)
            : '';

        // Format judul skripsi jika bahasa Arab (seperti di Surat Tugas dan Surat Penelitian)
        $rawJudul = trim($data->judul_skripsi ?? '');
        $cleanJudul = trim($rawJudul, '"\' ');
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $cleanJudul);
        if ($hasArabic && class_exists('\ArPHP\I18N\Arabic')) {
            $arabic = new \ArPHP\I18N\Arabic();
            $cleanJudul = preg_replace('/\s+/u', ' ', $cleanJudul);
            $glyphs = $arabic->utf8Glyphs($cleanJudul, 65);
            $formattedJudul = ($cleanJudul !== $rawJudul) ? '"' . $glyphs . '"' : $glyphs;
        } else {
            $formattedJudul = $rawJudul;
        }

        $pdfData = [
            'nim'              => $data->nim,
            'nama_mhs'         => $data->nama_mhs,
            'prodi'            => $data->prodi,
            'dosen_pembimbing' => $data->dosen_pembimbing,
            'judul_skripsi'    => $formattedJudul,
            'tanggal_sidang'   => $tanggalSidangFormatted,
            'no_hp'            => $data->no_hp,
            'kopBase64'        => $kopBase64,
        ];

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
