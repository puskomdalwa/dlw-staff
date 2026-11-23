<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Izin Penelitian</title>

    <style>
        /* WAJIB untuk DomPDF */
        @page {
            margin: 0cm 2cm 0cm 2cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .header-univ {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .nomor {
            margin-bottom: 12px;
        }

        .alamat {
            margin-bottom: 12px;
        }

        .salam {
            margin-bottom: 10px;
        }

        .paragraf {
            text-align: justify;
            text-indent: 1.25cm;
            margin-bottom: 8px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
            margin-left: 30px;
            /* indent seperti Word */
        }

        table.data td {
            padding: 2px 0;
            vertical-align: top;
        }

        table.data td.label {
            width: 180px;
        }

        table.ttd {
            width: 100%;
            margin-top: 15px;
        }

        .nama-ttd {
            margin-top: 10px;
            font-weight: bold;
            text-decoration: underline;
        }

        .kop img {
            margin-top: 0px;
            width: 100%;
            height: auto;
            display: block;
        }

        .text-left {
            text-align: left;
            margin-left: 30px;
        }

        .kop {
            margin-top: 0.1cm;
            /* naik ke atas */
            margin-left: -1.5cm;
            /* tembus margin kiri */
            margin-right: -1.5cm;
            /* tembus margin kanan */
        }

        .penutup {
            margin-top: 10px;
            text-align: justify;
            text-indent: 1.25cm;
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

    <!-- HEADER -->
    <div class="kop">
        @if (!empty($kopBase64))
            <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- NOMOR SURAT -->
    <div class="nomor">
        Nomor &nbsp;&nbsp;: {{ $nomor }}<br>
        Lamp. &nbsp;&nbsp;&nbsp;: -<br>
        Hal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: Surat Izin Penelitian
    </div>

    <!-- TUJUAN -->
    <div class="alamat">
        Kepada Yth.<br>
        {{ $kepada }}<br>
        di Tempat
    </div>

    <!-- SALAM -->
    <div class="salam">
        Assalamu'alaikum Warahmatullahi Wabarakatuh,
    </div>

    <!-- PARAGRAF PEMBUKA -->
    <div class="paragraf">
        Bersamaan dengan surat ini mohon dengan hormat, agar mahasiswa/i berikut ini:
    </div>

    <!-- DATA MAHASISWA -->
    <table class="data">
        <tr>
            <td class="label">Nama</td>
            <td width="10">:</td>
            <td>{{ $nama }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td>:</td>
            <td>{{ $nim }}</td>
        </tr>
        <tr>
            <td class="label">Semester</td>
            <td>:</td>
            <td>{{ $semester }}</td>
        </tr>
        <tr>
            <td class="label">Fakultas / Prodi</td>
            <td>:</td>
            <td>{{ $fakultas_name }} / {{ $prodi_name }}</td>
        </tr>
        @if(!empty($judul))
        @php
        $isArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $judul);
        @endphp
        <tr>
            <td class="label">Judul Penelitian</td>
            <td>:</td>
            <td style="{{ $isArabic ? "font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4;" : "" }}">
                @if($isArabic)
                {!! nl2br(e($judul)) !!}
                @else
                {{ $judul }}
                @endif
            </td>
        </tr>
        @endif
        <tr>
            <td class="label">Dari Tanggal</td>
            <td>:</td>
            <td>{{ $dari_tanggal }} s/d Selesai</td>
        </tr>
    </table>

    <!-- PARAGRAF ISI -->
    <div class="paragraf">
        Diberikan kesempatan dan izinnya untuk melaksanakan penelitian di Lembaga Pendidikan
        yang Bapak / Ibu pimpin, sebagai prasyarat penyelesaian studi atau perkuliahan di
        Universitas Islam Internasional Darullughah Wadda'wah Bangil, Pasuruan.
    </div>

    <!-- PENUTUP -->
    <div class="penutup">
        Demikian surat permohonan ini, atas pertimbangan dan persetujuannya kami sampaikan
        terima kasih, jazakumullaahu khairan, insya Allaah, aamiin.
    </div>
    <div class="salam">
        Wassalamu'alaikum warahmatullahi wabarakatuh.
    </div>

    <!-- TANDA TANGAN -->
    <table class="ttd" width="100%" cellpadding="0" cellspacing="0" style="margin-top:15px;">
        <tr>
            <td width="60%"></td>

            <!-- BLOK KANAN -->
            <td width="40%" style="text-align:center;">
                Pasuruan, {{ $tanggal }}<br>
                Dekan Fakultas {{ $fakultas_name }}<br>

                <!-- AREA TTD (OVERLAY AMAN) -->
                <table align="center" cellpadding="0" cellspacing="0" style="margin: -10px auto -10px auto; width: 220px;">
                    <tr>
                        <td style="height:80px; text-align:left; vertical-align:middle; padding:0;">
                            <div style="position: relative; width: 220px; height: 80px;">
                                @if(!empty($stempel))
                                <img src="{{ $stempel }}" style="position: absolute; left: 15px; top: -5px; width: 88px; height: 88px; z-index: 2;">
                                @endif
                                @if(!empty($ttd))
                                <img src="{{ $ttd }}" style="position: absolute; left: 55px; top: 0px; width: 150px; max-height: 80px; object-fit: contain; z-index: 1;">
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
                @php
                $finalKepala = !empty($ketua) ? $ketua : (!empty($nama_dekan) ? $nama_dekan : null);
                $finalNidn = !empty($nidn) ? $nidn : (!empty($nidn_dekan) ? $nidn_dekan : null);
                if (empty($finalKepala) || empty($finalNidn)) {
                $fallback = \App\Services\SuratService::resolveSigner('SIP', [
                'prodi_id' => $prodi_id ?? null,
                'ketua' => $finalKepala,
                'nidn' => $finalNidn
                ]);
                if (empty($finalKepala)) $finalKepala = $fallback['ketua'] ?? '';
                if (empty($finalNidn)) $finalNidn = $fallback['nidn'] ?? '';
                }
                @endphp
                <div class="nama-ttd" style="margin-top:4px;">
                    {{ $finalKepala }}
                </div>
                @if(!empty($finalNidn))
                <div style="margin-top:2px; font-size:10pt;">NIDN. {{ $finalNidn }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>

</html>