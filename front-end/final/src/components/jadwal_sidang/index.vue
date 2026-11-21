<script lang="ts" setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { apiGet } from "../../services/api/request";

const props = defineProps({
  modelValue: Object,
  isEdit: Boolean,
  errors: {
    type: Object,
    default: () => ({}),
  },
  btnLoading: {
    type: Boolean,
    default: false,
  },
});

const form = reactive({
  id: "" as string | number,
  tgl_sidang: new Date().toISOString().slice(0, 10),
  pengajuan_judul_ids: [] as number[],
});

const listMahasiswa = ref<any[]>([]);
const listProdi = ref<any[]>([]);
const prodiFilter = ref("");
const searchKeyword = ref("");
const loadingMahasiswa = ref(false);
const isLoadingData = ref(false);

const filteredMahasiswa = computed(() => {
  const kw = searchKeyword.value.trim().toLowerCase();
  if (!kw) return listMahasiswa.value;
  return listMahasiswa.value.filter((item: any) => {
    return (
      String(item.nim || "").toLowerCase().includes(kw) ||
      String(item.nama_mhs || "").toLowerCase().includes(kw) ||
      String(item.nama_prodi || "").toLowerCase().includes(kw) ||
      String(item.dosen_penasehat || "").toLowerCase().includes(kw) ||
      String(item.judul_proposal_skripsi || "").toLowerCase().includes(kw)
    );
  });
});

const allVisibleChecked = computed(() => {
  if (filteredMahasiswa.value.length === 0) return false;
  return filteredMahasiswa.value.every((item: any) =>
    form.pengajuan_judul_ids.includes(Number(item.id))
  );
});

function isChecked(id: number | string): boolean {
  return form.pengajuan_judul_ids.includes(Number(id));
}

function toggleItem(id: number | string) {
  const numId = Number(id);
  const idx = form.pengajuan_judul_ids.indexOf(numId);
  if (idx === -1) {
    form.pengajuan_judul_ids.push(numId);
  } else {
    form.pengajuan_judul_ids.splice(idx, 1);
  }
}

function toggleAllVisible(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  const visibleIds = filteredMahasiswa.value.map((item: any) => Number(item.id));
  if (checked) {
    const merged = new Set([...form.pengajuan_judul_ids, ...visibleIds]);
    form.pengajuan_judul_ids = Array.from(merged);
  } else {
    const visibleSet = new Set(visibleIds);
    form.pengajuan_judul_ids = form.pengajuan_judul_ids.filter(
      (id) => !visibleSet.has(id)
    );
  }
}

async function getProdi() {
  try {
    const response = await apiGet("/get-prodi");
    if (response.success) {
      const rawData = response.data?.data ?? response.data;
      listProdi.value = Array.isArray(rawData)
        ? rawData
        : rawData
          ? [rawData]
          : [];
    }
  } catch (error) {}
}

async function getMahasiswa() {
  try {
    loadingMahasiswa.value = true;
    const response = await apiGet("/jadwal-sidang/mahasiswa", {
      prodi_id: prodiFilter.value,
    });
    if (response.success) {
      listMahasiswa.value = response.data?.data || [];
    }
  } catch (error) {
  } finally {
    loadingMahasiswa.value = false;
  }
}

watch(prodiFilter, () => {
  getMahasiswa();
});

watch(
  () => props.modelValue,
  async (val) => {
    if (!props.isEdit) return;
    if (!val || !val.tgl_sidang) {
      isLoadingData.value = true;
      return;
    }

    isLoadingData.value = true;
    await new Promise((resolve) => setTimeout(resolve, 150));

    form.id = val.id ?? "";
    form.tgl_sidang = String(val.tgl_sidang).slice(0, 10);

    const existingIds = Array.isArray(val.mahasiswa_sidang)
      ? val.mahasiswa_sidang
          .map((ms: any) => Number(ms.pengajuan_judul_id))
          .filter((id: number) => !isNaN(id))
      : [];
    form.pengajuan_judul_ids = Array.from(new Set(existingIds));

    isLoadingData.value = false;
  },
  { immediate: true }
);

onMounted(() => {
  getProdi();
  getMahasiswa();
});

const emit = defineEmits(["submit"]);

function submitForm() {
  emit("submit", { ...form });
}
</script>

<template>
  <div class="row">
    <form @submit.prevent="submitForm">
      <div class="col-xl-12">
        <div class="card custom-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">
              {{ isEdit ? "Edit" : "Tambah" }} Jadwal Sidang
            </div>
            <span class="badge bg-primary-transparent fs-12">
              {{ form.pengajuan_judul_ids.length }} Mahasiswa Dipilih
            </span>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-4">
                <label class="form-label">
                  Tanggal Sidang <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="date"
                  v-model="form.tgl_sidang"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.tgl_sidang }"
                  required
                />
                <div v-if="errors?.tgl_sidang" class="invalid-feedback">
                  {{ errors.tgl_sidang[0] }}
                </div>
              </div>

              <div class="col-xl-4" v-if="listProdi.length > 1">
                <label class="form-label">Filter Program Studi :</label>
                <select class="form-select" v-model="prodiFilter">
                  <option value="">Semua Program Studi</option>
                  <option v-for="p in listProdi" :key="p.id" :value="p.id">
                    {{ p.nama }}
                  </option>
                </select>
              </div>

              <div class="col-xl-4" v-else-if="listProdi.length === 1">
                <label class="form-label">Program Studi :</label>
                <input
                  type="text"
                  class="form-control"
                  :value="listProdi[0].nama"
                  readonly
                  disabled
                />
              </div>

              <div class="col-xl-4 ms-auto">
                <label class="form-label">Cari Mahasiswa :</label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    v-model="searchKeyword"
                    placeholder="Cari NIM / Nama / Judul..."
                  />
                  <span class="input-group-text">
                    <i class="ri-search-line"></i>
                  </span>
                </div>
              </div>

              <div class="col-xl-12 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label fw-semibold mb-0">
                    Daftar Mahasiswa (Pengajuan Judul) — Centang Mahasiswa yang Akan Sidang :
                  </label>
                  <small class="text-muted">
                    Menampilkan {{ filteredMahasiswa.length }} dari {{ listMahasiswa.length }} data
                  </small>
                </div>

                <div v-if="loadingMahasiswa || isLoadingData" class="text-center py-4 border rounded">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>

                <div v-else-if="filteredMahasiswa.length === 0" class="text-center text-muted py-4 border rounded">
                  Tidak ada data mahasiswa pengajuan judul yang tersedia.
                </div>

                <div v-else class="table-responsive border rounded" style="max-height: 460px; overflow-y: auto">
                  <table class="table table-hover table-bordered table-sm align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 1">
                      <tr>
                        <th class="text-center" style="width: 45px">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :checked="allVisibleChecked"
                            @change="toggleAllVisible"
                            title="Pilih Semua yang Tampil"
                          />
                        </th>
                        <th style="width: 45px">No</th>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Dosen Penasehat</th>
                        <th>Judul Proposal Skripsi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(mhs, idx) in filteredMahasiswa"
                        :key="mhs.id"
                        :class="{ 'table-primary': isChecked(mhs.id) }"
                        style="cursor: pointer"
                        @click="toggleItem(mhs.id)"
                      >
                        <td class="text-center" @click.stop>
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :checked="isChecked(mhs.id)"
                            @change="toggleItem(mhs.id)"
                          />
                        </td>
                        <td>{{ idx + 1 }}</td>
                        <td class="text-nowrap">{{ mhs.nim }}</td>
                        <td class="fw-medium">{{ mhs.nama_mhs }}</td>
                        <td>{{ mhs.nama_prodi || "-" }}</td>
                        <td>{{ mhs.dosen_penasehat || "-" }}</td>
                        <td style="min-width: 240px">{{ mhs.judul_proposal_skripsi }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-if="errors?.pengajuan_judul_ids" class="text-danger small mt-1">
                  {{ errors.pengajuan_judul_ids[0] }}
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex justify-content-between">
            <router-link to="/jadwal-sidang" class="btn btn-secondary-light btn-wave">
              Batal
            </router-link>
            <button class="btn btn-primary-light btn-wave" :disabled="btnLoading">
              <span
                v-if="btnLoading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              {{ btnLoading ? (isEdit ? "Mengupdate..." : "Menyimpan...") : isEdit ? "Update" : "Simpan" }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.skeleton-input {
  height: 38px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 5px;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
