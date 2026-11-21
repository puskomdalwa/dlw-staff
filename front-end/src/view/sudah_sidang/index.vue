<script lang="ts">
import { computed, defineComponent, onMounted, ref, watch } from "vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPost } from "../../services/api/request";
import SimpleCardComponent from "../../shared/components/@spk/simple-card.vue";

export default defineComponent({
  components: {
    SimpleCardComponent,
  },
  setup() {
    const listMahasiswa = ref<any[]>([]);
    const listProdi = ref<any[]>([]);
    const prodiFilter = ref("");
    const statusFilter = ref("");
    const searchKeyword = ref("");
    const loading = ref(false);
    const saving = ref(false);
    const checkedIds = ref<number[]>([]);

    const filteredMahasiswa = computed(() => {
      const kw = searchKeyword.value.trim().toLowerCase();
      return listMahasiswa.value.filter((item: any) => {
        if (statusFilter.value && item.status_sidang !== statusFilter.value) {
          return false;
        }
        if (!kw) return true;
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
        checkedIds.value.includes(Number(item.id))
      );
    });

    function isChecked(id: number | string): boolean {
      return checkedIds.value.includes(Number(id));
    }

    function toggleItem(id: number | string) {
      const numId = Number(id);
      const idx = checkedIds.value.indexOf(numId);
      if (idx === -1) {
        checkedIds.value.push(numId);
      } else {
        checkedIds.value.splice(idx, 1);
      }
    }

    function toggleAllVisible(event: Event) {
      const checked = (event.target as HTMLInputElement).checked;
      const visibleIds = filteredMahasiswa.value.map((item: any) => Number(item.id));
      if (checked) {
        checkedIds.value = Array.from(new Set([...checkedIds.value, ...visibleIds]));
      } else {
        const visibleSet = new Set(visibleIds);
        checkedIds.value = checkedIds.value.filter((id) => !visibleSet.has(id));
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

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/pengajuan-judul/sudah-sidang", {
          prodi_id: prodiFilter.value,
        });
        if (response.success) {
          const rows = response.data?.data || [];
          listMahasiswa.value = rows;
          checkedIds.value = rows
            .filter((row: any) => row.status_sidang === "sudah")
            .map((row: any) => Number(row.id));
        }
      } catch (error) {
        toast.error("Gagal mengambil data mahasiswa pengajuan judul");
      } finally {
        loading.value = false;
      }
    }

    async function saveStatusSidang() {
      try {
        saving.value = true;
        const displayedIds = listMahasiswa.value.map((item: any) => Number(item.id));
        const response = await apiPost("/pengajuan-judul/sudah-sidang", {
          checked_ids: checkedIds.value,
          displayed_ids: displayedIds,
        });

        if (response.success && response.data?.status !== false) {
          toast.success("Status sidang mahasiswa berhasil disimpan", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          await getData();
        } else {
          const errMsg =
            (response as any).error?.response?.data?.message ||
            response.data?.message ||
            "Gagal menyimpan status sidang";
          toast.error(errMsg);
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menyimpan status sidang");
      } finally {
        saving.value = false;
      }
    }

    watch(prodiFilter, () => {
      getData();
    });

    onMounted(() => {
      getProdi();
      getData();
    });

    return {
      listMahasiswa,
      listProdi,
      prodiFilter,
      statusFilter,
      searchKeyword,
      loading,
      saving,
      checkedIds,
      filteredMahasiswa,
      allVisibleChecked,
      isChecked,
      toggleItem,
      toggleAllVisible,
      getData,
      saveStatusSidang,
    };
  },
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Status Sidang</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Skripsi</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Status Sidang
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <SimpleCardComponent>
      <template #showheader>
        <div class="row g-2 align-items-center w-100 m-0">
          <div class="col-12 col-md-auto">
            <button
              class="btn btn-primary btn-wave shadow-sm w-100"
              :disabled="saving || loading"
              @click="saveStatusSidang"
            >
              <span
                v-if="saving"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              <i v-else class="ri-save-line align-middle me-1"></i>
              {{ saving ? "Menyimpan..." : "Simpan Status Sidang" }}
            </button>
          </div>
          <div class="col-12 col-md-auto">
            <span class="badge bg-success-transparent fs-12 px-3 py-2">
              {{ checkedIds.length }} Mahasiswa Sudah Sidang
            </span>
          </div>
          <div class="col-12 col-md-auto ms-auto d-flex flex-wrap gap-2">
            <select
              v-if="listProdi.length > 1"
              class="form-select form-select-sm"
              style="min-width: 190px; width: auto"
              v-model="prodiFilter"
            >
              <option value="">Semua Prodi Unit</option>
              <option v-for="p in listProdi" :key="p.id" :value="p.id">
                {{ p.nama }}
              </option>
            </select>
            <select
              class="form-select form-select-sm"
              style="min-width: 150px; width: auto"
              v-model="statusFilter"
            >
              <option value="">Semua Status</option>
              <option value="belum">Belum Sidang</option>
              <option value="sudah">Sudah Sidang</option>
            </select>
          </div>
        </div>
      </template>

      <div class="row mb-3 align-items-center">
        <div class="col-md-7 text-muted fs-12">
          Centang checkbox pada mahasiswa yang sudah melaksanakan sidang, lalu klik tombol <strong>Simpan Status Sidang</strong>.
        </div>
        <div class="col-md-5 col-lg-3 ms-auto mt-2 mt-md-0">
          <div class="input-group">
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="searchKeyword"
              placeholder="Cari (NIM/Nama/Judul)..."
            />
            <span class="input-group-text">
              <i class="ri-search-line"></i>
            </span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div
        v-else-if="filteredMahasiswa.length === 0"
        class="text-center text-muted py-5 border rounded"
      >
        Tidak ada data mahasiswa pengajuan judul yang ditemukan.
      </div>

      <div v-else class="table-responsive border rounded">
        <table class="table table-hover table-bordered table-sm align-middle mb-0">
          <thead class="table-light">
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
              <th class="text-center" style="width: 110px">Status Sidang</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in filteredMahasiswa"
              :key="item.id"
              :class="{ 'table-success': isChecked(item.id) }"
              style="cursor: pointer"
              @click="toggleItem(item.id)"
            >
              <td class="text-center" @click.stop>
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="isChecked(item.id)"
                  @change="toggleItem(item.id)"
                />
              </td>
              <td>{{ idx + 1 }}</td>
              <td class="text-nowrap">{{ item.nim }}</td>
              <td class="fw-medium">{{ item.nama_mhs }}</td>
              <td>{{ item.nama_prodi || "-" }}</td>
              <td>{{ item.dosen_penasehat || "-" }}</td>
              <td style="min-width: 240px">{{ item.judul_proposal_skripsi }}</td>
              <td class="text-center">
                <span
                  v-if="isChecked(item.id)"
                  class="badge bg-success-transparent"
                >
                  Sudah
                </span>
                <span v-else class="badge bg-warning-transparent">
                  Belum
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end mt-3" v-if="filteredMahasiswa.length > 0">
        <button
          class="btn btn-primary btn-wave shadow-sm"
          :disabled="saving || loading"
          @click="saveStatusSidang"
        >
          <span
            v-if="saving"
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          <i v-else class="ri-save-line align-middle me-1"></i>
          {{ saving ? "Menyimpan..." : "Simpan Status Sidang" }}
        </button>
      </div>
    </SimpleCardComponent>
  </div>
</template>
