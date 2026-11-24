<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Cuti</title>

    <style>
        /* ===== PAGE ===== */
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
        }

        /* ===== KOP SURAT ===== */
        .kop {
            margin-top: 0.1cm;
            width: 100%;
        }

        .kop img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ===== KONTEN ===== */
        .content {
            padding-right: 2cm;
            padding-left: 2cm;
            margin-top: 15px;
        }

        .text-center {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .indent {
            text-indent: 1.25cm;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
        }

        table.data td {
            padding: 3px 0;
            vertical-align: top;
        }

        table.data td.label {
            width: 100px;
        }

        table.data td.separator {
            width: 15px;
            text-align: center;
        }

        table.ttd {
            width: 100%;
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .nama-ttd {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    @php
        $finalDekan = !empty($dekan) ? $dekan : (!empty($ketua) ? $ketua : (!empty($nama_kepala_prodi) ? $nama_kepala_prodi : ''));
        $finalNidnDekan = !empty($nidn_dekan) ? $nidn_dekan : (!empty($nidn) ? $nidn : (!empty($nidn_kepala_prodi) ? $nidn_kepala_prodi : ''));
        if (empty($finalDekan) || empty($finalNidnDekan)) {
            $fallback = \App\Services\SuratService::resolveSigner('SKC', [
                'prodi_id' => $prodi_id ?? null,
                'ketua' => $finalDekan,
                'nidn' => $finalNidnDekan,
            ]);
            if (empty($finalDekan)) $finalDekan = $fallback['ketua'] ?? '';
            if (empty($finalNidnDekan)) $finalNidnDekan = $fallback['nidn'] ?? '';
        }
        $finalNidnDekan = \App\Services\DosenService::resolveNidn($finalNidnDekan, $finalDekan);

        $rawFak = trim($fakultas ?? '');
        $fakultasText = $rawFak
            ? (str_starts_with(strtolower($rawFak), 'fakultas') ? $rawFak : 'Fakultas ' . $rawFak)
            : 'Fakultas';

        $alasanText = !empty($alasan) ? trim($alasan) : 'urusan finansial yang belum terpenuhi';
    @endphp

    <!-- KOP SURAT -->
    <div class="kop">
        @if(!empty($kopBase64))
            <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- ISI SURAT -->
    <div class="content">
        <p style="font-style: italic; margin-bottom: 12px;">Assalamualaikum Wr.Wb</p>

        <p style="margin-bottom: 8px;">Dengan Hormat.</p>

        <p class="indent" style="margin-bottom: 12px;">Saya yang bertanda tangan di bawah ini :</p>

        <table class="data">
            <tr>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td>{{ $nama_mhs }}</td>
            </tr>
            <tr>
                <td class="label">Nim</td>
                <td class="separator">:</td>
                <td>{{ $nim }}</td>
            </tr>
            <tr>
                <td class="label">Jurusan</td>
                <td class="separator">:</td>
                <td>{{ $nama_prodi }}</td>
            </tr>
        </table>

        <p class="text-justify indent" style="margin-top: 10px; margin-bottom: 18px;">
            Mengajukan izin Cuti Pada semester {{ $dari_semester }} &amp; {{ $sampai_semester }} di Prodi {{ $nama_prodi }} {{ $fakultasText }} dikarenakan {{ $alasanText }} di perkuliahan Universitas Islam Internasional Darullughah Wadda'wah.
        </p>

        <p class="text-justify indent" style="margin-bottom: 18px;">
            Demikian permohonan izin yang kami ajukan, atas perkenannya saya ucapkan<br>
            <span style="display: inline-block; margin-left: 1.25cm;">Terimakasih</span>
        </p>

        <p style="margin-top: 14px; margin-bottom: 30px;">Wassalamualaikum Wr.Wb</p>

        <table class="ttd" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%"></td>
                <td width="45%" style="text-align: left; padding-left: 15px;">
                    Bangil, {{ $tanggal_surat }}<br>
                    Dekan {{ $fakultasText }}<br>

                    <div style="position: relative; width: 100%; height: 100px; margin-top: 4px; margin-bottom: 4px;">
                        @if(isset($petanda_tangan) && in_array($petanda_tangan, ['ya', 'stempel']))
                            @if(!empty($stempel))
                                <img src="{{ $stempel }}" style="position: absolute; left: -30px; top: 0px; width: 100px; height: 100px; z-index: 2;" alt="Stempel">
                            @endif
                            @if(!empty($ttd) && $petanda_tangan === 'ya')
                                <img src="{{ $ttd }}" style="position: absolute; left: 5px; top: 8px; max-width: 170px; max-height: 85px; object-fit: contain; z-index: 1;" alt="Tanda Tangan">
                            @endif
                        @endif
                    </div>

                    <div class="nama-ttd">{{ $finalDekan }}</div>
                    @if(!empty($finalNidnDekan))
                        <div style="font-size: 10pt; margin-top: 2px;">NIDN. {{ $finalNidnDekan }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
