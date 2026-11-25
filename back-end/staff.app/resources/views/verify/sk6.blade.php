<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Tanda Tangan QR SK 6</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3f6f9;
            color: #1f2937;
            margin: 0;
            padding: 24px 16px;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            padding: 20px 24px;
            color: #fff;
            background: {{ ($signature && $signature->status === 'approved') ? '#15803d' : '#b91c1c' }};
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .body {
            padding: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        td {
            padding: 8px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 14px;
        }
        td.label {
            width: 170px;
            color: #6b7280;
            font-weight: 600;
        }
        .timeline-title {
            font-size: 14px;
            font-weight: 700;
            margin: 16px 0 10px;
            color: #374151;
        }
        .step {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .step.active {
            border-color: #86efac;
            background: #f0fdf4;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-ok {
            background: #dcfce7;
            color: #166534;
        }
        .badge-wait {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            @if($signature && $signature->status === 'approved')
                <h1>Tanda Tangan QR Terverifikasi</h1>
                <p>Dokumen SK 6 UII Darullughah Wadda'wah</p>
            @else
                <h1>Tanda Tangan Tidak Valid / Belum Dikonfirmasi</h1>
                <p>Token verifikasi tidak ditemukan atau masih berstatus pending.</p>
            @endif
        </div>
        <div class="body">
            @if($signature && $signature->status === 'approved' && $sk6)
                <table>
                    <tr>
                        <td class="label">Nama Mahasiswa</td>
                        <td>{{ $sk6->nama_mhs }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIM</td>
                        <td>{{ $sk6->nim }}</td>
                    </tr>
                    <tr>
                        <td class="label">Program Studi</td>
                        <td>{{ $sk6->prodi->nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan / Title</td>
                        <td><strong>{{ $signature->title }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Penandatangan</td>
                        <td>{{ $signature->signer_name ?: '-' }}</td>
                    </tr>
                    @if(!empty($signature->signer_nidn))
                    <tr>
                        <td class="label">NIDN / NIY</td>
                        <td>{{ $signature->signer_nidn }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Waktu Konfirmasi</td>
                        <td>{{ $signature->signed_at ? $signature->signed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H:i') . ' WIB' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Sumber Konfirmasi</td>
                        <td>{{ $signature->signed_by_source ?? '-' }}</td>
                    </tr>
                </table>

                @if(!empty($timeline))
                    <div class="timeline-title">Status Timeline TTD SK 6</div>
                    @foreach($timeline as $item)
                        <div class="step {{ $item['is_signed'] ? 'active' : '' }}">
                            <div>
                                <strong>{{ $item['step_order'] }}. {{ $item['step_key'] === 'baku_aupk' ? 'BAKU' : $item['title'] }}</strong><br>
                                <span style="color:#6b7280;">{{ $item['signer_name'] ?: '-' }}</span>
                            </div>
                            <span class="badge {{ $item['is_signed'] ? 'badge-ok' : 'badge-wait' }}">
                                {{ $item['is_signed'] ? 'Terkonfirmasi' : 'Menunggu' }}
                            </span>
                        </div>
                    @endforeach
                @endif
            @else
                <p style="font-size:14px;color:#4b5563;">
                    Pastikan QR Code berasal dari dokumen SK 6 resmi dan sudah dikonfirmasi oleh pejabat terkait.
                </p>
            @endif
        </div>
    </div>
</body>
</html>
