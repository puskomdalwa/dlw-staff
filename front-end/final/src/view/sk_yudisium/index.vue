<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch, defineComponent } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { apiDelete, apiGet, apiPdf } from "../../services/api/request";
import SimpleCardComponent from "../../shared/components/@spk/simple-card.vue";
import router from "../../router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

export default defineComponent({
  components: {
    SimpleCardComponent,
  },
  setup() {
    const items = ref<any[]>([]);
    const searchValue = ref("");
    const total = ref(0);
    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "Nomor Surat", value: "nomor_surat", sortable: true },
      { text: "Tanggal", value: "tanggal", sortable: true },
      { text: "Program Studi / Fakultas", value: "prodi_display", sortable: false },
      { text: "Jumlah Mahasiswa", value: "total_mahasiswa", sortable: true },
      { text: "URL Drive", value: "drive_link", sortable: false },
      { text: "Action", value: "action", sortable: false },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "id",
      sortType: "desc",
    });

    const loading = ref(false);
    const listProdi = ref<any[]>([]);
    const prodiFilter = ref("");

    // State untuk Modal Detail Mahasiswa
    const showDetailModal = ref(false);
    const loadingDetail = ref(false);
    const selectedSk = ref<any>(null);
    const mahasiswaList = ref<any[]>([]);

    async function getProdi() {
      try {
        const response = await apiGet(`/get-prodi`);
        if (response.success) {
          const data = response.data?.data;
          listProdi.value = Array.isArray(data) ? data : [data];
        }
      } catch (error) {}
    }

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/sk-yudisium", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
          prodi_id: prodiFilter.value,
        });

        if (response.success) {
          const rows = response.data.data.data || [];
          const startNo =
            (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
          items.value = rows.map((row: any, idx: number) => ({
            ...row,
            no: startNo + idx + 1,
            prodi_display: row.nama_prodi || row.prodi || "SEMUA PRODI",
          }));
          total.value = response.data.data.total || 0;
        }
      } catch (error) {
      } finally {
        loading.value = false;
      }
    }

    async function viewDetail(params: any) {
      selectedSk.value = params;
      showDetailModal.value = true;
      loadingDetail.value = true;
      mahasiswaList.value = [];

      try {
        const res = await apiGet(`/sk-yudisium/${params.id}/mahasiswa`);
        if (res.success && res.data?.status) {
          mahasiswaList.value = res.data.data.mahasiswa || [];
        } else {
          toast.error("Gagal memuat rincian mahasiswa");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat memuat data mahasiswa");
      } finally {
        loadingDetail.value = false;
      }
    }

    function openFileExport(blob: any) {
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank", "noopener");
      setTimeout(() => URL.revokeObjectURL(url), 10_000);
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: `Dokumen SK (${params.nomor_surat || "No Surat"}) beserta seluruh data mahasiswa (${params.total_mahasiswa} orang) di dalamnya akan dihapus!`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, hapus SK ini!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete("/sk-yudisium/" + params.id);
            if (response.success || response.data?.status) {
              toast.success("SK Yudisium berhasil dihapus");
              getData();
              if (showDetailModal.value) {
                showDetailModal.value = false;
              }
            } else {
              toast.error("Data gagal dihapus");
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus data");
          }
        }
      });
    }

    async function download(params: any) {
      Swal.fire({
        title: "Sedang menyiapkan PDF...",
        text: "Mohon tunggu sejenak, dokumen SK Yudisium sedang digenerate.",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      try {
        const res = await apiPdf(
          `/sk-yudisium/download-pdf/${params.id}`,
          {},
          { responseType: "blob" }
        );
        openFileExport(res.data);
        Swal.close();
      } catch (error) {
        Swal.close();
        toast.error("Gagal mengunduh file PDF");
      }
    }

    watch(
      () => serverOptions.value,
      () => {
        getData();
      },
      { deep: true }
    );

    watch(
      () => searchValue.value,
      () => {
        serverOptions.value.page = 1;
        getData();
      }
    );

    watch(
      () => prodiFilter.value,
      () => {
        serverOptions.value.page = 1;
        getData();
      }
    );

    onMounted(() => {
      getProdi();
      getData();
    });

    function goAdd() {
      router.push("/sk-yudisium/add");
    }

    return {
      headers,
      items,
      getData,
      remove,
      download,
      viewDetail,
      total,
      goAdd,
      serverOptions,
      searchValue,
      loading,
      listProdi,
      prodiFilter,
      showDetailModal,
      loadingDetail,
      selectedSk,
      mahasiswaList,
    };
  },
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">
        Surat Keputusan (SK) Yudisium
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Surat</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              SK Yudisium
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <SimpleCardComponent>
      <template #showheader>
        <div class="row g-2 align-items-center w-100 m-0">
          <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
            <button
              class="btn btn-success btn-wave shadow-sm"
              @click="goAdd"
            >
              <i class="ri-file-excel-2-line align-middle me-1"></i> Import SK Yudisium (Excel)
            </button>
          </div>
          <div class="col-12 col-md-auto ms-auto d-flex gap-2 flex-wrap align-items-center">
            <select
              class="form-select form-select-sm"
              style="min-width: 200px"
              v-model="prodiFilter"
            >
              <option value="">Semua Prodi Unit</option>
              <option v-for="item in listProdi" :key="item.id" :value="item.id">
                {{ item.nama }}
              </option>
            </select>
          </div>
        </div>
      </template>

      <div class="row mb-3">
        <div class="col-md-3 ms-auto">
          <div class="input-group">
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="searchValue"
              placeholder="Cari (NIM/Nama/No Surat)..."
            />
            <button class="btn btn-primary btn-sm" type="button">
              <i class="ri-search-line"></i>
            </button>
          </div>
        </div>
      </div>

      <EasyDataTable
        class="table text-nowrap"
        :headers="headers"
        :items="items"
        border-cell
        v-model:server-options="serverOptions"
        :loading="loading"
        :server-items-length="total"
        :rows-items="[10, 25, 50, 100]"
        buttons-pagination
      >
        <template #loading>
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </template>

        <template #item-nomor_surat="item">
          <span class="fw-semibold text-primary">
            {{ item.nomor_surat || '-' }}
          </span>
        </template>

        <template #item-total_mahasiswa="item">
          <span class="badge bg-primary-transparent fs-12 px-2 py-1">
            <i class="ri-user-follow-line me-1"></i>
            {{ item.total_mahasiswa }} Mahasiswa
          </span>
        </template>

        <template #item-drive_link="item">
          <template v-if="item.drive_link">
            <a
              :href="item.drive_link"
              target="_blank"
              class="btn btn-sm btn-success-light"
            >
              <i class="ri-link"></i> Buka Drive
            </a>
          </template>
          <template v-else>
            <span class="badge bg-warning-transparent">
              <i class="ri-time-line me-1"></i> proses upload
            </span>
          </template>
        </template>

        <template #item-action="item">
          <div class="btn-list">
            <button
              class="btn btn-sm btn-info-light btn-wave"
              title="Lihat Daftar Mahasiswa"
              @click="viewDetail(item)"
            >
              <i class="ri-team-line me-1"></i> Mahasiswa
            </button>
            <button
              class="btn btn-sm btn-icon btn-primary-light btn-wave"
              title="Download PDF SK"
              @click="download(item)"
            >
              <i class="ri-download-2-line"></i>
            </button>
            <button
              class="btn btn-sm btn-icon btn-danger-light btn-wave"
              title="Hapus SK"
              @click="remove(item)"
            >
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </template>
      </EasyDataTable>
    </SimpleCardComponent>

    <!-- MODAL DETAIL MAHASISWA DALAM SK -->
    <div
      v-if="showDetailModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background-color: rgba(0, 0, 0, 0.5); z-index: 1055;"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-semibold fs-16">
              <i class="ri-file-list-3-line text-primary me-2 align-middle"></i>
              Rincian Mahasiswa SK Yudisium
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="showDetailModal = false"
            ></button>
          </div>
          <div class="modal-body p-4">
            <div class="card bg-light border-0 mb-3" v-if="selectedSk">
              <div class="card-body py-2 px-3">
                <div class="row gy-2 fs-13">
                  <div class="col-md-6">
                    <span class="text-muted">Nomor Surat:</span>
                    <strong class="d-block text-dark">{{ selectedSk.nomor_surat || '-' }}</strong>
                  </div>
                  <div class="col-md-3">
                    <span class="text-muted">Tanggal:</span>
                    <strong class="d-block text-dark">{{ selectedSk.tanggal || '-' }}</strong>
                  </div>
                  <div class="col-md-3">
                    <span class="text-muted">Total Lulusan:</span>
                    <strong class="d-block text-primary">{{ mahasiswaList.length }} Mahasiswa</strong>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="loadingDetail" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Memuat data...</span>
              </div>
              <p class="text-muted mt-2 mb-0 fs-13">Mengambil data mahasiswa...</p>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Program Studi</th>
                    <th class="text-center" style="width: 120px;">Nilai Skripsi</th>
                    <th class="text-center" style="width: 60px;">L/P</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mhs, idx) in mahasiswaList" :key="mhs.id">
                    <td class="text-center fw-semibold text-muted">{{ idx + 1 }}</td>
                    <td class="fw-semibold text-dark">{{ mhs.nim }}</td>
                    <td>{{ mhs.nama_mhs }}</td>
                    <td>{{ mhs.prodi_nama || mhs.prodi }}</td>
                    <td class="text-center">
                      <span class="badge bg-secondary-transparent">{{ mhs.nilai_skripsi || '-' }}</span>
                    </td>
                    <td class="text-center">
                      <span
                        class="badge"
                        :class="mhs.jenis_kelamin === 'P' ? 'bg-danger-transparent' : 'bg-info-transparent'"
                      >
                        {{ mhs.jenis_kelamin }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="mahasiswaList.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted">
                      Tidak ada mahasiswa yang ditemukan dalam SK ini.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="showDetailModal = false"
            >
              Tutup
            </button>
            <button
              v-if="selectedSk"
              type="button"
              class="btn btn-primary btn-sm"
              @click="download(selectedSk)"
            >
              <i class="ri-download-2-line me-1"></i> Unduh PDF SK Yudisium
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
