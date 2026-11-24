<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>SK Yudisium - {{ $nomor_surat ?? 'Dokumen' }}</title>
    <style>
        /* ===== PAGE SETUP ===== */
        @page {
            margin: 0cm 2cm 0cm 2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #000;
        }

        /* ===== KOP SURAT ===== */
        .kop {
            margin-top: 0cm;
            margin-left: -1.5cm;
            margin-right: -1.5cm;
            margin-bottom: 5px;
        }

        .kop img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ===== FORMATTING ===== */
        .text-center {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .text-bold {
            font-weight: bold;
        }

        .text-italic {
            font-style: italic;
        }

        .text-underline {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 1px 0;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .table-data thead {
            display: table-header-group;
        }

        .table-data tr {
            page-break-inside: avoid;
        }

        .table-data th,
        .table-data td {
            border: 1px solid #000;
            padding: 7px 6px;
            font-size: 10pt;
        }

        .table-data th {
            text-align: center;
            font-weight: bold;
            background-color: #f7f7f7;
        }

        .page-break {
            page-break-after: always;
        }

        .ttd-container {
            position: relative;
            width: 100%;
            height: 60px;
            margin: 2px 0;
        }

        .ttd-section {
            page-break-inside: avoid;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- ========================================== -->
    <!-- HALAMAN 1: SURAT KEPUTUSAN DEKAN           -->
    <!-- ========================================== -->

    <!-- KOP SURAT -->
    <div class="kop">
        @if (!empty($kopBase64))
        <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- JUDUL SURAT -->
    <div class="text-center" style="margin-top: 2px; margin-bottom: 14px;">
        <div class="text-bold" style="font-size: 11pt;">
            SURAT KEPUTUSAN DEKAN FAKULTAS {{ strtoupper($nama_fakultas) }}
        </div>
        <div class="text-bold" style="font-size: 11pt;">
            UNIVERSITAS ISLAM INTERNASIONAL DARULLUGHAH WADDA'WAH PASURUAN
        </div>
        <div style="font-size: 10.5pt; margin-top: 1px;">
            Nomor : {{ $nomor_surat }}
        </div>
        <div style="font-size: 10.5pt; margin-top: 2px;">
            Tentang
        </div>
        <div class="text-bold" style="font-size: 11pt; margin-top: 2px;">
            YUDISIUM MAHASISWA FAKULTAS {{ strtoupper($nama_fakultas) }}
        </div>
        <div class="text-bold" style="font-size: 11pt;">
            UNIVERSITAS ISLAM INTERNASIONAL DARULLUGHAH WADDA'WAH PASURUAN
        </div>
    </div>

    <!-- PEMBUKA -->
    <div class="text-center text-italic" style="margin-bottom: 6px; font-size: 11pt;">
        Bismillaahirrohmaanirrohiim
    </div>

    <div class="text-justify" style="margin-bottom: 8px;">
        Dekan Fakultas {{ ucwords(strtolower($nama_fakultas)) }}, Universitas Islam Internasional Darullughah Wadda'wah Bangil:
    </div>

    <!-- MENIMBANG -->
    <table style="margin-bottom: 6px;">
        <tr>
            <td width="16%">Menimbang</td>
            <td width="3%">:</td>
            <td width="4%">1.</td>
            <td width="77%" class="text-justify">
                Bahwa untuk ketertiban dan kelancaran pelaksanaan Yudisium Mahasiswa Fakultas {{ ucwords(strtolower($nama_fakultas)) }}, Universitas Islam Internasional Darullughah Wadda'wah Bangil, maka perlu diterbitkan Surat Keputusan Dekan tentang Yudisium;
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>2.</td>
            <td class="text-justify">
                Bahwa nama-nama yang disebut dalam lampiran Surat Keputusan ini dinyatakan lulus dan berhak mengikuti agenda Yudisium Fakultas {{ ucwords(strtolower($nama_fakultas)) }} Mahasiswa Tahun {{ $tahun }};
            </td>
        </tr>
    </table>

    <!-- MENGINGAT -->
    <table style="margin-bottom: 10px;">
        <tr>
            <td width="16%">Mengingat</td>
            <td width="3%">:</td>
            <td width="4%">1.</td>
            <td width="77%" class="text-justify">
                Undang-Undang Nomor 20 Tahun 2003 Tentang Sistem Pendidikan Nasional;
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>2.</td>
            <td class="text-justify">
                Undang-undang Nomor 12 Tahun 2012 Tentang Pendidikan Tinggi;
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>3.</td>
            <td class="text-justify">
                Peraturan Pemerintah Nomor 4 Tahun 2014 Tentang Penyelenggaraan Pendidikan Tinggi Dan Pengelolaan Perguruan Tinggi;
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>4.</td>
            <td class="text-justify">
                Rencana Induk Pengembangan (RIP) Universitas Islam Internasional Darullughah Wadda'wah Bangil Pasuruan Jawa Timur;
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>5.</td>
            <td class="text-justify">
                Statuta Universitas Islam Internasional Darullughah Wadda'wah Bangil, Pasuruan, Jawa timur Pasal 35dan 36.
            </td>
        </tr>
    </table>

    <!-- MEMUTUSKAN -->
    <div class="text-center" style="margin-bottom: 8px;">
        <div class="text-bold" style="font-size: 11pt;">MEMUTUSKAN</div>
        <div class="text-bold" style="font-size: 11pt;">
            YUDISIUM MAHASISWA FAKULTAS {{ strtoupper($nama_fakultas) }}
        </div>
        <div class="text-bold" style="font-size: 11pt;">
            UNIVERSITAS ISLAM INTERNASIONAL DARULLUGHAH WADDA'WAH PASURUAN
        </div>
    </div>

    <!-- MENETAPKAN -->
    <table style="margin-bottom: 12px;">
        <tr>
            <td width="16%">Menetapkan</td>
            <td width="3%">:</td>
            <td width="81%"></td>
        </tr>
        <tr>
            <td>Pertama</td>
            <td>:</td>
            <td class="text-justify">
                1. Surat Keputusan Dekan Fakultas {{ ucwords(strtolower($nama_fakultas)) }}, Universitas Islam Internasional Darullughah Wadda'wah Bangil, tentang Yudisium Mahasiswa Fakultas {{ ucwords(strtolower($nama_fakultas)) }}, Universitas Islam Internasional Darullughah Wadda'wah Bangil Tahun {{ $tahun }};
            </td>
        </tr>
        <tr>
            <td>Kedua</td>
            <td>:</td>
            <td class="text-justify">
                2. Yudisium diberikan kepada mahasiswa sebagaimana terlampir;
            </td>
        </tr>
        <tr>
            <td>Ketiga</td>
            <td>:</td>
            <td class="text-justify">
                3. Surat keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari terdapat kekeliruan dalam penetapannya akan diadakan perbaikan sebagaimana mestinya;
            </td>
        </tr>
    </table>

    <!-- AREA TANDA TANGAN & TEMBUSAN -->
    <table width="100%" style="margin-top: 10px;">
        <tr>
            <!-- Kolom Kiri: Tembusan -->
            <td width="55%" style="vertical-align: bottom; font-size: 9.5pt; padding-bottom: 10px;">
                <div class="text-bold" style="margin-bottom: 2px;">Tembusan Yth.</div>
                @if (!empty($tembusan_list) && count($tembusan_list) > 0)
                    @foreach ($tembusan_list as $idx => $item)
                        <div>{{ $idx + 1 }}. {{ $item }}</div>
                    @endforeach
                @elseif (!empty($tembusan_prodis) && count($tembusan_prodis) > 0)
                    @foreach ($tembusan_prodis as $idx => $aliasProdi)
                        @php
                            $text = $aliasProdi;
                            if (stripos($text, 'Ka. Prodi') !== 0 && stripos($text, 'Para Kepala') !== 0) {
                                if ($text === 'AS-HK' || stripos($text, 'Hukum Keluarga') !== false) {
                                    $text = 'HKI';
                                } elseif ($text === 'ESY') {
                                    $text = 'ESy';
                                }
                                $text = 'Ka. Prodi ' . $text;
                            }
                        @endphp
                        <div>{{ $idx + 1 }}. {{ $text }}</div>
                    @endforeach
                    @if (!collect($tembusan_prodis)->contains(fn($i) => stripos($i, 'Biro') !== false))
                        <div>{{ count($tembusan_prodis) + 1 }}. Para Kepala Biro</div>
                    @endif
                @else
                    @php
                        $fakultasNameUpper = strtoupper($nama_fakultas ?? '');
                    @endphp
                    @if (str_contains($fakultasNameUpper, 'TARBIYAH'))
                        <div>1. Ka. Prodi PAI</div>
                        <div>2. Ka. Prodi MPI</div>
                        <div>3. Ka. Prodi PBA</div>
                        <div>4. Para Kepala Biro</div>
                    @elseif (str_contains($fakultasNameUpper, 'DAKWAH'))
                        <div>1. Ka. Prodi KPI</div>
                        <div>2. Ka. Prodi BKI</div>
                        <div>3. Ka. Prodi MHU</div>
                        <div>4. Para Kepala Biro</div>
                    @elseif (str_contains($fakultasNameUpper, 'ADAB'))
                        <div>1. Ka. Prodi SPI</div>
                        <div>2. Para Kepala Biro</div>
                    @else
                        <div>1. Ka. Prodi HKI</div>
                        <div>2. Ka. Prodi ESy</div>
                        <div>3. Para Kepala Biro</div>
                    @endif
                @endif
            </td>

            <!-- Kolom Kanan: Tanda Tangan Dekan -->
            <td width="45%" class="text-center" style="vertical-align: top;">
                <div>Bangil, {{ $tanggal }}</div>
                <div style="margin-bottom: 2px;">Dekan Fakultas {{ ucwords(strtolower($nama_fakultas)) }}</div>

                <div class="ttd-container">
                    @if (!empty($stempel) && in_array($petanda_tangan, ['ya', 'stempel']))
                        <img src="{{ $stempel }}"
                            style="position: absolute; left: 10px; top: -15px; width: 90px; height: 90px; z-index: 2;" />
                    @endif

                    @if (!empty($ttd) && $petanda_tangan === 'ya')
                        <img src="{{ $ttd }}"
                            style="position: absolute; left: 45px; top: -10px; max-width: 140px; max-height: 80px; z-index: 1;" />
                    @endif
                </div>

                @php
    $finalKepala = !empty($ketua) ? $ketua : (!empty($nama_dekan) ? $nama_dekan : null);
    $finalNidn   = !empty($nidn) ? $nidn : (!empty($nidn_dekan) ? $nidn_dekan : null);
    if (empty($finalKepala) || empty($finalNidn)) {
        $fallback = \App\Services\SuratService::resolveSigner('SKY', [
            'ketua' => $finalKepala,
            'nidn'  => $finalNidn,
        ]);
        if (empty($finalKepala)) $finalKepala = $fallback['ketua'] ?? '';
        if (empty($finalNidn)) $finalNidn = $fallback['nidn'] ?? '';
    }
    $finalNidn = \App\Services\DosenService::resolveNidn($finalNidn, $finalKepala);
@endphp
                    <div class="text-bold text-underline">{{ $finalKepala }}</div>
                    @if(!empty($finalNidn))
                    <div>NIDN. {{ $finalNidn }}</div>
                    @endif
            </td>
        </tr>
    </table>

    <!-- ========================================== -->
    <!-- HALAMAN 2+: LAMPIRAN I DOKUMEN             -->
    <!-- ========================================== -->
    <div class="page-break"></div>

    <!-- HEADER LAMPIRAN -->
    <table style="width: 100%; margin-top: 15px; margin-bottom: 20px; font-size: 10.5pt;">
        <tr>
            <td width="15%">Lampiran I</td>
            <td width="3%">:</td>
            <td width="82%">Surat Keputusan Dekan Fakultas {{ ucwords(strtolower($nama_fakultas)) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>Universitas Islam Internasional Darullughah Wadda'wah</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>Nomor : {{ $nomor_surat }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>Tanggal : {{ $tanggal }}</td>
        </tr>
    </table>

    <!-- JUDUL TABEL LAMPIRAN -->
    <div class="text-center text-bold" style="font-size: 11pt; margin-bottom: 18px; line-height: 1.4;">
        YUDISIUM MAHASISWA FAKULTAS {{ strtoupper($nama_fakultas) }} UNIVERSITAS ISLAM<br>
        INTERNASIONAL DARULLUGHAH WADDA'WAH
    </div>

    <!-- TABEL DATA MAHASISWA -->
    <table class="table-data">
        <thead>
            <tr>
                <th width="7%">NO</th>
                <th width="23%">NIM</th>
                <th width="35%">NAMA</th>
                <th width="23%">PROGRAM STUDI</th>
                <th width="12%">Nilai skripsi</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($mahasiswas) && count($mahasiswas) > 0)
            @foreach ($mahasiswas as $index => $mhs)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $mhs->nim }}</td>
                <td style="padding-left: 8px;">{{ strtoupper($mhs->nama_mhs) }}</td>
                <td class="text-center">{{ strtoupper($mhs->prodi_nama ?? $mhs->prodi) }}</td>
                <td class="text-center">{{ $mhs->nilai_skripsi ?: '-' }}</td>
            </tr>
            @endforeach
            @else
            <tr>
                <td class="text-center">1</td>
                <td class="text-center">{{ $nim }}</td>
                <td style="padding-left: 8px;">{{ strtoupper($nama_mhs) }}</td>
                <td class="text-center">{{ strtoupper($prodi) }}</td>
                <td class="text-center">{{ $nilai_skripsi ?: '-' }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- TANDA TANGAN DI LAMPIRAN -->
    <div class="ttd-section">
        <table width="100%">
            <tr>
                <td width="55%"></td>
                <td width="45%" class="text-center">
                    <div>Bangil, {{ $tanggal }}</div>
                    <div style="margin-bottom: 2px;">Dekan Fakultas {{ ucwords(strtolower($nama_fakultas)) }}</div>

                    <div class="ttd-container">
                        @if (!empty($stempel) && in_array($petanda_tangan, ['ya', 'stempel']))
                            <img src="{{ $stempel }}"
                                style="position: absolute; left: 10px; top: -15px; width: 90px; height: 90px; z-index: 2;" />
                        @endif

                        @if (!empty($ttd) && $petanda_tangan === 'ya')
                            <img src="{{ $ttd }}"
                                style="position: absolute; left: 45px; top: -10px; max-width: 140px; max-height: 80px; z-index: 1;" />
                        @endif
                    </div>

                    @php
    $finalKepala = !empty($ketua) ? $ketua : (!empty($nama_dekan) ? $nama_dekan : null);
    $finalNidn   = !empty($nidn) ? $nidn : (!empty($nidn_dekan) ? $nidn_dekan : null);
    if (empty($finalKepala) || empty($finalNidn)) {
        $fallback = \App\Services\SuratService::resolveSigner('SKY', [
            'ketua' => $finalKepala,
            'nidn'  => $finalNidn,
        ]);
        if (empty($finalKepala)) $finalKepala = $fallback['ketua'] ?? '';
        if (empty($finalNidn)) $finalNidn = $fallback['nidn'] ?? '';
    }
    $finalNidn = \App\Services\DosenService::resolveNidn($finalNidn, $finalKepala);
@endphp
                    <div class="text-bold text-underline">{{ $finalKepala }}</div>
                    @if(!empty($finalNidn))
                    <div>NIDN. {{ $finalNidn }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

</body>

</html>