<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengumuman Akademik</title>

    <style>
        /* WAJIB untuk DomPDF */
        @page {
            margin: 0cm 2cm 1cm 2cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11.5pt;
            line-height: 1.5;
            color: #000;
        }

        /* ===== KOP SURAT ===== */
        .kop {
            margin-top: 0.1cm;
            /* mentok ke atas batas margin kertas (1cm) */
            margin-left: -1.5cm;
            /* tembus margin kiri */
            margin-right: -1.5cm;
            /* tembus margin kanan */
        }

        .kop img {
            margin-top: 0cm;
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

        .text-bold {
            font-weight: bold;
        }

        /* ===== JUDUL & HEADER PENGUMUMAN ===== */
        .judul {
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-top: 20px;
            font-size: 13pt;
        }

        .nomor {
            margin-top: 3px;
            margin-bottom: 8px;
            font-size: 11pt;
        }

        .tentang {
            margin-bottom: 6px;
            font-size: 11pt;
        }

        .judul-pengumuman {
            font-weight: bold;
            font-size: 12pt;
            line-height: 1.35;
            margin-bottom: 20px;
        }

        /* ===== ISI PENGUMUMAN ===== */
        .isi-pengumuman {
            text-align: justify;
            line-height: 1.5;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .isi-pengumuman p {
            margin-top: 0;
            margin-bottom: 8px;
            text-align: justify;
        }

        .penutup {
            text-align: justify;
            line-height: 1.5;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        /* ===== TANDA TANGAN ===== */
        table.ttd {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        table.ttd td {
            vertical-align: top;
            padding: 0;
        }

        .ttd-block {
            text-align: center;
            line-height: 1.4;
        }

        .nama-pejabat {
            margin-top: 4px;
            font-size: 11.5pt;
        }

        .niy-pejabat {
            font-size: 11pt;
            margin-top: 1px;
        }
    </style>
</head>

<body>

    <!-- KOP SURAT -->
    <div class="kop">
        @if(isset($kopBase64))
        <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- JUDUL -->
    <div class="text-center">
        <div class="judul">PEMBERITAHUAN</div>
        <div class="nomor">{{ preg_replace('/^nomor\s*:\s*/i', '', $nomor) }}</div>
        <div class="tentang">Tentang</div>
        @if(!empty($judul))
        <div class="judul-pengumuman">{!! nl2br(e(strtoupper($judul))) !!}</div>
        @endif
    </div>

    @if(!empty($kepada))
    <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td style="width: 10%; vertical-align: top;">Kepada</td>
            <td style="width: 3%; vertical-align: top;">:</td>
            <td style="vertical-align: top;"><strong>{{ $kepada }}</strong></td>
        </tr>
    </table>
    @endif

    <!-- ISI PENGUMUMAN -->
    @php
        $isHtml = ($isi != strip_tags($isi));
    @endphp
    <div class="isi-pengumuman">
        @if($isHtml)
            {!! $isi !!}
        @else
            {!! nl2br(e($isi)) !!}
        @endif
    </div>

    <!-- PENUTUP -->
    @if(!empty($isi) && !str_contains(strtolower($isi), 'demikian'))
    <div class="penutup">
        Demikian pengumuman ini disampaikan atas perhatian dan kerjasamanya disampaikan terima kasih
    </div>
    @endif

    <!-- TANDA TANGAN -->
    @php
        $formattedNiy = $nidn_penandatangan ?? '';
        if (!empty($formattedNiy)) {
            if (!preg_match('/^(NIY|NIDN)/i', $formattedNiy)) {
                $formattedNiy = 'NIY. ' . $formattedNiy;
            }
        }
    @endphp

    <table class="ttd" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 40px;">
        <tr>
            <td width="55%"></td>
            <td width="45%" style="text-align: center; vertical-align: top;">
                <div>{{ $tempat }}, {{ $tanggal }}</div>
                <div>Kepala BAAK</div>

                <!-- AREA TTD & STEMPEL -->
                @if($petanda_tangan == 'ya' && !empty($ttd))
                    <div style="position: relative; width: 230px; height: 105px; margin: 0 auto; margin-top: -5px; margin-bottom: -15px;">
                        @if(!empty($stempel))
                        <img src="{{ $stempel }}" style="position: absolute; left: 10px; top: 5px; width: 95px; height: 95px; z-index: 2;" alt="Stempel">
                        @endif
                        <img src="{{ $ttd }}" style="position: absolute; left: 45px; top: 10px; max-width: 150px; max-height: 80px; object-fit: contain; z-index: 1;" alt="Tanda Tangan">
                    </div>
                @elseif($petanda_tangan == 'stempel' && !empty($stempel))
                    <div style="position: relative; width: 230px; height: 105px; margin: 0 auto; margin-top: 5px; margin-bottom: 5px;">
                        <img src="{{ $stempel }}" style="position: absolute; left: 67px; top: 5px; width: 95px; height: 95px; z-index: 2;" alt="Stempel">
                    </div>
                @else
                    <div style="height: 80px;"></div>
                @endif

                @php
    $finalKepala = !empty($ketua) ? $ketua : (!empty($nama_penandatangan) ? $nama_penandatangan : null);
    $finalNidn   = !empty($nidn) ? $nidn : (!empty($nidn_penandatangan) ? $nidn_penandatangan : null);
    if (empty($finalKepala) || empty($finalNidn)) {
        $fallback = \App\Services\SuratService::resolveSigner('PA', [
            'ketua' => $finalKepala,
            'nidn'  => $finalNidn,
        ]);
        if (empty($finalKepala)) $finalKepala = $fallback['ketua'] ?? '';
        if (empty($finalNidn)) $finalNidn = $fallback['nidn'] ?? '';
    }
@endphp
                <div class="nama-pejabat">{{ $finalKepala }}</div>
                @if(!empty($finalNidn))
                <div class="niy-pejabat">NIDN. {{ $finalNidn }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>

</html>
