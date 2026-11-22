# Form Pengambilan Ijazah Digital QR Signature & External Integration API

Dokumen ini menjelaskan integrasi Tanda Tangan QR (4 Tahap Berurutan) untuk modul **Form Pengambilan Ijazah (Form Bebas Tanggungan)** pada aplikasi Staff UII Dalwa agar website/layanan lain (Keuangan Pondok, BAKU, BAAK, Wakil Rektor I) dapat mengecek status serta mengonfirmasi TTD QR secara langsung.

---

## 1. Base URL & Autentikasi

- **Base URL**: `<APP_URL>/api/external/form-pengambilan-ijazah` (lihat nilai aktual di tombol **API & Integrasi TTD** pada halaman `/form-pengambilan-ijazah`).
- **Autentikasi**: Kirim API Key lewat header:
  - `X-API-Key: <API_KEY>` (atau `Authorization: Bearer <API_KEY>`)
- **API Key dapat dilihat / disalin / digenerate ulang** langsung di UI menu **Form Pengambilan Ijazah -> API & Integrasi TTD**.

---

## 2. Daftar Tahap (`step_key`)

Terdapat 4 tahap tanda tangan QR:

| Urutan | `step_key` | Jabatan / Unit | Keterangan |
| :---: | :--- | :--- | :--- |
| 1 | `keuangan_pondok` | Keuangan Pondok | Bebas Tanggungan Keuangan Pondok |
| 2 | `baku` | Kepala Biro Administrasi Keuangan (Keuangan Jamiah) | Bebas Tanggungan Keuangan Jamiah |
| 3 | `baak` | Kepala BAAK (Akademik Jamiah) | Bebas Tanggungan Akademik Jamiah |
| 4 | `warek_1` | Wakil Rektor I | Rekomendasi Yayasan / Wakil Rektor I |

> Ketika seluruh **4/4 step** berstatus `approved`, sistem otomatis men-generate file PDF bertanda tangan QR (`signed_local_path`) dan mengirimkannya ke antrean upload Google Drive (`signed_drive_link`). URL Drive pada tabel terbagi dua: **Tanpa TTD** (`drive_link`) dan **Ber-TTD (QR)** (`signed_drive_link`).

---

## 3. Endpoint Eksternal (Membutuhkan API Key)

### A. List Data Form Pengambilan Ijazah
`GET /api/external/form-pengambilan-ijazah`

Query params opsional:
- `nim` — filter NIM persis
- `prodi` — filter nama prodi
- `search` — cari nama/NIM/prodi
- `step_key` + `step_status` (`pending` / `approved`) — filter status step tertentu
- `ready_for_step` — **(Rekomendasi untuk alur berjenjang)** hanya tampilkan data yang **semua step sebelumnya sudah `approved`** dan step target masih `pending`. Contoh untuk BAKU di Simkeu: `?ready_for_step=baku` (hanya muncul setelah `keuangan_pondok` ACC dan `baku` belum ACC).
- `require_previous_approved` (`1` / `true`) — digabung dengan `step_key` (misal `?step_key=baku&step_status=pending&require_previous_approved=1`), mewajibkan semua step sebelum `step_key` sudah `approved`.
- `approved_steps` — daftar `step_key` dipisah koma yang wajib sudah `approved` (misal `?approved_steps=keuangan_pondok` atau `?approved_steps=keuangan_pondok,baku`).
- `pending_steps` — daftar `step_key` dipisah koma yang wajib masih `pending` (misal `?pending_steps=baku`).
- `limit` — jumlah per halaman (1..100, default 15)

### B. Detail Berdasarkan ID / NIM
- `GET /api/external/form-pengambilan-ijazah/{id}`
- `GET /api/external/form-pengambilan-ijazah/by-nim/{nim}`

### C. Konfirmasi TTD QR (Sign)
- `POST /api/external/form-pengambilan-ijazah/{id}/sign`
- `POST /api/external/form-pengambilan-ijazah/by-nim/{nim}/sign`

Contoh body JSON:
```json
{
  "step_key": "baku",
  "signer_name": "Dr. Musleh Harry, SH, M.Hum",
  "signer_nidn": "123456",
  "source": "web_keuangan",
  "notes": "Lunas seluruh administrasi keuangan",
  "require_previous_approved": true
}
```
Parameter wajib: `step_key` (`keuangan_pondok` | `baku` | `baak` | `warek_1`).
Field opsional: `signer_name`, `signer_nidn`, `title`, `source`, `notes`, serta:
- `require_previous_approved` (boolean, default `false`): jika `true`, ditolak (`422`) bila ada step sebelumnya yang masih `pending` (tidak bisa loncat step).
- `required_steps` (array `step_key`): misal `["keuangan_pondok"]`, ditolak (`422`) bila step syarat tersebut belum `approved`.

### D. Batalkan Konfirmasi TTD QR (Revoke)
- `POST /api/external/form-pengambilan-ijazah/{id}/revoke`

Body JSON:
```json
{
  "step_key": "keuangan_pondok"
}
```
Membatalkan status step menjadi `pending` dan menghapus file PDF Ber-TTD & Drive Ber-TTD terkait.

---

## 4. Contoh Filter Berjenjang (Contoh untuk Simkeu / BAKU)

- List yang siap diproses BAKU (sudah ACC Keuangan Pondok & belum ACC BAKU):
  `GET /api/external/form-pengambilan-ijazah?ready_for_step=baku`
- Atau dengan filter eksplisit:
  `GET /api/external/form-pengambilan-ijazah?approved_steps=keuangan_pondok&pending_steps=baku`
- Konfirmasi ACC BAKU dengan pengaman agar tidak bisa loncat step (`422` jika `keuangan_pondok` masih `pending`):
  `POST /api/external/form-pengambilan-ijazah/by-nim/{nim}/sign` dengan body `{"step_key": "baku", "require_previous_approved": true}`

---

## 5. Verifikasi Publik (Tanpa API Key)

- **Halaman Web (hasil scan QR kamera HP)**: `GET /verify/form-pengambilan-ijazah/{token}`
- **JSON API**: `GET /api/form-pengambilan-ijazah/verify/{token}`
