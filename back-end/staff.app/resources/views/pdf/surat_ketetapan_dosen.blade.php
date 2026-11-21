<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Ketetapan Dosen</title>

    <style>
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

        .kop {
            margin-top: 0.2cm;
            width: 100%;
        }

        .kop img {
            width: 100%;
            height: auto;
            display: block;
        }

        .content {
            padding-left: 2.2cm;
            padding-right: 2.2cm;
            margin-top: 14px;
        }

        .text-center {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .title {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .nomor {
            font-size: 11.5pt;
            margin-bottom: 20px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 12px;
        }

        table.data td {
            padding: 3px 0;
            vertical-align: top;
        }

        table.data td.spacer {
            width: 24px;
        }

        table.data td.label {
            width: 115px;
        }

        table.data td.separator {
            width: 15px;
            text-align: center;
        }

        table.ttd {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .ttd-box {
            position: relative;
            width: 100%;
            height: 95px;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .nama-ttd {
            margin-top: 2px;
        }
    </style>
</head>

<body>
    @php
        $institusiModel = !empty($institusi_id) ? \App\Models\Institusi::find($institusi_id) : null;
        $sjRektor = \App\Models\SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'rektor')->first();

        $finalNamaInstitusi = !empty($institusiModel?->nama_institusi)
            ? $institusiModel->nama_institusi
            : ($nama_institusi ?? ($instansi ?? ''));

        $finalRektor = !empty($institusiModel?->rektor)
            ? $institusiModel->rektor
            : (!empty($sjRektor?->tandaTangan?->nama) ? $sjRektor->tandaTangan->nama : ($rektor ?? '-'));

        $finalKopBase64 = '';
        if (!empty($institusiModel?->kop_surat)) {
            $relKop = ltrim($institusiModel->kop_surat, '/');
            $kopFile = public_path($relKop);
            if (!file_exists($kopFile)) {
                $kopFile = base_path('../public_html/' . $relKop);
            }
            if (file_exists($kopFile)) {
                $finalKopBase64 = \App\Services\SuratService::getBase64Image($kopFile) ?? '';
            }
        }
        if (empty($finalKopBase64)) {
            $finalKopBase64 = $kopBase64 ?? '';
        }

        $finalTtd = '';
        $finalStempel = $stempel ?? '';
        if (isset($petanda_tangan) && in_array($petanda_tangan, ['ya', 'stempel'])) {
            if ($petanda_tangan === 'ya') {
                $ttdSource = $sjRektor?->tandaTangan?->tdd ?: ($sjRektor?->tandaTangan?->gambar ?? null);
                if (!empty($ttdSource)) {
                    if (str_starts_with($ttdSource, 'data:image')) {
                        $finalTtd = $ttdSource;
                    } else {
                        $relTtd = ltrim($ttdSource, '/');
                        $ttdFile = public_path($relTtd);
                        if (!file_exists($ttdFile)) {
                            $ttdFile = base_path('../public_html/' . $relTtd);
                        }
                        if (file_exists($ttdFile)) {
                            $finalTtd = \App\Services\SuratService::getBase64Image($ttdFile) ?? '';
                        }
                    }
                }
                if (empty($finalTtd)) {
                    $finalTtd = $ttd ?? '';
                }
            }

            if (empty($finalStempel)) {
                $stempelFile = public_path('img/stempel.png');
                if (!file_exists($stempelFile)) {
                    $stempelFile = base_path('../public_html/img/stempel.png');
                }
                if (file_exists($stempelFile)) {
                    $finalStempel = \App\Services\SuratService::getBase64Image($stempelFile, 'image/png') ?? '';
                }
            }
        }
    @endphp

    <div class="kop">
        @if(!empty($finalKopBase64))
            <img src="{{ $finalKopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <div class="content">
        <div class="text-center title">Surat Keterangan</div>
        <div class="text-center nomor">
            Nomor : {{ !empty($nomor) ? $nomor : '-' }}
        </div>

        <p style="margin-bottom: 6px;">Yang bertanda tangan di bawah ini:</p>

        <table class="data">
            <tr>
                <td class="spacer"></td>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td>{{ $finalRektor }}</td>
            </tr>
            <tr>
                <td class="spacer"></td>
                <td class="label">Jabatan</td>
                <td class="separator">:</td>
                <td>Rektor {{ $finalNamaInstitusi }}</td>
            </tr>
            <tr>
                <td class="spacer"></td>
                <td class="label">Alamat</td>
                <td class="separator">:</td>
                <td>{{ $alamat_penandatangan }}</td>
            </tr>
        </table>

        <p style="margin-top: 10px; margin-bottom: 6px;">Dengan ini menyatakan bahwa :</p>

        <table class="data">
            <tr>
                <td class="spacer"></td>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td>{{ $nama_dosen }}</td>
            </tr>
            <tr>
                <td class="spacer"></td>
                <td class="label">Jabatan</td>
                <td class="separator">:</td>
                <td>{{ $jabatan }}</td>
            </tr>
            <tr>
                <td class="spacer"></td>
                <td class="label">NIDN</td>
                <td class="separator">:</td>
                <td>{{ !empty($nidn) ? $nidn : '-' }}</td>
            </tr>
            <tr>
                <td class="spacer"></td>
                <td class="label">Instansi</td>
                <td class="separator">:</td>
                <td>{{ $instansi }}</td>
            </tr>
        </table>

        <p class="text-justify" style="margin-top: 12px; margin-bottom: 10px;">
            Adalah benar-benar {{ $jabatan }} di {{ $finalNamaInstitusi }} Bangil-Pasuruan.
        </p>

        <p class="text-justify" style="margin-bottom: 24px;">
            Demikian surat ini kami buat dengan sebenarnya, agar dipergunakan sebagaimana mestinya.
        </p>

        <table class="ttd" cellpadding="0" cellspacing="0">
            <tr>
                <td width="54%"></td>
                <td width="46%" style="text-align: left;">
                    <table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 95px; padding: 1px 0;">Ditetapkan di</td>
                            <td style="width: 12px; padding: 1px 0;">:</td>
                            <td style="padding: 1px 0;">{{ $tempat_ditetapkan }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0;">Pada tanggal</td>
                            <td style="padding: 1px 0;">:</td>
                            <td style="padding: 1px 0;">{{ $tanggal_surat }}</td>
                        </tr>
                    </table>

                    <div class="ttd-box">
                        @if(isset($petanda_tangan) && in_array($petanda_tangan, ['ya', 'stempel']))
                            @if(!empty($finalStempel))
                                <img src="{{ $finalStempel }}" style="position: absolute; left: -25px; top: 0px; width: 95px; height: 95px; z-index: 2;" alt="Stempel">
                            @endif
                            @if(!empty($finalTtd) && $petanda_tangan === 'ya')
                                <img src="{{ $finalTtd }}" style="position: absolute; left: 5px; top: 6px; max-width: 170px; max-height: 82px; object-fit: contain; z-index: 1;" alt="Tanda Tangan">
                            @endif
                        @endif
                    </div>

                    <div class="nama-ttd">{{ $finalRektor }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
