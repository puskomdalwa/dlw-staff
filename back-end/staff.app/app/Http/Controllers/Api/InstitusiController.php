<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InstitusiController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Institusi index:', $request->all());

        $data = Institusi::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('nama_institusi', 'LIKE', "%{$search}%")
                  ->orWhere('rektor', 'LIKE', "%{$search}%");
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        if ($request->has('all') && filter_var($request->input('all'), FILTER_VALIDATE_BOOLEAN)) {
            $result = $data->get();
        } else {
            $result = $data->paginate($request->input('limit', 10));
        }

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Institusi store:', $request->except(['kop_surat']));

        try {
            $validator = Validator::make($request->all(), [
                'nama_institusi' => 'required|string|max:255',
                'rektor'         => 'nullable|string|max:255',
                'kop_surat'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $institusi = new Institusi();
            $institusi->nama_institusi = $validate['nama_institusi'];
            $institusi->rektor         = $validate['rektor'] ?? null;

            if ($request->hasFile('kop_surat')) {
                $dir = public_path('img/kop_surat');
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                $file = $request->file('kop_surat');
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move($dir, $filename);
                $institusi->kop_surat = 'img/kop_surat/' . $filename;
            }

            $institusi->save();

            return response()->json([
                'status'  => true,
                'data'    => $institusi,
                'message' => 'Data berhasil ditambahkan'
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $institusi = Institusi::find($id);

        if (!$institusi) {
            return response()->json([
                'status'  => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'data'    => $institusi,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info("Institusi update [{$id}]:", $request->except(['kop_surat']));

        try {
            $institusi = Institusi::find($id);

            if (!$institusi) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_institusi' => 'required|string|max:255',
                'rektor'         => 'nullable|string|max:255',
                'kop_surat'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $institusi->nama_institusi = $validate['nama_institusi'];
            $institusi->rektor         = $validate['rektor'] ?? null;

            if ($request->hasFile('kop_surat')) {
                if ($institusi->kop_surat && File::exists(public_path($institusi->kop_surat))) {
                    @File::delete(public_path($institusi->kop_surat));
                }

                $dir = public_path('img/kop_surat');
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                $file = $request->file('kop_surat');
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move($dir, $filename);
                $institusi->kop_surat = 'img/kop_surat/' . $filename;
            }

            $institusi->save();

            return response()->json([
                'status'  => true,
                'data'    => $institusi,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $institusi = Institusi::find($id);

            if (!$institusi) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($institusi->kop_surat && File::exists(public_path($institusi->kop_surat))) {
                @File::delete(public_path($institusi->kop_surat));
            }

            $institusi->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal dihapus: ' . $th->getMessage()
            ], 500);
        }
    }
}
