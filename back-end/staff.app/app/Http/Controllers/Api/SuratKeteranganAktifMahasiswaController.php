<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\LogSurat;
use App\Models\NoSurat;
use App\Models\Prodi;
use App\Models\SuratKeteranganAktifMahasiswa;
use App\Models\ThAkademik;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SuratKeteranganAktifMahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $data = SuratKeteranganAktifMahasiswa::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_aktif_mahasiswa.prodi_id');

        $data->select(
            'surat_keterangan_aktif_mahasiswa.*',
            'prodi.nama as nama_prodi',
            'prodi.alias as alias_prodi',
            'prodi.nama_kepala as nama_kepala_prodi'
        );

        if ($request->filled("prodi_id")) {
            $data->where('surat_keterangan_aktif_mahasiswa.prodi_id', $request->prodi_id);
        }

        $login = Auth::user()->prodi;
        if ($login) {
            $data->where('surat_keterangan_aktif_mahasiswa.prodi_id', $login->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('tahun_akademik', 'like', "%{$search}%");
            });
        }

        $auth = Auth::user()->jenis_kelamin;
        if ($auth == 'L') {
            $data->where('surat_keterangan_aktif_mahasiswa.jenis_kelamin', 'L');
        } else {
            $data->where('surat_keterangan_aktif_mahasiswa.jenis_kelamin', 'P');
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $data = $data->paginate($request->input('limit', 10));

        return response()->json(
            [
                'status' => true,
                'data' => $data,
                'message' => 'Data berhasil diambil'
            ]
        );
    }

    public function store(Request $request)
    {
        Log::info($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'prodi_id' => 'required|exists:prodi,id',
                'no_surat' => 'required|string|max:255|unique:nomor,nomor',
                'nama_mhs' => 'required|string|max:255',
                'nim' => 'required|string|max:255',

                'tempat_lahir' => 'required|string|max:100',
                'tanggal_lahir' => 'required|date',
                'prodi_mhs' => 'required|string|max:255',
                'semester' => 'required|string|max:50',
                'tahun_akademik' => 'required|string|max:100',
                'nama_ortu' => 'required|string|max:255',

                'alamat_ortu' => 'required|string',
                'hp_ortu' => 'nullable|string|max:50',
                'tanggal' => 'required|date',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
            ], [
                'no_surat.unique' => 'Nomor surat sudah terpakai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $no_surat = $validate['no_surat'];

            $noSurat = SuratService::formatNomorSurat('SKAM', $no_surat ?? '', $validate['tanggal'], $validate['prodi_id']);

            $skam = new SuratKeteranganAktifMahasiswa();
            $skam->nomor_surat = $noSurat;
            $skam->prodi_id = $validate['prodi_id'];
            $skam->th_akademik_id = null;
            $skam->nama_lengkap = $validate['nama_mhs'];
            $skam->nim = $validate['nim'];
            $skam->nik = null;
            $skam->tempat_lahir = $validate['tempat_lahir'];
            $skam->tanggal_lahir = $validate['tanggal_lahir'];
            $skam->prodi_mhs = $validate['prodi_mhs'];
            $skam->semester = $validate['semester'];
            $skam->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $skam->tahun_akademik = $validate['tahun_akademik'];
            $skam->nama_ortu = $validate['nama_ortu'];
            $skam->nik_ortu = null;
            $skam->nip_ortu = null;
            $skam->alamat_ortu = $validate['alamat_ortu'];
            $skam->hp_ortu = $validate['hp_ortu'] ?? null;
            $skam->tanggal = $validate['tanggal'];
            $skam->user_id = Auth::user()->id;
            $skam->jenis_kelamin  = Auth::user()->jenis_kelamin;
            $skam->status         = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('SKAM', [
                'prodi_id' => $validate['prodi_id'] ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? null,
                'ketua' => $validate['ketua'] ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? null),
            ]);
            $skam->ketua = $signer['ketua'];
            $skam->nidn = $signer['nidn'];
            $skam->save();

            $Nomor              = new NoSurat();
            $Nomor->nomor       = $no_surat;
            $Nomor->user_id     = Auth::user()->id;
            $Nomor->save();

            $prodiName = $this->generateAndSavePdf($skam->id);
            if ($prodiName !== null) {
                $nameTable = 'Surat Keterangan Aktif Mahasiswa';
                UploudSuratToDrive::dispatch($skam->id, $nameTable, $prodiName, SuratKeteranganAktifMahasiswa::class);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan',
                'error' => $th->getMessage() . ' di baris ' . $th->getLine()
            ]);
        }
    }

    public function show($id)
    {
        $skam = SuratKeteranganAktifMahasiswa::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_aktif_mahasiswa.prodi_id')
            ->select(
                'surat_keterangan_aktif_mahasiswa.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi',
                'prodi.nama_kepala as nama_kepala_prodi'
            )
            ->where('surat_keterangan_aktif_mahasiswa.id', $id)
            ->first();

        if (!$skam) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }


        $nomorStr = $skam->nomor_surat ?? $skam->nomor ?? null;
        if ($nomorStr) {
            $parts = explode('/', $nomorStr);
            $firstPart = $parts[0];
            if (strpos($firstPart, '-') !== false) {
                $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
            }
            $skam->no_surat = trim($firstPart);
        }

        return response()->json([
            'status' => true,
            'data' => $skam,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'no_surat' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id) {
                        $skam = \App\Models\SuratKeteranganAktifMahasiswa::find($id);
                        if ($skam) {
                            $originalNoSurat = '';
                            $nomorStr = $skam->nomor_surat ?? $skam->nomor ?? null;
                            if ($nomorStr) {
                                $parts = explode('/', $nomorStr);
                                $firstPart = $parts[0];
                                if (strpos($firstPart, '-') !== false) {
                                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                                }
                                $originalNoSurat = trim($firstPart);
                            }
                            if (trim((string)$value) !== trim((string)$originalNoSurat)) {
                                if (\App\Models\NoSurat::where('nomor', trim($value))->exists()) {
                                    $fail('Nomor surat sudah terpakai.');
                                }
                            }
                        }
                    }
                ],
                'prodi_id' => 'required|exists:prodi,id',
                'nama_mhs' => 'required|string|max:255',
                'nim' => 'required|string|max:255',

                'tempat_lahir' => 'required|string|max:100',
                'tanggal_lahir' => 'required|date',
                'prodi_mhs' => 'required|string|max:255',
                'semester' => 'required|string|max:50',
                'tahun_akademik' => 'required|string|max:100',
                'nama_ortu' => 'required|string|max:255',

                'alamat_ortu' => 'required|string',
                'hp_ortu' => 'nullable|string|max:50',
                'tanggal' => 'required|date',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $skam = SuratKeteranganAktifMahasiswa::find($id);
            if (!$skam) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $oldDriveFileId = $skam->drive_file_id;
            $oldLocalPath = $skam->local_path;

            // Track changes in no_surat
            $origNoSurat = '';
            $origNomorStr = $skam->nomor_surat ?? $skam->nomor ?? null;
            if ($origNomorStr) {
                $parts = explode('/', $origNomorStr);
                $firstPart = $parts[0];
                if (strpos($firstPart, '-') !== false) {
                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                }
                $origNoSurat = trim($firstPart);
            }
            if (trim((string)$validate['no_surat']) !== trim((string)$origNoSurat)) {
                if (!empty($origNoSurat)) {
                    \App\Models\NoSurat::where('nomor', $origNoSurat)->delete();
                }
                \App\Models\NoSurat::updateOrCreate(
                    ['nomor' => trim($validate['no_surat'])],
                    ['user_id' => Auth::user()->id]
                );
            }

            // Map frontend fields (if different) to database fields
            $dataToUpdate = [
                'prodi_id'       => $validate['prodi_id'],
                'th_akademik_id' => null,
                'nama_lengkap'   => $validate['nama_mhs'],
                'nim'            => $validate['nim'],
                'nik'            => null,
                'tempat_lahir'   => $validate['tempat_lahir'],
                'tanggal_lahir'  => $validate['tanggal_lahir'],
                'prodi_mhs'      => $validate['prodi_mhs'],
                'semester'       => $validate['semester'],
                'tahun_akademik' => $validate['tahun_akademik'],
                'nama_ortu'      => $validate['nama_ortu'],
                'nik_ortu'       => null,
                'nip_ortu'       => null,
                'alamat_ortu'    => $validate['alamat_ortu'],
                'hp_ortu'        => $validate['hp_ortu'] ?? null,
                'tanggal'        => $validate['tanggal'],
                'jenis_kelamin'  => Auth::user()->jenis_kelamin,
                'user_id'        => Auth::user()->id,
                'petanda_tangan' => $validate['petanda_tangan'] ?? 'tidak',
            ];

            $skam->fill($dataToUpdate);

            $noSurat = \App\Services\SuratService::formatNomorSurat('SKAM', $validate['no_surat'] ?? '', $validate['tanggal'], $validate['prodi_id']);
            $skam->nomor_surat = $noSurat;

            // Delete old file from Google Drive if exists
            if (!empty($oldDriveFileId)) {
                \App\Services\GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            $skam->drive_file_id = null;
            $skam->drive_link = null;
            $skam->status = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('SKAM', [
                'prodi_id' => $validate['prodi_id'] ?? $skam->prodi_id ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? null,
                'ketua' => $validate['ketua'] ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? null),
            ]);
            $skam->ketua = $signer['ketua'];
            $skam->nidn = $signer['nidn'];
            $skam->save();

            $prodiName = $this->generateAndSavePdf($skam->id);
            if ($prodiName !== null) {
                $nameTable = 'Surat Keterangan Aktif Mahasiswa';
                UploudSuratToDrive::dispatch($skam->id, $nameTable, $prodiName, SuratKeteranganAktifMahasiswa::class);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $skam = SuratKeteranganAktifMahasiswa::find($id);
            if (!$skam) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // $noSurat = \App\Services\SuratService::formatNomorSurat('SKAM', $validate['no_surat'], $validate['tanggal'], $validate['prodi_id'] ?? null);
            // $data->nomor_surat = $noSurat;

            $skam->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal dihapus'
            ]);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $data = SuratKeteranganAktifMahasiswa::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/surat_aktif.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $hasLocalFile = !empty($data->local_path) && file_exists($data->local_path) && is_readable($data->local_path);
            $pdfMtime = $hasLocalFile ? filemtime($data->local_path) : 0;

            if (!$hasLocalFile || $viewMtime > $pdfMtime) {
                $this->generateAndSavePdf($data->id);
                $data->refresh();
            }

            if (empty($data->local_path) || !file_exists($data->local_path) || !is_readable($data->local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->local_path);

            return response()->file($data->local_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error("SuratKeteranganAktifMahasiswa downloadPdf error for ID {$id}: " . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF', 'error' => $th->getMessage()], 500);
        }
    }

    private function generateAndSavePdf(int $id): ?string
    {
        $data = SuratKeteranganAktifMahasiswa::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_aktif_mahasiswa.prodi_id')
            ->leftJoin('fakultas_prodi', 'fakultas_prodi.prodi_id', '=', 'prodi.id')
            ->leftJoin('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'fakultas.tanda_tangan_id')
            ->select(
                'surat_keterangan_aktif_mahasiswa.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi',
                'prodi.nama_kepala as nama_kepala_prodi',
                'prodi.nidn_kepala as nidn_kepala_prodi',
                'fakultas.nama as fakultas',
                'fakultas.dekan as dekan',
                'fakultas.nidn_dekan as nidn_dekan',
                \DB::raw('COALESCE(tanda_tangan.tdd, tanda_tangan.gambar) as ttd')
            )
            ->where('surat_keterangan_aktif_mahasiswa.id', $id)
            ->first();

        if (!$data) {
            return null;
        }

        $pdfData = $this->buildPdfData($data);
        $prodiFolder = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($data->nama_prodi ?? 'UMUM');
        $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganAktifMahasiswaController');
        $pdf = Pdf::loadView('pdf.surat_aktif', $pdfData)->setPaper('a4', 'portrait');
        $safeNim = SuratService::sanitizeFileSegment($data->nim ?? null, (string) $data->id);
        $fileName = 'surat_keterangan_aktif_mahasiswa_' . $safeNim . '_' . uniqid() . '.pdf';

        $path = $directory . $fileName;
        $pdf->save($path);

        SuratKeteranganAktifMahasiswa::where('id', $data->id)->update(['local_path' => $path]);

        return $data->nama_prodi ?? 'UMUM';
    }

    private function buildPdfData($data)
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
                        $tddBase64 = \App\Services\SuratService::getBase64Image($tddPath);
                    }
                }
            }

            $stempelPath = base_path('../public_html/img/stempel.png');
            if (file_exists($stempelPath)) {
                $stempelBase64 = \App\Services\SuratService::getBase64Image($stempelPath, 'image/png');
            }
        }

        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $finalPejabat = !empty($data->ketua) ? $data->ketua : ($data->nama_kepala_prodi ? $data->nama_kepala_prodi : $data->dekan);
        $finalNidn = !empty($data->nidn) ? $data->nidn : ($data->nama_kepala_prodi ? $data->nidn_kepala_prodi : $data->nidn_dekan);
        if (empty($finalPejabat) || empty($finalNidn)) {
            $fallbackSigner = SuratService::resolveSigner('SKAM', [
                'prodi_id' => $data->prodi_id ?? null,
                'ketua' => $finalPejabat,
                'nidn' => $finalNidn,
            ]);
            if (empty($finalPejabat)) {
                $finalPejabat = $fallbackSigner['ketua'] ?? null;
            }
            if (empty($finalNidn)) {
                $finalNidn = $fallbackSigner['nidn'] ?? null;
            }
        }

        $dosenIdentity = \App\Services\DosenService::findDosenIdentity($finalNidn, $finalPejabat);
        $kodeDosen = $dosenIdentity['kode'];
        $nidnDosen = $dosenIdentity['matched'] ? $dosenIdentity['nidn'] : $finalNidn;

        return [
            'prodi_id' => $data->prodi_id,
            'nomor_surat' => $data->nomor_surat,
            'nama' => $data->nama_lengkap,
            'nim' => $data->nim,
            'nik' => $data->nik,
            'tempat_lahir' => $data->tempat_lahir,
            'tanggal_lahir' => \App\Services\SuratService::formatTanggalIndonesian($data->tanggal_lahir),
            'prodi_mhs' => $data->prodi_mhs,
            'prodi' => $data->nama_prodi ?? ($data->prodi ?? ($data->prodi_mhs ?? '')),
            'alias_prodi' => !empty($data->alias_prodi) ? $data->alias_prodi : ($data->prodi_mhs ?? ''),
            'semester' => $data->semester,
            'tahun_akademik' => $data->tahun_akademik,
            'nama_ortu' => $data->nama_ortu,
            'nik_ortu' => $data->nik_ortu,
            'alamat_ortu' => $data->alamat_ortu,
            'hp_ortu' => $data->hp_ortu,
            'tanggal_surat' => \App\Services\SuratService::formatTanggalIndonesian($data->tanggal),
            'nama_prodi' => $data->nama_prodi,
            'nama_kepala_prodi' => $data->nama_kepala_prodi,
            'nidn_kepala_prodi' => $data->nidn_kepala_prodi,
            'ketua' => $data->ketua,
            'nidn' => $data->nidn,
            'kprodi' => $finalPejabat,
            'nidn_kprodi' => $finalNidn,
            'dekan' => $finalPejabat,
            'fakultas' => $data->fakultas,
            'nidn_dekan' => $finalNidn,
            'kode_dosen' => $kodeDosen,
            'nidn_dosen' => $nidnDosen,
            'kopBase64' => $kopBase64,
            'stempel' => $stempelBase64,
            'petanda_tangan' => $data->petanda_tangan ?? ($petandaTangan ?? 'tidak'),
            'ttd' => $tddBase64,
        ];
    }

    public function getProdi()
    {
        try {
            $login = Auth::user()->prodi;
            if ($login) {
                $prodi = Prodi::where('id', $login->id)->first();
            } else {
                $prodi = Prodi::all();
            }
            return response()->json([
                'status' => true,
                'data' => $prodi
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diunduh'
            ]);
        }
    }
}
