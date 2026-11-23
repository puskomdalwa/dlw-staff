<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\Prodi;
use App\Models\SuratKeteranganCuti;
use App\Services\GoogleDrive;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuratKeteranganCutiController extends Controller
{
    public function index(Request $request)
    {
        Log::info('SuratKeteranganCuti index:', $request->all());

        $data = SuratKeteranganCuti::query()
            ->leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_cuti.prodi_id')
            ->select(
                'surat_keterangan_cuti.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('surat_keterangan_cuti.nama_mhs', 'LIKE', "%{$search}%")
                  ->orWhere('surat_keterangan_cuti.nim', 'LIKE', "%{$search}%")
                  ->orWhere('surat_keterangan_cuti.dari_semester', 'LIKE', "%{$search}%")
                  ->orWhere('surat_keterangan_cuti.sampai_semester', 'LIKE', "%{$search}%")
                  ->orWhere('prodi.nama', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('prodi_id')) {
            $data->where('surat_keterangan_cuti.prodi_id', $request->prodi_id);
        }

        $login = Auth::user()->prodi;
        if ($login) {
            $data->where('surat_keterangan_cuti.prodi_id', $login->id);
        }

        $authGender = Auth::user()->jenis_kelamin;
        if ($authGender === 'L') {
            $data->where('surat_keterangan_cuti.jenis_kelamin', 'L');
        } elseif ($authGender === 'P') {
            $data->where('surat_keterangan_cuti.jenis_kelamin', 'P');
        }

        $data->orderBy(
            'surat_keterangan_cuti.' . $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $data = $data->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        Log::info('SuratKeteranganCuti store:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'prodi_id'        => 'required|exists:prodi,id',
                'nama_mhs'        => 'required|string|max:255',
                'nim'             => 'required|string|max:255',
                'dari_semester'   => 'required|string|max:50',
                'sampai_semester' => 'required|string|max:50',
                'jenis_kelamin'   => 'nullable|in:L,P',
                'alasan'          => 'nullable|string',
                'tanggal'         => 'nullable|date',
                'petanda_tangan'  => 'nullable|in:ya,tidak,stempel',
                'ketua'           => 'nullable|string|max:255',
                'nidn'            => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $skc = new SuratKeteranganCuti();
            $skc->prodi_id        = $validate['prodi_id'];
            $skc->nama_mhs        = $validate['nama_mhs'];
            $skc->nim             = $validate['nim'];
            $skc->dari_semester   = $validate['dari_semester'];
            $skc->sampai_semester = $validate['sampai_semester'];
            $skc->jenis_kelamin   = $validate['jenis_kelamin'] ?? Auth::user()->jenis_kelamin;
            $skc->alasan          = $validate['alasan'] ?? 'urusan finansial yang belum terpenuhi';
            $skc->tanggal         = $validate['tanggal'] ?? date('Y-m-d');
            $skc->petanda_tangan  = $validate['petanda_tangan'] ?? 'ya';
            $skc->user_id         = Auth::user()->id;
            $skc->status          = 'pending';

            $signer = SuratService::resolveSigner('SKC', [
                'prodi_id' => $validate['prodi_id'] ?? null,
                'ketua'    => $validate['ketua'] ?? null,
                'nidn'     => $validate['nidn'] ?? null,
            ]);
            $skc->ketua = $signer['ketua'];
            $skc->nidn  = $signer['nidn'];
            $skc->save();

            // Re-fetch with joins for PDF generation
            $data = $this->fetchJoinedData($skc->id);

            if ($data) {
                $pdfData = $this->buildPdfData($data);
                $prodiFolder = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($data->nama_prodi ?? 'UMUM');
                $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganCutiController');
                $pdf = Pdf::loadView('pdf.surat_keterangan_cuti', $pdfData)->setPaper('a4', 'portrait');
                $safeNim = SuratService::sanitizeFileSegment($data->nim ?? null, (string) $data->id);
                $fileName = 'surat_keterangan_cuti_' . $safeNim . '_' . uniqid() . '.pdf';

                $path = $directory . $fileName;
                $pdf->save($path);

                $skc->update(['local_path' => $path]);

                $nameTable = 'Surat Keterangan Cuti';
                UploudSuratToDrive::dispatch($skc->id, $nameTable, $data->nama_prodi ?: $prodiFolder, SuratKeteranganCuti::class);
            }

            return response()->json([
                'status'  => true,
                'data'    => $skc,
                'message' => 'Data berhasil ditambahkan'
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage(),
                'error'   => $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function show($id)
    {
        $skc = SuratKeteranganCuti::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_cuti.prodi_id')
            ->select(
                'surat_keterangan_cuti.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi'
            )
            ->where('surat_keterangan_cuti.id', $id)
            ->first();

        if (!$skc) {
            return response()->json([
                'status'  => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'data'    => $skc,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('SuratKeteranganCuti update:', $request->all());

        try {
            $skc = SuratKeteranganCuti::find($id);

            if (!$skc) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'prodi_id'        => 'required|exists:prodi,id',
                'nama_mhs'        => 'required|string|max:255',
                'nim'             => 'required|string|max:255',
                'dari_semester'   => 'required|string|max:50',
                'sampai_semester' => 'required|string|max:50',
                'jenis_kelamin'   => 'nullable|in:L,P',
                'alasan'          => 'nullable|string',
                'tanggal'         => 'nullable|date',
                'petanda_tangan'  => 'nullable|in:ya,tidak,stempel',
                'ketua'           => 'nullable|string|max:255',
                'nidn'            => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $oldDriveFileId = $skc->drive_file_id;
            $oldLocalPath   = $skc->local_path;
            $prodiChanged   = ((int) $skc->prodi_id !== (int) $validate['prodi_id']);

            $skc->prodi_id        = $validate['prodi_id'];
            $skc->nama_mhs        = $validate['nama_mhs'];
            $skc->nim             = $validate['nim'];
            $skc->dari_semester   = $validate['dari_semester'];
            $skc->sampai_semester = $validate['sampai_semester'];
            if (isset($validate['jenis_kelamin'])) {
                $skc->jenis_kelamin = $validate['jenis_kelamin'];
            }
            if (array_key_exists('alasan', $validate)) {
                $skc->alasan = $validate['alasan'] ?: 'urusan finansial yang belum terpenuhi';
            }
            if (!empty($validate['tanggal'])) {
                $skc->tanggal = $validate['tanggal'];
            }
            $skc->petanda_tangan = $validate['petanda_tangan'] ?? ($skc->petanda_tangan ?? 'ya');

            $inputKetua = $validate['ketua'] ?? null;
            $inputNidn  = $validate['nidn'] ?? null;
            if ($prodiChanged) {
                if ($inputKetua === $skc->ketua) $inputKetua = null;
                if ($inputNidn === $skc->nidn) $inputNidn = null;
            }

            $signer = SuratService::resolveSigner('SKC', [
                'prodi_id' => $validate['prodi_id'] ?? null,
                'ketua'    => $inputKetua,
                'nidn'     => $inputNidn,
            ]);
            $skc->ketua = $signer['ketua'];
            $skc->nidn  = $signer['nidn'];
            $skc->drive_file_id = null;
            $skc->drive_link    = null;
            $skc->status        = 'pending';
            $skc->save();

            // Hapus file lama di Drive & lokal jika ada
            if ($oldDriveFileId) {
                GoogleDrive::deleteFile($oldDriveFileId);
            }
            if ($oldLocalPath && File::exists($oldLocalPath)) {
                @File::delete($oldLocalPath);
            }

            // Regenerate PDF baru
            $data = $this->fetchJoinedData($skc->id);
            if ($data) {
                $pdfData = $this->buildPdfData($data);
                $prodiFolder = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($data->nama_prodi ?? 'UMUM');
                $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganCutiController');
                $pdf = Pdf::loadView('pdf.surat_keterangan_cuti', $pdfData)->setPaper('a4', 'portrait');
                $safeNim = SuratService::sanitizeFileSegment($data->nim ?? null, (string) $data->id);
                $fileName = 'surat_keterangan_cuti_' . $safeNim . '_' . uniqid() . '.pdf';

                $path = $directory . $fileName;
                $pdf->save($path);

                $skc->local_path = $path;
                $skc->save();

                $nameTable = 'Surat Keterangan Cuti';
                UploudSuratToDrive::dispatch($skc->id, $nameTable, $data->nama_prodi ?: $prodiFolder, SuratKeteranganCuti::class);
            }

            return response()->json([
                'status'  => true,
                'data'    => $skc,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $skc = SuratKeteranganCuti::find($id);

            if (!$skc) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($skc->drive_file_id) {
                GoogleDrive::deleteFile($skc->drive_file_id);
            }
            if ($skc->local_path && File::exists($skc->local_path)) {
                @File::delete($skc->local_path);
            }

            $skc->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal dihapus'
            ], 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $data = SuratKeteranganCuti::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            // Jika file belum ada di server atau tidak terbaca, generate ulang secara otomatis
            if (empty($data->local_path) || !file_exists($data->local_path) || !is_readable($data->local_path)) {
                $joined = $this->fetchJoinedData($data->id);
                if ($joined) {
                    $pdfData = $this->buildPdfData($joined);
                    $prodiFolder = $joined->nama_prodi ?? 'UMUM';
                    $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganCutiController');
                    $safeNim = SuratService::sanitizeFileSegment($joined->nim ?? null, (string) $joined->id);
                    $fileName = 'surat_keterangan_cuti_' . $safeNim . '_' . uniqid() . '.pdf';
                    $path = $directory . $fileName;
                    Pdf::loadView('pdf.surat_keterangan_cuti', $pdfData)->setPaper('a4', 'portrait')->save($path);
                    $data->update(['local_path' => $path]);
                }
            }

            if (empty($data->local_path) || !file_exists($data->local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->local_path);

            return response()->file($data->local_path, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF'], 500);
        }
    }

    private function fetchJoinedData($id)
    {
        return SuratKeteranganCuti::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_cuti.prodi_id')
            ->leftJoin('fakultas_prodi', 'fakultas_prodi.prodi_id', '=', 'prodi.id')
            ->leftJoin('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'fakultas.tanda_tangan_id')
            ->select(
                'surat_keterangan_cuti.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi',
                'prodi.nama_kepala as nama_kepala_prodi',
                'prodi.nidn_kepala as nidn_kepala_prodi',
                'fakultas.nama as fakultas',
                'fakultas.dekan as dekan',
                'fakultas.nidn_dekan as nidn_dekan',
                DB::raw('COALESCE(tanda_tangan.tdd, tanda_tangan.gambar) as ttd')
            )
            ->where('surat_keterangan_cuti.id', $id)
            ->first();
    }

    private function buildPdfData($data): array
    {
        $stempelBase64 = '';
        $tddBase64 = '';

        if (isset($data->petanda_tangan) && in_array($data->petanda_tangan, ['ya', 'stempel'])) {
            if ($data->petanda_tangan === 'ya' && !empty($data->ttd)) {
                if (str_starts_with($data->ttd, 'data:image')) {
                    $tddBase64 = $data->ttd;
                } else {
                    $tddPath = base_path('../public_html/' . $data->ttd);
                    if (file_exists($tddPath)) {
                        $tddBase64 = SuratService::getBase64Image($tddPath);
                    }
                }
            }

            $stempelPath = base_path('../public_html/img/stempel.png');
            if (file_exists($stempelPath)) {
                $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
            }
        }

        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        return [
            'prodi_id'          => $data->prodi_id,
            'nama_mhs'          => $data->nama_mhs,
            'nim'               => $data->nim,
            'nama_prodi'        => $data->nama_prodi,
            'alias_prodi'       => $data->alias_prodi,
            'dari_semester'     => $data->dari_semester,
            'sampai_semester'   => $data->sampai_semester,
            'alasan'            => $data->alasan,
            'tanggal_surat'     => SuratService::formatTanggalIndonesian($data->tanggal ?: date('Y-m-d')),
            'nama_kepala_prodi' => $data->nama_kepala_prodi,
            'nidn_kepala_prodi' => $data->nidn_kepala_prodi,
            'ketua'             => $data->ketua,
            'nidn'              => $data->nidn,
            'dekan'             => !empty($data->ketua) ? $data->ketua : $data->dekan,
            'nidn_dekan'        => !empty($data->nidn) ? $data->nidn : $data->nidn_dekan,
            'fakultas'          => $data->fakultas,
            'kopBase64'         => $kopBase64,
            'stempel'           => $stempelBase64,
            'petanda_tangan'    => $data->petanda_tangan ?? 'ya',
            'ttd'               => $tddBase64,
        ];
    }
}
