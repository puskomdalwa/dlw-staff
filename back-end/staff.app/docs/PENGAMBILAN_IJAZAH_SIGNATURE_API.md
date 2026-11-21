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
  "step_key": "keuangan_pondok",
  "signer_name": "Ust. Ahmad",
  "signer_nidn": "123456",
  "source": "web_keuangan_pondok",
  "notes": "Lunas seluruh administrasi pondok"
}
```
Parameter wajib: `step_key` (`keuangan_pondok` | `baku` | `baak` | `warek_1`). Field lain opsional.

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

## 4. Verifikasi Publik (Tanpa API Key)

- **Halaman Web (hasil scan QR kamera HP)**: `GET /verify/form-pengambilan-ijazah/{token}`
- **JSON API**: `GET /api/form-pengambilan-ijazah/verify/{token}`
