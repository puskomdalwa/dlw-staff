<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Formulir Pengajuan Judul Skripsi</title>

    <style>
        @page {
            margin: 0.2cm 2cm 0.8cm 2cm;
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

        .font-bold {
            font-weight: bold;
        }

        /* ===== JUDUL ===== */
        .judul {
            font-size: 11.5pt;
            font-weight: bold;
            text-align: center;
            line-height: 1.25;
            margin-top: 8px;
            margin-bottom: 18px;
        }

        /* ===== TABEL DATA ===== */
        table.form-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 5px;
        }

        table.form-table td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 11pt;
        }

        table.form-table td.label {
            width: 155px;
        }

        table.form-table td.colon {
            width: 15px;
            text-align: center;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            min-height: 18px;
            padding-bottom: 1px;
            width: 100%;
        }

        .dots-row {
            letter-spacing: 0.5px;
            line-height: 1.65;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
        }

        .multiline-box {
            min-height: 82px;
            line-height: 1.55;
            text-align: justify;
            padding-bottom: 4px;
        }

        .multiline-box-5 {
            min-height: 102px;
            line-height: 1.55;
            text-align: justify;
            padding-bottom: 4px;
        }

        .lined-text {
            border-bottom: 1px dotted #000;
            padding-bottom: 2px;
        }

        table.signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        table.signature-table td {
            vertical-align: top;
            font-size: 11pt;
            line-height: 1.35;
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

    <!-- JUDUL FORMULIR -->
    <div class="judul">
        FORMULIR PENGAJUAN JUDUL SKRIPSI<br>
        PROGRAM STRATA 1
    </div>

    @php
        $dots = '........................................................................................................................';
        $isArabicJudul = !empty($judul_proposal_skripsi) && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $judul_proposal_skripsi);
        $isArabicRumusan = !empty($rumusan_masalah) && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $rumusan_masalah);
        $isArabicCatatan = !empty($catatan_kprodi) && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $catatan_kprodi);
    @endphp

    <!-- BAGIAN DATA MAHASISWA & JUDUL -->
    <table class="form-table">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td>
                @if (!empty($nama_mhs))
                    <div class="dotted-line">{{ $nama_mhs }}</div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td class="colon">:</td>
            <td>
                @if (!empty($nim))
                    <div class="dotted-line">{{ $nim }}</div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Program Studi</td>
            <td class="colon">:</td>
            <td>
                @if (!empty($prodi))
                    <div class="dotted-line">{{ $prodi }}</div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Judul Proposal Skripsi</td>
            <td class="colon">:</td>
            <td>
                @if (!empty($judul_proposal_skripsi))
                    <div class="multiline-box" style="{{ $isArabicJudul ? "font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.5;" : '' }}">
                        <span class="lined-text">{!! nl2br(e($judul_proposal_skripsi)) !!}</span>
                    </div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- BAGIAN RUMUSAN MASALAH / FOKUS PENELITIAN -->
    <table class="form-table" style="margin-top: 12px;">
        <tr>
            <td class="label">
                Rumusan Masalah/<br>
                Fokus Penelitian
            </td>
            <td class="colon">:</td>
            <td>
                @if (!empty($rumusan_masalah))
                    <div class="multiline-box-5" style="{{ $isArabicRumusan ? "font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.5;" : '' }}">
                        <span class="lined-text">{!! nl2br(e($rumusan_masalah)) !!}</span>
                    </div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN MAHASISWA & DOSEN PENASEHAT AKADEMIK -->
    <table class="signature-table" style="margin-top: 18px; margin-bottom: 18px;">
        <tr>
            <td width="52%" style="padding-left: 25px;">
                <br>
                Yang mengajukan,
                <div style="height: 58px;"></div>
                <div style="display: inline-block; min-width: 165px; border-bottom: 1px solid #000; padding-bottom: 1px;">
                    {{ !empty($nama_mhs) ? $nama_mhs : '............................................' }}
                </div>
            </td>
            <td width="48%" style="padding-left: 35px;">
                Pasuruan, {{ !empty($tanggal) ? $tanggal : '...............................' }}<br>
                Menyetujui<br>
                Dosen Penasehat Akademik
                <div style="height: 42px;"></div>
                <div style="display: inline-block; min-width: 165px; border-bottom: 1px solid #000; padding-bottom: 1px;">
                    {{ !empty($dosen_penasehat) ? $dosen_penasehat : '............................................' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- BAGIAN CATATAN KAPRODI -->
    <table class="form-table" style="margin-top: 6px;">
        <tr>
            <td class="label">Catatan Kaprodi</td>
            <td class="colon">:</td>
            <td>
                @if (!empty($catatan_kprodi))
                    <div class="multiline-box" style="{{ $isArabicCatatan ? "font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.5;" : '' }}">
                        <span class="lined-text">{!! nl2br(e($catatan_kprodi)) !!}</span>
                    </div>
                @else
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                    <div class="dots-row">{{ $dots }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN KETUA PROGRAM STUDI -->
    <table class="signature-table" style="margin-top: 18px;">
        <tr>
            <td width="52%"></td>
            <td width="48%" style="padding-left: 35px;">
                Pasuruan, {{ !empty($tanggal) ? $tanggal : '...............................' }}<br>
                Ketua Program Studi,

                @if (!empty($ttd) || !empty($stempel))
                    <div style="position: relative; width: 220px; height: 85px; margin-top: -5px; margin-bottom: -10px;">
                        @if (!empty($stempel))
                            <img src="{{ $stempel }}"
                                style="position: absolute; left: -15px; top: 0px; width: 95px; height: 95px; z-index: 2;">
                        @endif
                        @if (!empty($ttd))
                            <img src="{{ $ttd }}"
                                style="position: absolute; left: 20px; top: -5px; max-width: 160px; max-height: 95px; object-fit: contain; z-index: 1;">
                        @endif
                    </div>
                @else
                    <div style="height: 60px;"></div>
                @endif

                <div style="display: inline-block; min-width: 195px; border-bottom: 1px solid #000; padding-bottom: 2px; font-weight: bold;">
                    {{ !empty($nama_kaprodi) ? $nama_kaprodi : '' }}
                </div>
                @if (!empty($nidn_kaprodi))
                    <div style="font-size: 10.5pt; margin-top: 2px;">NIDN. {{ $nidn_kaprodi }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>

</html>

