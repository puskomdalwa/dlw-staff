# Dokumentasi API Integrasi Tanda Tangan QR SK 6

API ini digunakan untuk menghubungkan modul **SK 6** dengan website lain (misalnya sistem **Keuangan / BAKU**, **KKN / TASMA**, **Keamanan / Qismul Aman**, **Prodi**, atau **Idaroh Syuun / Wakil Rektor I**) agar dapat melihat status timeline TTD SK 6 dan melakukan konfirmasi tanda tangan QR secara terpusat.

---

## 1. Base URL & Autentikasi

- **Base URL**: `{APP_URL}/api/external/sk6`
  - Nilai aktual dapat dilihat & disalin langsung dari halaman `/sk6` -> tombol **API & Integrasi TTD**.
- **Header Autentikasi** (pilih salah satu):
  - `X-API-Key: <API_KEY>`
  - `Authorization: Bearer <API_KEY>`
- **Regenerate API Key**:
  - Dapat dilakukan langsung dari halaman `/sk6` -> tombol **API & Integrasi TTD** -> **Generate Ulang**.
  - Saat API Key digenerate ulang, key lama otomatis tidak berlaku (`401 Unauthorized`).

---

## 2. Daftar `step_key` (Urutan Timeline Resmi)

| Urutan | `step_key` | Jabatan / Title di PDF SK 6 |
|---|---|---|
| 1 | `kaprodi` | Ketua Program Studi `{Nama Prodi}` |
| 2 | `baku` | Kepala Biro Administrasi Keuangan |
| 3 | `kkn` | Ketua TASMA, KKN & PPL |
| 4 | `idaroh_syuun` | Wakil Rektor I |
| 5 | `keamanan` | Ketua Qismul Aman |

---

## 3. Endpoints

### A. List Data SK 6
`GET /`

Query parameter opsional:
- `nim` — filter tepat berdasarkan NIM mahasiswa
- `prodi_id` — filter berdasarkan ID prodi
- `search` — cari berdasarkan nama mahasiswa atau NIM
- `step_key` & `step_status` (`pending` atau `approved`) — misal `?step_key=baku&step_status=pending` untuk mengambil daftar SK 6 yang belum dikonfirmasi BAKU
- `ready_for_step` — **(Rekomendasi untuk alur berjenjang)** hanya tampilkan data yang **semua step sebelumnya sudah `approved`** dan step target masih `pending`. Contoh untuk Simkeu (BAKU): `?ready_for_step=baku` (hanya muncul setelah `kaprodi` ACC dan `baku` belum ACC).
- `require_previous_approved` (`1` / `true`) — digabung dengan `step_key` (misal `?step_key=baku&step_status=pending&require_previous_approved=1` atau `?step_key=baku&step_status=approved&require_previous_approved=1`), mewajibkan semua step sebelum `step_key` sudah `approved`.
- `approved_steps` — daftar `step_key` dipisah koma yang wajib sudah `approved` (misal `?approved_steps=kaprodi` atau `?approved_steps=kaprodi,baku`).
- `pending_steps` — daftar `step_key` dipisah koma yang wajib masih `pending` (misal `?pending_steps=baku`).
- `limit` (default `15`, maksimal `100`), `page`

Contoh Response `200 OK`:
```json
{
  "status": true,
  "data": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1,
    "items": [
      {
        "id": 248,
        "nama_mhs": "Ahmad Fauzi",
        "nim": "202285290001",
        "prodi_id": 10,
        "prodi_nama": "Pendidikan Bahasa Arab",
        "tanggal": "2026-11-21",
        "signed_count": 1,
        "total_steps": 5,
        "is_completed": false,
        "steps": [
          {
            "step_order": 1,
            "step_key": "kaprodi",
            "short_label": "Kaprodi",
            "title": "Ketua Program Studi Pendidikan Bahasa Arab",
            "signer_name": "Dr. Mustahar Ali Wardana, M.Pd.",
            "signer_nidn": "2126056801",
            "status": "approved",
            "is_signed": true,
            "signed_at": "2026-11-21T15:50:00+00:00",
            "signed_by_source": "web_staff",
            "signature_token": "...",
            "verify_url": "https://staffapp.uiidalwa.web.id/verify/sk6/..."
          }
        ]
      }
    ]
  },
  "message": "Daftar SK6 berhasil diambil"
}
```

### B. Detail SK 6 berdasarkan ID atau NIM
- `GET /{id}`
- `GET /by-nim/{nim}`

Mengembalikan data SK 6 lengkap dengan 5 step timeline beserta `qr_data_uri` untuk step yang sudah berstatus `approved`.

### C. Konfirmasi / Centang TTD QR
- `POST /{id}/sign`
- `POST /by-nim/{nim}/sign`

Body JSON:
```json
{
  "step_key": "baku",
  "signer_name": "Dr. Musleh Harry, SH, M.Hum",
  "signer_nidn": "",
  "title": "Kepala Biro Administrasi Keuangan",
  "source": "web-keuangan",
  "notes": "Lunas administrasi keuangan",
  "require_previous_approved": true
}
```
Keterangan field:
- `step_key` (**wajib**): salah satu dari `kaprodi`, `baku`, `kkn`, `idaroh_syuun`, `keamanan`.
- `signer_name`, `signer_nidn`, `title`, `source`, `notes` (**opsional**): jika `signer_name` / `title` tidak dikirim, sistem otomatis memakai nama dan jabatan resmi yang sudah terdaftar di master `prodi` / `setting_jabatan`.
- `require_previous_approved` (**opsional**, boolean, default `false`): jika `true`, request ditolak (`422`) apabila ada step sebelumnya yang masih `pending` (mencegah loncat step).
- `required_steps` (**opsional**, array `step_key`): misal `["kaprodi"]`, menolak (`422`) apabila step syarat tersebut belum `approved`.

Setelah request berhasil, sistem otomatis memperbarui file PDF SK 6 sehingga QR Code verifikasi langsung terpasang pada halaman surat terkait.

### D. Batalkan Konfirmasi TTD QR
- `POST /{id}/revoke`

Body JSON:
```json
{
  "step_key": "baku"
}
```

### E. Verifikasi QR Publik (Tanpa API Key)
- Halaman web (saat QR discan kamera HP): `GET {APP_URL}/verify/sk6/{signature_token}`
- JSON API: `GET {APP_URL}/api/sk6/verify/{signature_token}`

---

## 4. Contoh Integrasi

### cURL (Ambil List Siap BAKU setelah Kaprodi ACC & Konfirmasi Anti-Loncat)
```bash
# 1. Ambil daftar SK 6 yang sudah ACC Kaprodi dan belum ACC BAKU
curl -X GET "https://staffapp.uiidalwa.web.id/api/external/sk6?ready_for_step=baku" \
  -H "X-API-Key: sk6_xxxxxxxxxxxxxxxxx"

# 2. Konfirmasi ACC BAKU (tolak 422 jika step sebelumnya belum ACC)
curl -X POST "https://staffapp.uiidalwa.web.id/api/external/sk6/by-nim/202285290001/sign" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk6_xxxxxxxxxxxxxxxxx" \
  -d '{
    "step_key": "baku",
    "source": "web-keuangan",
    "notes": "Pembayaran ujian skripsi lunas",
    "require_previous_approved": true
  }'
```

### Laravel (`Http` Client)
```php
use Illuminate\Support\Facades\Http;

// Ambil hanya data yang sudah ACC Kaprodi & siap diproses BAKU
$list = Http::withHeaders([
    'X-API-Key' => config('services.sk6.api_key'),
])->get(config('services.sk6.base_url'), [
    'ready_for_step' => 'baku',
]);

// Konfirmasi ACC BAKU dengan pengaman agar tidak bisa loncat step
$response = Http::withHeaders([
    'X-API-Key' => config('services.sk6.api_key'),
])->post(config('services.sk6.base_url') . '/by-nim/' . $nim . '/sign', [
    'step_key' => 'baku',
    'source'   => 'web-keuangan',
    'notes'    => 'Lunas via sistem keuangan',
    'require_previous_approved' => true,
]);

if ($response->successful()) {
    $data = $response->json('data');
}
```
