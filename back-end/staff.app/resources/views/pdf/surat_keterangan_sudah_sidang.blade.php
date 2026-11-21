<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan</title>

    <style>
        @page {
            margin: 0cm 2cm 1cm 2cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .judul {
            font-weight: bold;
            font-size: 13pt;
            text-decoration: underline;
            margin-top: 15px;
            letter-spacing: 0.5px;
        }

        .nomor {
            font-size: 11pt;
            margin-top: 4px;
            margin-bottom: 25px;
        }

        .paragraf {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 25px;
        }

        table.data td {
            padding: 3px 0;
            vertical-align: top;
        }

        table.data td.label {
            width: 80px;
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
            margin-top: 5px;
        }

        .kop img {
            width: 100%;
            height: auto;
            display: block;
        }

        .kop {
            margin-top: 0.1cm;
            margin-left: -1.5cm;
            margin-right: -1.5cm;
        }
    </style>
</head>

<body>

    <!-- KOP SURAT -->
    <div class="kop">
        @if(!empty($kopBase64))
        <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- JUDUL & NOMOR SURAT -->
    <div class="text-center">
        <div class="judul">SURAT KETERANGAN</div>
        <div class="nomor">
            {{ $nomor_surat }}
        </div>
    </div>

    <div class="paragraf">
        Menerangkan bahwa :
    </div>

    <!-- DATA MAHASISWA -->
    <table class="data">
        <tr>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td class="value">{{ $nama ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td class="separator">:</td>
            <td class="value">{{ $nim ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Prodi</td>
            <td class="separator">:</td>
            <td class="value">{{ $prodi ?? '' }}</td>
        </tr>
    </table>

    <!-- KETERANGAN LULUS SIDANG -->
    <div class="paragraf">
        Mahasiswa tersebut <strong>telah mengikuti ujian skripsi</strong> pada {{ $tanggal_sidang_formatted }} dan dinyatakan <strong>LULUS</strong>.
    </div>

    <div class="paragraf" style="margin-bottom: 40px;">
        Demikian surat keterangan ini dibuat, untuk dapat digunakan sebagai syarat mendaftar di Program Magister Pascasarjana UII Dalwa dan keperluan lainnya.
    </div>

    <!-- PENANDATANGAN (BAAK) -->
    <table class="ttd" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%"></td>
            <td width="45%" style="text-align: left; padding-left: 20px;">
                Pasuruan, {{ $tanggal_surat_formatted }}<br>
                Kepala BAAK<br>

                <!-- AREA QR CODE / TTD & STEMPEL -->
                @if(isset($petanda_tangan) && in_array($petanda_tangan, ['ya', 'stempel']))
                    <div style="position: relative; width: 100%; height: 110px; margin-top: 5px; margin-bottom: 5px;">
                        @if(!empty($stempel) && in_array($petanda_tangan, ['ya', 'stempel']))
                            <img src="{{ $stempel }}" style="position: absolute; left: -25px; top: 5px; width: 95px; height: 95px; z-index: 2;" alt="Stempel">
                        @endif

                        @if(!empty($qrCode) && $petanda_tangan == 'ya')
                            <img src="{{ $qrCode }}" style="position: absolute; left: 0px; top: 5px; width: 95px; height: 95px; z-index: 1;" alt="QR Code">
                        @elseif(!empty($ttd) && $petanda_tangan == 'ya')
                            <img src="{{ $ttd }}" style="position: absolute; left: -10px; top: 15px; max-width: 150px; max-height: 80px; object-fit: contain; z-index: 1;" alt="Tanda Tangan">
                        @endif
                    </div>
                @else
                    <div style="height: 100px;"></div>
                @endif

                <div class="nama-ttd">
                    {{ $kepala ?? 'Kuzairi, M.Pd' }}
                </div>
                @if(!empty($nidn))
                <div style="margin-top: 2px;">NIY. {{ $nidn }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>

</html>

