<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Form Bebas Tanggungan</title>

    <style>
        @page {
            margin: 0.5cm 2cm 1cm 2cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        /* ===== KOP SURAT ===== */
        .kop {
            margin-top: 0.1cm;
            margin-left: -1.5cm;
            margin-right: -1.5cm;
        }

        .kop img {
            width: 100%;
            height: auto;
            display: block;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-justify {
            text-align: justify;
        }

        .font-bold {
            font-weight: bold;
        }

        /* ===== JUDUL ===== */
        .judul {
            font-size: 13.5pt;
            font-weight: bold;
            text-decoration: underline;
            text-align: center;
            margin-top: 25px;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }

        /* ===== TABEL DATA MAHASISWA ===== */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            margin-left: 15px;
        }

        table.data td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 11pt;
        }

        table.data td.label {
            width: 160px;
            font-weight: bold;
        }

        .titik-titik {
            color: #222;
        }

        /* ===== PERSYARATAN ===== */
        .intro-persyaratan {
            font-weight: bold;
            font-size: 11pt;
            margin-left: 15px;
            margin-bottom: 12px;
            margin-top: 15px;
        }

        table.table-persyaratan {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-left: 0;
        }

        table.table-persyaratan th,
        table.table-persyaratan td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 9.5pt;
            line-height: 1.35;
        }

        table.table-persyaratan th {
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
            padding: 8px 4px;
        }

        table.table-persyaratan td.cell-blank {
            height: 48px;
            vertical-align: middle;
        }

        .sub-item {
            padding-left: 14px;
            text-indent: -10px;
            margin-top: 2px;
            font-weight: normal;
        }
    </style>
    @php
        $fontFile = base_path('../public_html/fonts/Amiri-Regular.ttf');
        $fontPath = file_exists($fontFile) ? str_replace('\\', '/', realpath($fontFile)) : null;
    @endphp
    @if ($fontPath)
        <style>
            @font-face {
                font-family: 'Amiri';
                font-style: normal;
                font-weight: normal;
                src: url('{{ $fontPath }}') format('truetype');
            }

            @font-face {
                font-family: 'Amiri';
                font-style: normal;
                font-weight: bold;
                src: url('{{ $fontPath }}') format('truetype');
            }

            @font-face {
                font-family: 'Amiri';
                font-style: italic;
                font-weight: normal;
                src: url('{{ $fontPath }}') format('truetype');
            }

            @font-face {
                font-family: 'Amiri';
                font-style: italic;
                font-weight: bold;
                src: url('{{ $fontPath }}') format('truetype');
            }
        </style>
    @endif
</head>

<body>

    <!-- KOP SURAT -->
    <div class="kop">
        @if (!empty($kopBase64))
            <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="judul">
        FORM BEBAS TANGGUNGAN
    </div>

    <!-- DATA BIODATA -->
    <table class="data">
        <tr>
            <td class="label">NIM/NRIM</td>
            <td width="15">:</td>
            <td>{{ !empty($nim) ? $nim : '...........................................................................................' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Lengkap</td>
            <td>:</td>
            <td>{{ !empty($nama_mhs) ? $nama_mhs : '...........................................................................................' }}</td>
        </tr>
        <tr>
            <td class="label">Dosen Pembimbing</td>
            <td>:</td>
            <td>{{ !empty($dosen_pembimbing) ? $dosen_pembimbing : '...........................................................................................' }}</td>
        </tr>
        @php
            $isArabic = !empty($judul_skripsi) && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $judul_skripsi);
            $cleanJudul = !empty($judul_skripsi) ? trim($judul_skripsi, '"\' ') : '';
            $displayJudul = $isArabic ? $cleanJudul : (!empty($judul_skripsi) ? $judul_skripsi : '...........................................................................................');
        @endphp
        <tr>
            <td class="label">Judul Skripsi</td>
            <td>:</td>
            <td style="{{ $isArabic ? "font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4;" : "text-align: justify;" }}">
                @if($isArabic)
                    {!! nl2br(e($displayJudul)) !!}
                @else
                    {{ $displayJudul }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Tanggal Sidang</td>
            <td>:</td>
            <td>{{ !empty($tanggal_sidang) ? $tanggal_sidang : '...........................................................................................' }}</td>
        </tr>
        <tr>
            <td class="label">No Telpon/Hp</td>
            <td>:</td>
            <td>{{ !empty($no_hp) ? $no_hp : '...........................................................................................' }}</td>
        </tr>
    </table>

    <!-- TEKS PENGANTAR PERSYARATAN -->
    <div class="intro-persyaratan">
        Telah Menyelesaikan Persyaratan Adminitrasi Berupa:
    </div>

    <!-- TABEL PERSYARATAN -->
    <table class="table-persyaratan">
        <thead>
            <tr>
                <th width="6%">NO</th>
                <th width="48%">PERSYARATAN</th>
                <th width="21%">JABATAN</th>
                <th width="25%">TANDA TANGAN DAN STEMPEL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center font-bold">1</td>
                <td>
                    <div class="font-bold">BEBAS TANGGUNGAN KEUANGAN PONDOK</div>
                    <div class="sub-item">a. Membayar seluruh biaya Pendidikan sesuai ketentuan</div>
                    <div class="sub-item">b. Tidak memiliki segala bentuk semua tanggungan</div>
                </td>
                <td class="cell-blank"></td>
                <td class="cell-blank"></td>
            </tr>
            <tr>
                <td class="text-center font-bold">2</td>
                <td>
                    <div class="font-bold">BEBAS TANGGUNGAN KEUANGAN JAMIAH</div>
                    <div class="sub-item">a. Membayar seluruh biaya Pendidikan sesuai ketentuan</div>
                    <div class="sub-item">b. Membayar biaya wisuda bagi mahasiswa</div>
                    <div class="sub-item">c. Tidak memiliki segala bentuk semua tanggungan</div>
                </td>
                <td class="cell-blank"></td>
                <td class="cell-blank"></td>
            </tr>
            <tr>
                <td class="text-center font-bold">3</td>
                <td>
                    <div class="font-bold">BEBAS TANGGUNGAN AKADEMIK JAMIAH</div>
                    <div class="sub-item">a. Menyerahkan Skripsi 1 Jilid yang sudah disahkan oleh dewan penguji dan file skripsi</div>
                </td>
                <td class="cell-blank"></td>
                <td class="cell-blank"></td>
            </tr>
            <tr>
                <td class="text-center font-bold">4</td>
                <td>
                    <div class="font-bold">Rekomendasi Yayasan Pengambilan Ijazah IAI DALWA</div>
                    <div class="font-bold">Pengasuh Pondok Pesantren atau Wakil Rektor I</div>
                </td>
                <td class="cell-blank"></td>
                <td class="cell-blank"></td>
            </tr>
        </tbody>
    </table>

</body>

</html>
