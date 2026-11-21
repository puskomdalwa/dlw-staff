<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch, defineComponent } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { apiDelete, apiGet, apiPost } from "../../services/api/request";
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
    const items = ref<any[]>([]);
    const searchValue = ref("");
    const total = ref(0);
    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "NIM", value: "nim", sortable: true },
      { text: "Nama Mahasiswa", value: "nama", sortable: true },
      { text: "NIK", value: "nik", sortable: false },
      { text: "Program Studi", value: "prodi_display", sortable: false },
      { text: "Judul Skripsi", value: "judul_skripsi", sortable: true },
      { text: "Periode Sidang", value: "periode_sidang", sortable: true },
      { text: "Dokumen Persyaratan", value: "dokumen_badge", sortable: false },
      { text: "Aksi", value: "action", sortable: false },
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

    // Modal Kelola Dokumen State
    const docModalRef = ref<HTMLElement | null>(null);
    let docModal: Modal | null = null;
    const selectedItem = ref<any>(null);
    const uploadingModalDoc = ref(false);
    const newDocName = ref("");
    const newDocFile = ref<File | null>(null);
    const modalFileInput = ref<HTMLInputElement | null>(null);

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
        const response = await apiGet("/daftar-sidang-skripsi", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
          prodi_id: prodiFilter.value,
        });

        if (response.success && response.data?.status) {
          const rows = response.data.data.data || [];
          const startNo =
            (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
          items.value = rows.map((row: any, idx: number) => ({
            ...row,
            no: startNo + idx + 1,
            prodi_display: row.prodi || (row.prodi_rel ? row.prodi_rel.nama : "-"),
          }));
          total.value = response.data.data.total || 0;
        }
      } catch (error) {
        toast.error("Gagal memuat data pendaftaran sidang");
      } finally {
        loading.value = false;
      }
    }

    function openDocModal(item: any) {
      selectedItem.value = item;
      newDocName.value = "";
      newDocFile.value = null;
      if (modalFileInput.value) modalFileInput.value.value = "";

      if (docModalRef.value) {
        if (!docModal) {
          docModal = new Modal(docModalRef.value);
        }
        docModal.show();
      }
    }

    function onModalFileChange(e: Event) {
      const target = e.target as HTMLInputElement;
      if (target.files && target.files.length > 0) {
        newDocFile.value = target.files[0];
        if (!newDocName.value) {
          newDocName.value = target.files[0].name.replace(/\.[^/.]+$/, "");
        }
      }
    }

    async function uploadDocumentInModal() {
      if (!selectedItem.value) return;
      if (!newDocFile.value) {
        toast.warning("Silakan pilih file berkas terlebih dahulu");
        return;
      }
      if (!newDocName.value.trim()) {
        toast.warning("Nama berkas tidak boleh kosong");
        return;
      }

      try {
        uploadingModalDoc.value = true;
        const fd = new FormData();
        fd.append("file", newDocFile.value);
        fd.append("nama_dokumen", newDocName.value);

        const response = await apiPost(
          `/daftar-sidang-skripsi/${selectedItem.value.id}/upload-dokumen`,
          fd
        );

        if (response.success && response.data?.status) {
          toast.success("Dokumen berhasil diunggah dan dijadwalkan ke Google Drive");
          if (!selectedItem.value.dokumen) {
            selectedItem.value.dokumen = [];
          }
          selectedItem.value.dokumen.push(response.data.data);
          newDocName.value = "";
          newDocFile.value = null;
          if (modalFileInput.value) modalFileInput.value.value = "";
          getData();
        } else {
          toast.error(response.data?.message || "Gagal mengunggah berkas");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengunggah berkas");
      } finally {
        uploadingModalDoc.value = false;
      }
    }

    async function deleteDocInModal(doc: any, index: number) {
      const res = await Swal.fire({
        title: "Hapus Berkas?",
        text: `Berkas "${doc.nama_dokumen}" akan dihapus permanen.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      });

      if (res.isConfirmed) {
        try {
          const response = await apiDelete(`/daftar-sidang-skripsi/dokumen/${doc.id}`);
          if (response.success || response.data?.status) {
            toast.success("Berkas berhasil dihapus");
            selectedItem.value.dokumen.splice(index, 1);
            getData();
          } else {
            toast.error("Gagal menghapus berkas");
          }
        } catch (error) {
          toast.error("Terjadi kesalahan");
        }
      }
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: `Data pendaftaran sidang atas nama "${params.nama}" (${params.nim}) beserta semua dokumennya akan dihapus permanen!`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete("/daftar-sidang-skripsi/" + params.id);
            if (response.success || response.data?.status) {
              toast.success("Pendaftaran sidang berhasil dihapus");
              getData();
            } else {
              toast.error(response.data?.message || "Data gagal dihapus");
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus data");
          }
        }
      });
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

    // Setting Form Publik State
    const settingModalRef = ref<HTMLElement | null>(null);
    let settingModal: Modal | null = null;
    const loadingSetting = ref(false);
    const savingSetting = ref(false);
    const publicSetting = ref<any>({
      is_active: true,
      slug: "daftar-sidang-skripsi",
      title: "Formulir Pendaftaran Sidang Skripsi",
      description: "Silakan lengkapi formulir pendaftaran sidang skripsi berikut dan unggah seluruh berkas dokumen persyaratan.",
      duration_type: "days",
      duration_value: 7,
      custom_expired_at: "",
      total_submissions: 0,
      is_available: false,
      remaining_text: "",
    });

    async function getPublicSetting() {
      try {
        loadingSetting.value = true;
        const res = await apiGet("/daftar-sidang-skripsi/public-setting");
        if (res.success && res.data?.status) {
          publicSetting.value = {
            ...publicSetting.value,
            ...res.data.data,
          };
          if (res.data.data.expired_at) {
            publicSetting.value.custom_expired_at = res.data.data.expired_at.replace(" ", "T").slice(0, 16);
          }
        }
      } catch (error) {
        // ignore
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
        const res = await apiPost("/daftar-sidang-skripsi/public-setting", {
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
          toast.error(res.data?.message || "Gagal menyimpan pengaturan");
        }
      } catch (error: any) {
        toast.error("Terjadi kesalahan saat menyimpan pengaturan");
      } finally {
        savingSetting.value = false;
      }
    }

    function getFullPublicUrl() {
      const base = window.location.origin;
      return `${base}/form-public/daftar-sidang-skripsi/${publicSetting.value.slug || 'daftar-sidang-skripsi'}`;
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

    onMounted(() => {
      getProdi();
      getData();
      getPublicSetting();
    });

    function goAdd() {
      router.push("/daftar-sidang-skripsi/add");
    }

    return {
      items,
      searchValue,
      headers,
      serverOptions,
      loading,
      total,
      listProdi,
      prodiFilter,
      goAdd,
      remove,
      router,
      docModalRef,
      selectedItem,
      openDocModal,
      uploadingModalDoc,
      newDocName,
      modalFileInput,
      onModalFileChange,
      uploadDocumentInModal,
      deleteDocInModal,
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
        Pendaftaran Sidang Skripsi
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Akademik</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Pendaftaran Sidang Skripsi
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
              <i class="ri-add-line align-middle me-1"></i> Tambah Pendaftaran Sidang
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
              <option value="">Semua Program Studi</option>
              <option
                v-for="prodi in listProdi"
                :key="prodi.id"
                :value="prodi.id"
              >
                {{ prodi.nama }} ({{ prodi.alias }})
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
              placeholder="Cari NIM, Nama, Judul..."
              v-model="searchValue"
            />
            <span class="input-group-text">
              <i class="ri-search-line"></i>
            </span>
          </div>
        </div>
      </div>

        <EasyDataTable
          table-class-name="customize-table"
          :headers="headers"
          :items="items"
          theme-color="#1366ac"
          :loading="loading"
          v-model:server-options="serverOptions"
          :server-items-length="total"
        >
          <!-- Column NIM -->
          <template #item-nim="{ nim }">
            <span class="badge bg-light text-dark border fw-medium">{{ nim }}</span>
          </template>

          <!-- Column Nama -->
          <template #item-nama="{ nama }">
            <span class="fw-semibold text-primary">{{ nama }}</span>
          </template>

          <!-- Column NIK -->
          <template #item-nik="{ nik }">
            <span class="text-muted">{{ nik || "-" }}</span>
          </template>

          <!-- Column Judul Skripsi -->
          <template #item-judul_skripsi="{ judul_skripsi, dospem1, pembimbing1, dospem2, pembimbing2 }">
            <div
              class="text-truncate"
              style="max-width: 320px;"
              :title="judul_skripsi"
            >
              {{ judul_skripsi }}
            </div>
            <div v-if="dospem1 || pembimbing1 || dospem2 || pembimbing2" class="text-muted small mt-1">
              <span v-if="dospem1 || pembimbing1" class="d-block text-truncate" style="max-width: 320px;">
                <i class="ri-user-star-line text-primary me-1"></i>P1: {{ dospem1 || pembimbing1 }}
              </span>
              <span v-if="dospem2 || pembimbing2" class="d-block text-truncate" style="max-width: 320px;">
                <i class="ri-user-star-line text-secondary me-1"></i>P2: {{ dospem2 || pembimbing2 }}
              </span>
            </div>
          </template>

          <!-- Column Periode Sidang -->
          <template #item-periode_sidang="{ periode_sidang }">
            <span class="badge bg-info-transparent d-inline-flex align-items-center">
              <i class="ri-calendar-event-line me-1"></i>
              {{ periode_sidang }}
            </span>
          </template>

          <!-- Column Dokumen Persyaratan -->
          <template #item-dokumen_badge="item">
            <button
              type="button"
              class="btn btn-sm btn-outline-info py-1 px-2 d-inline-flex align-items-center"
              @click="openDocModal(item)"
            >
              <i class="ri-folder-shared-line me-1"></i>
              <span>{{ item.dokumen ? item.dokumen.length : 0 }} Dokumen</span>
            </button>
          </template>

          <!-- Column Action -->
          <template #item-action="item">
            <div class="d-flex gap-1 justify-content-center">
              <!-- Kelola Dokumen -->
              <button
                type="button"
                class="btn btn-sm btn-info-light btn-icon"
                @click="openDocModal(item)"
                title="Kelola Dokumen Persyaratan"
              >
                <i class="ri-file-list-3-line"></i>
              </button>

              <!-- Edit -->
              <router-link
                :to="`/daftar-sidang-skripsi/edit/${item.id}`"
                class="btn btn-sm btn-primary-light btn-icon"
                title="Edit Pendaftaran"
              >
                <i class="ri-edit-line"></i>
              </router-link>

              <!-- Hapus -->
              <button
                type="button"
                class="btn btn-sm btn-danger-light btn-icon"
                @click="remove(item)"
                title="Hapus Data"
              >
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>
          </template>
        </EasyDataTable>
      </SimpleCardComponent>

    <!-- Modal Kelola Dokumen Persyaratan -->
    <div
      class="modal fade"
      ref="docModalRef"
      tabindex="-1"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h6 class="modal-title d-flex align-items-center text-white mb-0">
              <i class="ri-folder-open-line me-2"></i>
              Berkas Sidang Skripsi - {{ selectedItem?.nama }} ({{ selectedItem?.nim }})
            </h6>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>

          <div class="modal-body p-3">
            <!-- Informasi Singkat Mahasiswa -->
            <div class="alert alert-light border mb-3 p-2 small">
              <div class="row">
                <div class="col-md-6">
                  <strong>Program Studi:</strong> {{ selectedItem?.prodi_display }}
                </div>
                <div class="col-md-6">
                  <strong>Periode Sidang:</strong> {{ selectedItem?.periode_sidang }}
                </div>
                <div class="col-12 mt-1">
                  <strong>Judul:</strong> {{ selectedItem?.judul_skripsi }}
                </div>
              </div>
            </div>

            <!-- Form Upload Berkas Baru -->
            <div class="card border border-dashed p-3 mb-3 bg-light">
              <div class="fw-semibold text-primary mb-2 d-flex align-items-center">
                <i class="ri-upload-cloud-line me-1"></i> Unggah Berkas Baru ke Google Drive
              </div>
              <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="small text-muted me-1 align-self-center">Pilihan cepat:</span>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'File Skripsi'"
                >File Skripsi</button>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'Lembar Konsultasi Dospem 1'"
                >Lembar Konsultasi 1</button>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'Lembar Konsultasi Dospem 2 (Boleh tidak diisi)'"
                >Lembar Konsultasi 2</button>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'FILE FOTO 3X4 (BERDASI, BACKGROUND MERAH, TANPA AKSESORIS)'"
                >Foto 3x4</button>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'Scan Nota Pembimbing (Bukti ACC Skripsi)'"
                >Nota ACC</button>
                <button
                  type="button"
                  class="btn btn-xs btn-outline-secondary py-0 px-2"
                  @click="newDocName = 'Scan Ijazah Asli'"
                >Scan Ijazah Asli</button>
              </div>
              <div class="row g-2 align-items-center">
                <div class="col-md-5">
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Nama Dokumen (Contoh: Naskah Skripsi)"
                    v-model="newDocName"
                  />
                </div>
                <div class="col-md-5">
                  <input
                    ref="modalFileInput"
                    type="file"
                    class="form-control form-control-sm"
                    @change="onModalFileChange"
                  />
                </div>
                <div class="col-md-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-success w-100 d-flex justify-content-center align-items-center"
                    :disabled="uploadingModalDoc"
                    @click="uploadDocumentInModal"
                  >
                    <span
                      v-if="uploadingModalDoc"
                      class="spinner-border spinner-border-sm me-1"
                    ></span>
                    <i v-else class="ri-upload-2-line me-1"></i> Unggah
                  </button>
                </div>
              </div>
            </div>

            <!-- Tabel Daftar Berkas -->
            <div class="table-responsive border rounded">
              <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 40px;">No</th>
                    <th>Nama Berkas</th>
                    <th style="width: 130px;">Status Upload</th>
                    <th style="width: 140px;">Google Drive</th>
                    <th style="width: 70px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-if="!selectedItem?.dokumen || selectedItem.dokumen.length === 0"
                  >
                    <td colspan="5" class="text-center text-muted py-3">
                      Belum ada dokumen yang diunggah. Silakan unggah dokumen di atas.
                    </td>
                  </tr>
                  <tr
                    v-for="(doc, idx) in selectedItem?.dokumen || []"
                    :key="doc.id"
                  >
                    <td>{{ idx + 1 }}</td>
                    <td class="fw-medium">
                      <i class="ri-file-text-line me-1 text-secondary"></i>
                      {{ doc.nama_dokumen }}
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="{
                          'bg-success': doc.status === 'uploaded' || doc.status === 'uplouded',
                          'bg-warning text-dark': doc.status === 'pending',
                          'bg-danger': doc.status === 'failed'
                        }"
                      >
                        {{ doc.status }}
                      </span>
                    </td>
                    <td>
                      <a
                        v-if="doc.drive_link"
                        :href="doc.drive_link"
                        target="_blank"
                        class="btn btn-sm btn-outline-primary py-0 px-2 d-inline-flex align-items-center"
                      >
                        <i class="ri-external-link-line me-1"></i> Buka Link
                      </a>
                      <span v-else class="text-muted small">Sedang Antre</span>
                    </td>
                    <td class="text-center">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger py-0 px-2"
                        @click="deleteDocInModal(doc, idx)"
                        title="Hapus Berkas Ini"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer py-2">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              data-bs-dismiss="modal"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

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
                  Pengaturan Formulir Publik Pendaftaran Sidang Skripsi
                </h5>
                <small class="text-white-50 fs-12">
                  Atur tautan dan batas masa aktif formulir agar mahasiswa bisa mendaftar sidang skripsi tanpa login
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
                  <span class="fs-13 text-muted ms-1">pendaftaran</span>
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
                  <span class="input-group-text bg-light text-muted fs-13">/form-public/daftar-sidang-skripsi/</span>
                  <input
                    type="text"
                    class="form-control"
                    v-model="publicSetting.slug"
                    placeholder="daftar-sidang-skripsi"
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
                  placeholder="Formulir Pendaftaran Sidang Skripsi"
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
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 4px;
}
</style>

