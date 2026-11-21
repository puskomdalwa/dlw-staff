<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriBerkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KategoriBerkasController extends Controller
{
    public function index(Request $request)
    {
        Log::info($request->all());

        $data = KategoriBerkas::query()
            ->withCount('berkas');

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('nama_kategori', 'LIKE', "%{$search}%");
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        if ($request->has('all') && filter_var($request->input('all'), FILTER_VALIDATE_BOOLEAN)) {
            $data = $data->get();
        } else {
            $data = $data->paginate($request->input('limit', 10));
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        Log::info($request->all());

        try {
            $validator = Validator::make($request->all(), [
                'nama_kategori' => 'required|string|max:255|unique:kategori_berkas,nama_kategori',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $kategoriBerkas = new KategoriBerkas();
            $kategoriBerkas->nama_kategori = $validate['nama_kategori'];
            $kategoriBerkas->save();

            return response()->json([
                'status' => true,
                'data' => $kategoriBerkas,
                'message' => 'Data berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $kategoriBerkas = KategoriBerkas::withCount('berkas')->find($id);

        if (!$kategoriBerkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $kategoriBerkas,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info($request->all());

        try {
            $kategoriBerkas = KategoriBerkas::find($id);

            if (!$kategoriBerkas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_kategori' => 'required|string|max:255|unique:kategori_berkas,nama_kategori,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $kategoriBerkas->nama_kategori = $validate['nama_kategori'];
            $kategoriBerkas->save();

            return response()->json([
                'status' => true,
                'data' => $kategoriBerkas,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kategoriBerkas = KategoriBerkas::find($id);

            if (!$kategoriBerkas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($kategoriBerkas->berkas()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kategori berkas tidak dapat dihapus karena masih memiliki relasi data berkas'
                ], 400);
            }

            $kategoriBerkas->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal dihapus: ' . $th->getMessage()
            ], 500);
        }
    }
}
