<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch, defineComponent } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { apiDelete, apiGet, apiPdf, apiPost } from "../../services/api/request";
import SimpleCardComponent from "../../shared/components/@spk/simple-card.vue";
import router from "../../router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { Modal } from "bootstrap";

export default defineComponent({
  components: {
    SimpleCardComponent,
  },
  setup() {
    const items = ref([]);
    const searchValue = ref("");
    const total = ref(0);
    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "NIM", value: "nim", sortable: true },
      { text: "Nama Mahasiswa", value: "nama_mhs", sortable: true },
      { text: "Prodi", value: "prodi", sortable: true },
      { text: "Dosen Penasehat", value: "dosen_penasehat", sortable: true },
      { text: "Judul Proposal Skripsi", value: "judul_proposal_skripsi", sortable: true },
      { text: "URL", value: "drive_link", sortable: false },
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

    // Setting Form Publik State
    const settingModalRef = ref<HTMLElement | null>(null);
    let settingModal: Modal | null = null;
    const loadingSetting = ref(false);
    const savingSetting = ref(false);
    const publicSetting = ref<any>({
      is_active: true,
      slug: "pengajuan-judul",
      title: "Formulir Pengajuan Judul Skripsi",
      description: "",
      duration_type: "days",
      duration_value: 7,
      custom_expired_at: "",
      expired_at: null,
      total_submissions: 0,
      is_available: true,
      remaining_text: "",
    });

    async function getPublicSetting() {
      try {
        loadingSetting.value = true;
        const res = await apiGet("/pengajuan-judul/public-setting");
        if (res.success && res.data?.status) {
          publicSetting.value = {
            ...publicSetting.value,
            ...res.data.data,
          };
        }
      } catch (error) {
      } finally {
        loadingSetting.value = false;
      }
    }

    function openSettingModal() {
      if (settingModalRef.value) {
        if (!settingModal) {
          settingModal = new Modal(settingModalRef.value);
        }
        getPublicSetting();
        settingModal.show();
      }
    }

    function closeSettingModal() {
      settingModal?.hide();
    }

    async function savePublicSetting() {
      try {
        savingSetting.value = true;
        const res = await apiPost("/pengajuan-judul/public-setting", {
          is_active: publicSetting.value.is_active,
          slug: publicSetting.value.slug,
          title: publicSetting.value.title,
          description: publicSetting.value.description,
          duration_type: publicSetting.value.duration_type,
          duration_value: publicSetting.value.duration_value,
          custom_expired_at: publicSetting.value.custom_expired_at,
        });

        if (res.success && res.data?.status) {
          toast.success("Pengaturan form publik berhasil disimpan!");
          publicSetting.value = {
            ...publicSetting.value,
            ...res.data.data,
          };
          closeSettingModal();
        } else {
          toast.error(res.data?.message || "Gagal menyimpan pengaturan.");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menyimpan pengaturan.");
      } finally {
        savingSetting.value = false;
      }
    }

    function getFullPublicUrl() {
      const base = window.location.origin;
      return `${base}/form-public/pengajuan-judul/${publicSetting.value.slug || 'pengajuan-judul'}`;
    }

    function copyPublicLink() {
      const link = getFullPublicUrl();
      navigator.clipboard
        .writeText(link)
        .then(() => {
          toast.success("Tautan form publik berhasil disalin ke clipboard!");
        })
        .catch(() => {
          toast.info(link);
        });
    }

    function testOpenPublicLink() {
      window.open(getFullPublicUrl(), "_blank");
    }

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
        const response = await apiGet("/pengajuan-judul", {
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
          }));
          total.value = response.data.data.total || 0;
        }
      } catch (error) {
      } finally {
        loading.value = false;
      }
    }

    function openFileExport(blob: any) {
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank", "noopener");
      setTimeout(() => URL.revokeObjectURL(url), 10_000);
    }

    function edit(params: any) {
      router.push(`/pengajuan-judul/edit/${params.id}`);
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete("/pengajuan-judul/" + params.id);
            if (response.success || response.data?.status) {
              toast.success("Data berhasil dihapus");
              getData();
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
        text: "Mohon tunggu sejenak, dokumen sedang digenerate.",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      try {
        const res = await apiPdf(
          `/pengajuan-judul/download-pdf/${params.id}`,
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

    watch([searchValue, prodiFilter], () => {
      serverOptions.value.page = 1;
      getData();
    });

    watch(
      serverOptions,
      () => {
        getData();
      },
      { deep: true }
    );

    onMounted(() => {
      getProdi();
      getData();
      getPublicSetting();
    });

    function goAdd() {
      router.push("/pengajuan-judul/add");
    }

    return {
      headers,
      items,
      getData,
      edit,
      remove,
      download,
      total,
      goAdd,
      serverOptions,
      searchValue,
      loading,
      listProdi,
      prodiFilter,
      // Public Setting
      publicSetting,
      settingModalRef,
      loadingSetting,
      savingSetting,
      openSettingModal,
      closeSettingModal,
      savePublicSetting,
      getFullPublicUrl,
      copyPublicLink,
      testOpenPublicLink,
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
        Formulir Pengajuan Judul Skripsi
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Surat</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Pengajuan Judul Skripsi
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
              class="btn btn-primary btn-wave shadow-sm"
              @click="goAdd"
            >
              <i class="ri-add-line align-middle me-1"></i> Tambah Data
            </button>
            <button
              class="btn btn-outline-primary btn-wave shadow-sm"
              @click="openSettingModal"
            >
              <i class="ri-global-line align-middle me-1"></i> Pengaturan Form Publik
              <span
                class="badge ms-1"
                :class="publicSetting.is_active && publicSetting.is_available ? 'bg-success' : 'bg-danger'"
              >
                {{ publicSetting.is_active && publicSetting.is_available ? 'Aktif' : 'Tutup' }}
              </span>
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
              placeholder="Cari (NIM/Nama/Dosen/Judul)..."
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

        <template #item-drive_link="item">
          <template v-if="item.drive_link">
            <a
              :href="item.drive_link"
              target="_blank"
              class="btn btn-sm btn-success-light"
            >
              <i class="ri-link"></i> Lihat
            </a>
          </template>
          <template v-else>
            <span class="badge bg-warning-transparent">
              proses upload google drive
            </span>
          </template>
        </template>

        <template #item-action="item">
          <div class="btn-list">
            <button
              class="btn btn-sm btn-icon btn-info-light btn-wave"
              title="Download PDF"
              @click="download(item)"
            >
              <i class="ri-download-2-line"></i>
            </button>
            <button
              class="btn btn-sm btn-icon btn-primary-light btn-wave"
              title="Edit"
              @click="edit(item)"
            >
              <i class="ri-edit-line"></i>
            </button>
            <button
              class="btn btn-sm btn-icon btn-danger-light btn-wave"
              title="Hapus"
              @click="remove(item)"
            >
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </template>
      </EasyDataTable>
    </SimpleCardComponent>

    <!-- ==================== MODAL PENGATURAN FORM PUBLIK ==================== -->
    <div
      class="modal fade"
      id="publicSettingModal"
      ref="settingModalRef"
      tabindex="-1"
      aria-labelledby="publicSettingModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-primary text-white p-3 p-md-4">
            <div class="d-flex align-items-center">
              <div
                class="bg-white bg-opacity-25 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                style="width: 44px; height: 44px;"
              >
                <i class="ri-global-line fs-20 text-white"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0 text-white" id="publicSettingModalLabel">
                  Pengaturan Formulir Publik Pengajuan Judul
                </h5>
                <small class="text-white-50 fs-12">
                  Atur tautan dan batas masa aktif formulir agar mahasiswa bisa mengajukan judul skripsi tanpa login
                </small>
              </div>
            </div>
            <button
              type="button"
              class="btn-close btn-close-white"
              aria-label="Close"
              @click="closeSettingModal"
            ></button>
          </div>

          <div class="modal-body p-4">
            <!-- Ringkasan Status -->
            <div class="card bg-light border-0 rounded-3 p-3 mb-4">
              <div class="row g-3 align-items-center">
                <div class="col-sm-6">
                  <div class="text-muted fs-12 mb-1">Status Ketersediaan:</div>
                  <div class="d-flex align-items-center">
                    <span
                      class="badge px-3 py-2 fs-12 rounded-pill me-2"
                      :class="publicSetting.is_active && publicSetting.is_available ? 'bg-success' : 'bg-danger'"
                    >
                      <i
                        :class="publicSetting.is_active && publicSetting.is_available ? 'ri-check-line' : 'ri-close-line'"
                        class="me-1"
                      ></i>
                      {{ publicSetting.is_active && publicSetting.is_available ? 'Formulir Aktif' : 'Formulir Ditutup' }}
                    </span>
                    <span class="fs-13 text-secondary fw-semibold">
                      {{ publicSetting.remaining_text }}
                    </span>
                  </div>
                </div>
                <div class="col-sm-6 text-sm-end">
                  <div class="text-muted fs-12 mb-1">Total Respon Diterima:</div>
                  <span class="fs-18 fw-bold text-primary">
                    {{ publicSetting.total_submissions || 0 }}
                  </span>
                  <span class="fs-13 text-muted ms-1">pengajuan</span>
                </div>
              </div>
            </div>

            <!-- Form Kontrol -->
            <form @submit.prevent="savePublicSetting">
              <!-- Switch Status Aktif -->
              <div class="form-check form-switch mb-4 p-0 d-flex align-items-center justify-content-between p-3 rounded-3 border bg-white">
                <div>
                  <label class="form-check-label fw-bold text-dark fs-14 d-block" for="switchPublicActive">
                    Aktifkan Formulir Publik
                  </label>
                  <small class="text-muted fs-12">
                    Jika dinonaktifkan, mahasiswa yang membuka tautan akan melihat halaman pemberitahuan bahwa formulir sedang ditutup.
                  </small>
                </div>
                <input
                  class="form-check-input ms-3 fs-20 cursor-pointer"
                  type="checkbox"
                  role="switch"
                  id="switchPublicActive"
                  v-model="publicSetting.is_active"
                />
              </div>

              <!-- Alamat / Slug Link -->
              <div class="mb-4">
                <label class="form-label fw-semibold text-dark fs-13">
                  <i class="ri-link me-1 text-primary"></i> Slug / Alamat Tautan
                </label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-light text-muted fs-13">/form-public/pengajuan-judul/</span>
                  <input
                    type="text"
                    class="form-control"
                    v-model="publicSetting.slug"
                    placeholder="pengajuan-judul"
                    required
                  />
                </div>

                <!-- Preview Link Lengkap + Tombol Copy & Test -->
                <div class="p-3 bg-light rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <div class="text-truncate text-primary fs-13 fw-semibold flex-grow-1" style="max-width: 480px;">
                    <i class="ri-external-link-line me-1"></i> {{ getFullPublicUrl() }}
                  </div>
                  <div class="d-flex gap-2">
                    <button
                      type="button"
                      class="btn btn-sm btn-primary"
                      @click="copyPublicLink"
                      title="Salin Tautan"
                    >
                      <i class="ri-file-copy-line me-1"></i> Salin Link
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary"
                      @click="testOpenPublicLink"
                      title="Buka Halaman Form di Tab Baru"
                    >
                      <i class="ri-eye-line me-1"></i> Buka
                    </button>
                  </div>
                </div>
              </div>

              <!-- Pengaturan Masa Aktif (Durasi) -->
              <div class="card p-3 border rounded-3 mb-4 bg-white">
                <label class="form-label fw-semibold text-dark fs-14 mb-2">
                  <i class="ri-timer-line me-1 text-primary"></i> Atur Batas Waktu Formulir
                </label>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label text-muted fs-12">Pilih Satuan Waktu:</label>
                    <select class="form-select" v-model="publicSetting.duration_type">
                      <option value="minutes">Menit</option>
                      <option value="hours">Jam</option>
                      <option value="days">Hari</option>
                      <option value="custom">Pilih Tanggal & Waktu Khusus</option>
                      <option value="indefinite">Tanpa Batas Waktu (Selamanya Aktif)</option>
                    </select>
                  </div>

                  <div
                    class="col-md-6"
                    v-if="['minutes', 'hours', 'days'].includes(publicSetting.duration_type)"
                  >
                    <label class="form-label text-muted fs-12">
                      Jumlah ({{
                        publicSetting.duration_type === 'minutes' ? 'Menit' : publicSetting.duration_type === 'hours' ? 'Jam' : 'Hari'
                      }}):
                    </label>
                    <input
                      type="number"
                      class="form-control"
                      v-model="publicSetting.duration_value"
                      min="1"
                      required
                    />
                  </div>

                  <div class="col-md-6" v-if="publicSetting.duration_type === 'custom'">
                    <label class="form-label text-muted fs-12">Batas Tanggal & Jam:</label>
                    <input
                      type="datetime-local"
                      class="form-control"
                      v-model="publicSetting.custom_expired_at"
                      required
                    />
                  </div>
                </div>

                <div class="form-text fs-12 mt-2 text-muted">
                  <i class="ri-information-line me-1"></i>
                  <span v-if="publicSetting.duration_type === 'indefinite'">
                    Formulir akan terus aktif sampai dinonaktifkan secara manual.
                  </span>
                  <span v-else>
                    Waktu kedaluwarsa akan dihitung otomatis saat Anda menyimpan pengaturan ini.
                  </span>
                </div>
              </div>

              <!-- Judul & Deskripsi Formulir -->
              <div class="mb-3">
                <label class="form-label fw-semibold text-dark fs-13">Judul Tampilan Formulir</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="publicSetting.title"
                  placeholder="Formulir Pengajuan Judul Skripsi"
                />
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold text-dark fs-13">Deskripsi / Informasi Tambahan</label>
                <textarea
                  class="form-control"
                  rows="2"
                  v-model="publicSetting.description"
                  placeholder="Tuliskan catatan atau instruksi singkat untuk mahasiswa..."
                ></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button
                  type="button"
                  class="btn btn-secondary px-4"
                  @click="closeSettingModal"
                >
                  Tutup
                </button>
                <button
                  type="submit"
                  class="btn btn-primary px-4"
                  :disabled="savingSetting"
                >
                  <span v-if="savingSetting" class="spinner-border spinner-border-sm me-2"></span>
                  Simpan Pengaturan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-icon {
  width: 2rem;
  height: 2rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.cursor-pointer {
  cursor: pointer;
}
</style>

