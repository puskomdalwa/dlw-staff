<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch } from "vue";
import { defineComponent } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { apiDelete, apiGet, apiPost } from "@/services/api/request";
import SimpleCardComponent from "@/shared/components/@spk/simple-card.vue";
import Pageheader from "@/shared/components/pageheader/pageheader.vue";
import router from "@/router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiPdf } from "../../services/api/request";

interface SignatureStep {
  id: number;
  step_order: number;
  step_key: string;
  short_label: string;
  title: string;
  signer_name: string | null;
  signer_nidn: string | null;
  status: "pending" | "approved";
  is_signed: boolean;
  can_sign_in_staff?: boolean;
  signed_at: string | null;
  signed_at_formatted: string | null;
  signed_by_source: string | null;
  notes: string | null;
  signature_token: string;
  verify_url: string;
  qr_data_uri?: string;
}

export default defineComponent({
  components: {
    Pageheader,
    SimpleCardComponent,
  },
  setup() {
    const items = ref<any[]>([]);
    const searchValue = ref("");
    const total = ref(0);
    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "Nama Mahasiswa", value: "nama_lengkap", sortable: true },
      { text: "NIM", value: "nim", sortable: true },
      { text: "Timeline TTD (QR)", value: "signatures", sortable: false },
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

    // Timeline Modal State
    const showTimelineModal = ref(false);
    const timelineLoading = ref(false);
    const activeSk6 = ref<any>(null);
    const timelineSteps = ref<SignatureStep[]>([]);
    const actionStepLoading = ref<string | null>(null);
    const editingStepKey = ref<string | null>(null);
    const customSignerName = ref("");
    const customNotes = ref("");

    // API Integration & Docs Modal State
    const showApiModal = ref(false);
    const apiConfigLoading = ref(false);
    const regeneratingKey = ref(false);
    const showRawApiKey = ref(false);
    const apiConfig = ref<any>({
      base_url: "",
      verify_base_url: "",
      api_key: "",
      is_active: true,
      last_used_at: null,
      step_keys: [],
    });

    // Signer Defaults State (Module-wide default pejabat/penandatangan)
    const showSignerDefaultsModal = ref(false);
    const loadingSignerDefaults = ref(false);
    const savingSignerDefaults = ref(false);
    const signerDefaults = ref<any[]>([]);
    const applyDefaultsToExisting = ref(true);
    const applyDefaultsScope = ref<"all" | "pending">("all");

    async function getProdi() {
      try {
        const response = await apiGet(`/get-prodi`);
        if (response.success) {
          const data = response.data?.data
            ? response.data?.data
            : response.data;
          listProdi.value = Array.isArray(data) ? data : [data];
        }
      } catch (error) {
      }
    }

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/sk6", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
          prodi_id: prodiFilter.value,
        });

        const rows = Array.isArray(response.data.data.data)
          ? response.data.data.data
          : [];

        const startNo =
          (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
        items.value = rows.map((row: any, idx: number) => ({
          ...row,
          no: startNo + idx + 1,
        }));

        total.value = response.data.data.total;
      } catch (error) {
      } finally {
        loading.value = false;
      }
    }

    function openFileExport(blob: any) {
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.target = "_blank";
      link.rel = "noopener";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      setTimeout(() => URL.revokeObjectURL(url), 10_000);
    }

    function edit(params: any) {
      router.push(`/sk6/edit/${params.id}`);
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
            const response = await apiDelete("/sk6/" + params.id);
            if (response.success && response.data?.status !== false) {
              toast.success("Data berhasil dihapus", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
              getData();
            } else {
              toast.error("Data gagal dihapus", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus data", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      });
    }

    async function openTimeline(item: any) {
      showTimelineModal.value = true;
      timelineLoading.value = true;
      editingStepKey.value = null;
      activeSk6.value = {
        id: item.id,
        nama_mhs: item.nama_lengkap || item.nama_mhs,
        nim: item.nim,
        prodi_nama: item.prodi_nama || "-",
        signed_count: item.signed_count ?? 0,
        total_steps: item.total_steps ?? 6,
      };
      try {
        const res = await apiGet(`/sk6/${item.id}/signatures`);
        if (res.success && res.data?.status) {
          activeSk6.value = res.data.data.sk6;
          timelineSteps.value = res.data.data.steps || [];
        } else {
          toast.error("Gagal memuat timeline TTD");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat memuat timeline TTD");
      } finally {
        timelineLoading.value = false;
      }
    }

    function closeTimelineModal() {
      showTimelineModal.value = false;
      editingStepKey.value = null;
      customSignerName.value = "";
      customNotes.value = "";
    }

    function canSignInStaff(step: SignatureStep) {
      if (typeof step.can_sign_in_staff === "boolean") {
        return step.can_sign_in_staff;
      }
      return ["keamanan", "idaroh_syuun"].includes(step.step_key);
    }

    function toggleEditStep(step: SignatureStep) {
      if (!canSignInStaff(step)) return;
      if (editingStepKey.value === step.step_key) {
        editingStepKey.value = null;
        customSignerName.value = "";
        customNotes.value = "";
      } else {
        editingStepKey.value = step.step_key;
        customSignerName.value = step.signer_name || "";
        customNotes.value = step.notes || "";
      }
    }

    async function confirmSignature(step: SignatureStep) {
      if (!activeSk6.value) return;
      actionStepLoading.value = step.step_key;
      try {
        const payload: Record<string, any> = {};
        if (editingStepKey.value === step.step_key) {
          if (customSignerName.value.trim()) {
            payload.signer_name = customSignerName.value.trim();
          }
          if (customNotes.value.trim()) {
            payload.notes = customNotes.value.trim();
          }
        }

        const res = await apiPost(
          `/sk6/${activeSk6.value.id}/signatures/${step.step_key}/confirm`,
          payload
        );
        if (res.success && res.data?.status) {
          timelineSteps.value = res.data.data.steps || [];
          activeSk6.value = {
            ...activeSk6.value,
            signed_count: res.data.data.signed_count,
            total_steps: res.data.data.total_steps,
            is_completed: res.data.data.is_completed,
          };
          editingStepKey.value = null;
          toast.success(`TTD QR ${step.title} berhasil dikonfirmasi`);
          getData();
        } else {
          toast.error(
            res.data?.message || "Gagal mengkonfirmasi TTD QR"
          );
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat konfirmasi TTD QR");
      } finally {
        actionStepLoading.value = null;
      }
    }

    async function revokeSignature(step: SignatureStep) {
      if (!activeSk6.value) return;
      const result = await Swal.fire({
        title: "Batalkan konfirmasi TTD?",
        text: `Status TTD QR untuk ${step.title} akan dikembalikan ke Menunggu.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya, batalkan",
        cancelButtonText: "Tutup",
      });
      if (!result.isConfirmed) return;

      actionStepLoading.value = step.step_key;
      try {
        const res = await apiPost(
          `/sk6/${activeSk6.value.id}/signatures/${step.step_key}/revoke`,
          {}
        );
        if (res.success && res.data?.status) {
          timelineSteps.value = res.data.data.steps || [];
          activeSk6.value = {
            ...activeSk6.value,
            signed_count: res.data.data.signed_count,
            total_steps: res.data.data.total_steps,
            is_completed: res.data.data.is_completed,
          };
          toast.info(`TTD QR ${step.title} dibatalkan`);
          getData();
        } else {
          toast.error("Gagal membatalkan TTD QR");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat membatalkan TTD QR");
      } finally {
        actionStepLoading.value = null;
      }
    }

    async function openApiDocsModal() {
      showApiModal.value = true;
      apiConfigLoading.value = true;
      try {
        const res = await apiGet("/sk6/integration-config");
        if (res.success && res.data?.status) {
          apiConfig.value = res.data.data;
        } else {
          toast.error("Gagal mengambil konfigurasi API");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan mengambil konfigurasi API");
      } finally {
        apiConfigLoading.value = false;
      }
    }

    async function regenerateApiKey() {
      const result = await Swal.fire({
        title: "Generate ulang API Key?",
        text: "API Key lama akan langsung nonaktif dan website eksternal harus memakai API Key baru.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, Generate Ulang",
        cancelButtonText: "Batal",
      });
      if (!result.isConfirmed) return;

      regeneratingKey.value = true;
      try {
        const res = await apiPost("/sk6/integration-config/regenerate", {});
        if (res.success && res.data?.status) {
          apiConfig.value = {
            ...apiConfig.value,
            ...res.data.data,
          };
          showRawApiKey.value = true;
          toast.success("API Key baru berhasil digenerate");
        } else {
          toast.error("Gagal generate ulang API Key");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat generate ulang API Key");
      } finally {
        regeneratingKey.value = false;
      }
    }

    async function copyText(text: string, label: string) {
      if (!text) return;
      try {
        await navigator.clipboard.writeText(text);
        toast.success(`${label} berhasil disalin`);
      } catch {
        toast.error(`Gagal menyalin ${label}`);
      }
    }

    function maskedApiKey(key: string) {
      if (!key) return "";
      if (showRawApiKey.value) return key;
      if (key.length <= 12) return "••••••••••••";
      return `${key.slice(0, 8)}••••••••••••••••••••${key.slice(-6)}`;
    }

    async function openSignerDefaultsModal() {
      showSignerDefaultsModal.value = true;
      loadingSignerDefaults.value = true;
      try {
        const res = await apiGet("/sk6/signer-defaults");
        if (res.success && res.data?.status) {
          signerDefaults.value = (res.data.data || []).map((s: any) => ({
            ...s,
            signer_name: s.signer_name ?? "",
            signer_nidn: s.signer_nidn ?? "",
          }));
        } else {
          toast.error("Gagal mengambil default penandatangan SK6");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat memuat default penandatangan SK6");
      } finally {
        loadingSignerDefaults.value = false;
      }
    }

    async function saveSignerDefaults() {
      savingSignerDefaults.value = true;
      try {
        const res = await apiPost("/sk6/signer-defaults", {
          steps: signerDefaults.value.map((s: any) => ({
            step_key: s.step_key,
            title: s.title,
            signer_name: s.signer_name,
            signer_nidn: s.signer_nidn,
          })),
          apply_to_existing: applyDefaultsToExisting.value,
          apply_scope: applyDefaultsScope.value,
        });
        if (res.success && res.data?.status) {
          toast.success(
            res.data.message || "Default penandatangan SK6 berhasil disimpan"
          );
          showSignerDefaultsModal.value = false;
          getData();
        } else {
          toast.error(
            res.data?.message || "Gagal menyimpan default penandatangan SK6"
          );
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat menyimpan default penandatangan SK6");
      } finally {
        savingSignerDefaults.value = false;
      }
    }

    watch([searchValue, prodiFilter], () => {
      getData();
    });

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
          `/sk6/download-pdf/${params.id}`,
          {},
          { responseType: "blob" }
        );

        openFileExport(res.data);
        Swal.close();
      } catch (error) {
        Swal.close();
        toast.error("Gagal mengunduh file PDF", {
          theme: "auto",
          icon: true,
          hideProgressBar: true,
          autoClose: true,
          position: "top-right",
        });
      }
    }

    watch([serverOptions], () => {
      getData();
    });

    onMounted(() => {
      getProdi();
      getData();
    });

    function goAdd() {
      return router.push("/sk6/add");
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
      showTimelineModal,
      timelineLoading,
      activeSk6,
      timelineSteps,
      actionStepLoading,
      editingStepKey,
      customSignerName,
      customNotes,
      openTimeline,
      closeTimelineModal,
      canSignInStaff,
      toggleEditStep,
      confirmSignature,
      revokeSignature,
      showApiModal,
      apiConfigLoading,
      regeneratingKey,
      showRawApiKey,
      apiConfig,
      openApiDocsModal,
      regenerateApiKey,
      copyText,
      maskedApiKey,
      showSignerDefaultsModal,
      loadingSignerDefaults,
      savingSignerDefaults,
      signerDefaults,
      applyDefaultsToExisting,
      applyDefaultsScope,
      openSignerDefaultsModal,
      saveSignerDefaults,
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
        SK 6 (5 Surat Keterangan)
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Kegiatan</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">SK 6</li>
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
              @click="goAdd"
            >
              <i class="ri-add-line align-middle me-1"></i> Tambah Data
            </button>
          </div>
          <div class="col-12 col-md-auto">
            <button
              class="btn btn-info-light btn-wave shadow-sm w-100"
              @click="openApiDocsModal"
            >
              <i class="ri-key-2-line align-middle me-1"></i> API &amp; Integrasi TTD
            </button>
          </div>
          <div class="col-12 col-md-auto">
            <button
              class="btn btn-warning-light btn-wave shadow-sm w-100"
              @click="openSignerDefaultsModal"
            >
              <i class="ri-user-settings-line align-middle me-1"></i> Default Penandatangan
            </button>
          </div>
          <div class="col-12 col-md-auto ms-auto">
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
              placeholder="Cari (Nama/NIM)..."
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

        <template #item-signatures="item">
          <div
            class="d-flex flex-column gap-1 cursor-pointer"
            style="cursor: pointer; min-width: 250px"
            @click="openTimeline(item)"
            title="Klik untuk lihat & konfirmasi Timeline TTD QR"
          >
            <div class="d-flex align-items-center gap-2">
              <span
                class="badge"
                :class="
                  (item.signed_count || 0) === (item.total_steps || 6)
                    ? 'bg-success-transparent'
                    : (item.signed_count || 0) > 0
                    ? 'bg-info-transparent'
                    : 'bg-warning-transparent'
                "
              >
                {{ item.signed_count || 0 }}/{{ item.total_steps || 6 }} TTD QR
              </span>
              <small class="text-primary fw-medium">Lihat Timeline</small>
            </div>
            <div class="d-flex align-items-center gap-1 flex-wrap">
              <span
                v-for="step in item.signatures || []"
                :key="step.step_key"
                class="badge rounded-pill"
                :class="
                  step.is_signed
                    ? 'bg-success text-white'
                    : 'bg-light text-muted border'
                "
                style="font-size: 10px"
              >
                <i
                  :class="
                    step.is_signed ? 'ri-check-line me-1' : 'ri-time-line me-1'
                  "
                ></i>
                {{ step.short_label }}
              </span>
            </div>
          </div>
        </template>

        <template #item-drive_link="item">
          <div class="d-flex flex-wrap align-items-center gap-1" style="min-width: 240px">
            <a
              v-if="item.drive_link"
              :href="item.drive_link"
              target="_blank"
              class="btn btn-sm btn-outline-secondary"
              title="Buka PDF Tanpa TTD di Google Drive"
            >
              <i class="ri-file-text-line me-1"></i> Tanpa TTD
            </a>
            <span v-else class="badge bg-warning-transparent">
              Tanpa TTD: proses upload
            </span>

            <a
              v-if="item.is_completed && item.signed_drive_link"
              :href="item.signed_drive_link"
              target="_blank"
              class="btn btn-sm btn-success-light"
              title="Buka PDF Ber-TTD QR Lengkap di Google Drive"
            >
              <i class="ri-qr-code-line me-1"></i> Ber-TTD (QR)
            </a>
            <span v-else-if="item.is_completed" class="badge bg-info-transparent">
              Ber-TTD: proses upload Drive
            </span>
            <span v-else class="badge bg-light text-muted border">
              Ber-TTD: {{ item.signed_count || 0 }}/{{ item.total_steps || 6 }}
            </span>
          </div>
        </template>

        <template #item-action="item">
          <div class="btn-list">
            <button
              class="btn btn-sm btn-icon btn-success-light btn-wave"
              title="Timeline TTD QR"
              @click="openTimeline(item)"
            >
              <i class="ri-qr-code-line"></i>
            </button>
            <button
              v-if="item.is_completed"
              class="btn btn-sm btn-icon btn-info-light btn-wave"
              title="Download PDF (QR Lengkap)"
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

    <!-- MODAL TIMELINE TTD QR -->
    <div
      v-if="showTimelineModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-semibold mb-0">
                Timeline Tanda Tangan QR — SK 6
              </h5>
              <small class="text-muted" v-if="activeSk6">
                {{ activeSk6.nama_mhs }} ({{ activeSk6.nim }})
                <span v-if="activeSk6.prodi_nama">
                  • {{ activeSk6.prodi_nama }}
                </span>
              </small>
            </div>
            <button
              type="button"
              class="btn-close"
              @click="closeTimelineModal"
            ></button>
          </div>

          <div class="modal-body">
            <div v-if="timelineLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="text-muted mt-2 mb-0">Memuat timeline tanda tangan...</p>
            </div>

            <template v-else>
              <div
                v-if="activeSk6"
                class="card border bg-light-subtle mb-3 shadow-none"
              >
                <div class="card-body py-2 px-3">
                  <div
                    class="d-flex justify-content-between align-items-center mb-1"
                  >
                    <span class="fw-semibold fs-13">Progress Konfirmasi TTD</span>
                    <span class="badge bg-primary">
                      {{ activeSk6.signed_count || 0 }} /
                      {{ activeSk6.total_steps || 6 }} Terkonfirmasi
                    </span>
                  </div>
                  <div class="progress" style="height: 8px">
                    <div
                      class="progress-bar bg-success"
                      role="progressbar"
                      :style="{
                        width: `${
                          ((activeSk6.signed_count || 0) /
                            (activeSk6.total_steps || 6)) *
                          100
                        }%`,
                      }"
                    ></div>
                  </div>
                </div>
              </div>

              <div class="sk6-timeline">
                <div
                  v-for="(step, idx) in timelineSteps"
                  :key="step.step_key"
                  class="d-flex position-relative pb-4"
                >
                  <div
                    v-if="idx < timelineSteps.length - 1"
                    class="position-absolute"
                    :style="{
                      left: '17px',
                      top: '36px',
                      bottom: '0',
                      width: '2px',
                      backgroundColor: step.is_signed ? '#198754' : '#dee2e6',
                    }"
                  ></div>

                  <div class="me-3 flex-shrink-0" style="z-index: 1">
                    <div
                      class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                      :class="
                        step.is_signed
                          ? 'bg-success text-white'
                          : 'bg-light text-secondary border'
                      "
                      style="width: 36px; height: 36px"
                    >
                      <i v-if="step.is_signed" class="ri-check-line fs-18"></i>
                      <span v-else>{{ step.step_order }}</span>
                    </div>
                  </div>

                  <div class="flex-grow-1">
                    <div
                      class="card mb-0 shadow-none border"
                      :class="step.is_signed ? 'border-success' : ''"
                    >
                      <div class="card-body p-3">
                        <div
                          class="d-flex flex-wrap justify-content-between align-items-start gap-2"
                        >
                          <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                              <span class="fw-semibold fs-14">
                                {{ step.step_key === 'baku_aupk' ? 'BAKU' : step.title }}
                              </span>
                              <span
                                v-if="step.step_key === 'idaroh_syuun'"
                                class="badge bg-secondary-transparent"
                              >
                                Tanpa Surat
                              </span>
                              <span
                                v-if="step.step_key !== 'baku_aupk'"
                                class="badge bg-light text-dark border"
                              >
                                {{ step.step_key }}
                              </span>
                              <span
                                class="badge"
                                :class="
                                  step.is_signed
                                    ? 'bg-success-transparent'
                                    : 'bg-warning-transparent'
                                "
                              >
                                {{
                                  step.is_signed
                                    ? "Terkonfirmasi QR"
                                    : "Menunggu Konfirmasi"
                                }}
                              </span>
                            </div>

                            <div class="mt-1 fs-13 text-dark">
                              <i class="ri-user-line me-1 text-muted"></i>
                              <strong>{{ step.signer_name || "-" }}</strong>
                              <span
                                v-if="step.signer_nidn"
                                class="text-muted ms-2"
                              >
                                (NIDN: {{ step.signer_nidn }})
                              </span>
                            </div>

                            <div
                              v-if="step.is_signed"
                              class="mt-1 fs-12 text-muted"
                            >
                              <i class="ri-time-line me-1"></i>
                              {{ step.signed_at_formatted || step.signed_at }}
                              <span v-if="step.signed_by_source" class="ms-2">
                                • Sumber:
                                <code>{{ step.signed_by_source }}</code>
                              </span>
                            </div>

                            <div
                              v-if="step.notes"
                              class="mt-1 fs-12 text-muted fst-italic"
                            >
                              Catatan: {{ step.notes }}
                            </div>
                          </div>

                          <div
                            v-if="step.is_signed && step.qr_data_uri"
                            class="text-center"
                          >
                            <img
                              :src="step.qr_data_uri"
                              alt="QR TTD"
                              style="width: 72px; height: 72px"
                              class="border rounded p-1 bg-white"
                            />
                            <div>
                              <a
                                :href="step.verify_url"
                                target="_blank"
                                class="fs-11 text-primary text-decoration-underline"
                              >
                                Cek Verifikasi
                              </a>
                            </div>
                          </div>
                        </div>

                        <div
                          v-if="canSignInStaff(step) && editingStepKey === step.step_key"
                          class="mt-3 pt-2 border-top"
                        >
                          <div class="row g-2">
                            <div class="col-md-6">
                              <label class="form-label fs-12 mb-1">
                                Nama Penandatangan (Opsional)
                              </label>
                              <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="customSignerName"
                                placeholder="Nama pejabat"
                              />
                            </div>
                            <div class="col-md-6">
                              <label class="form-label fs-12 mb-1">
                                Catatan (Opsional)
                              </label>
                              <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="customNotes"
                                placeholder="Contoh: Dikonfirmasi langsung"
                              />
                            </div>
                          </div>
                        </div>

                        <div
                          class="mt-3 d-flex align-items-center gap-2 flex-wrap"
                        >
                          <template v-if="canSignInStaff(step)">
                            <template v-if="!step.is_signed">
                              <button
                                class="btn btn-sm btn-success btn-wave"
                                :disabled="actionStepLoading === step.step_key"
                                @click="confirmSignature(step)"
                              >
                                <span
                                  v-if="actionStepLoading === step.step_key"
                                  class="spinner-border spinner-border-sm me-1"
                                ></span>
                                <i v-else class="ri-check-double-line me-1"></i>
                                Konfirmasi / Centang TTD QR
                              </button>
                              <button
                                class="btn btn-sm btn-light border"
                                type="button"
                                @click="toggleEditStep(step)"
                              >
                                <i class="ri-edit-2-line me-1"></i>
                                {{
                                  editingStepKey === step.step_key
                                    ? "Tutup Opsi"
                                    : "Ubah Nama / Catatan"
                                }}
                              </button>
                            </template>
                            <template v-else>
                              <button
                                class="btn btn-sm btn-outline-danger"
                                :disabled="actionStepLoading === step.step_key"
                                @click="revokeSignature(step)"
                              >
                                <span
                                  v-if="actionStepLoading === step.step_key"
                                  class="spinner-border spinner-border-sm me-1"
                                ></span>
                                <i v-else class="ri-close-circle-line me-1"></i>
                                Batalkan TTD
                              </button>
                            </template>
                          </template>
                          <span
                            v-else
                            class="badge bg-light text-secondary border fw-normal py-2 px-2"
                          >
                            <i class="ri-external-link-line me-1"></i>
                            TTD dilakukan via website pihak terkait (eksternal)
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="modal-footer">
            <button
              v-if="activeSk6 && activeSk6.is_completed"
              type="button"
              class="btn btn-info-light"
              @click="download({ id: activeSk6.id })"
            >
              <i class="ri-download-2-line me-1"></i> Download PDF (QR Lengkap)
            </button>
            <button
              type="button"
              class="btn btn-secondary"
              @click="closeTimelineModal"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL API & DOKUMENTASI INTEGRASI -->
    <div
      v-if="showApiModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-semibold mb-0">
                API &amp; Dokumentasi Integrasi TTD QR SK 6
              </h5>
              <small class="text-muted">
                Hubungkan website eksternal (Prodi, KKN, Keamanan, BAKU Kabag, BAKU AUPK, Idaroh Syuun, Wakil Rektor 1) untuk konfirmasi TTD SK 6.
              </small>
            </div>
            <button
              type="button"
              class="btn-close"
              @click="showApiModal = false"
            ></button>
          </div>

          <div class="modal-body">
            <div v-if="apiConfigLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
            </div>

            <template v-else>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-13">Base URL</label>
                  <div class="input-group">
                    <input
                      type="text"
                      class="form-control form-control-sm font-monospace"
                      :value="apiConfig.base_url"
                      readonly
                    />
                    <button
                      class="btn btn-sm btn-outline-primary"
                      type="button"
                      @click="copyText(apiConfig.base_url, 'Base URL')"
                    >
                      <i class="ri-file-copy-line"></i> Copy
                    </button>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-13">API Key</label>
                  <div class="input-group">
                    <input
                      type="text"
                      class="form-control form-control-sm font-monospace"
                      :value="maskedApiKey(apiConfig.api_key)"
                      readonly
                    />
                    <button
                      class="btn btn-sm btn-outline-secondary"
                      type="button"
                      @click="showRawApiKey = !showRawApiKey"
                      :title="showRawApiKey ? 'Sembunyikan' : 'Tampilkan'"
                    >
                      <i
                        :class="
                          showRawApiKey ? 'ri-eye-off-line' : 'ri-eye-line'
                        "
                      ></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-primary"
                      type="button"
                      @click="copyText(apiConfig.api_key, 'API Key')"
                    >
                      <i class="ri-file-copy-line"></i> Copy
                    </button>
                    <button
                      class="btn btn-sm btn-warning"
                      type="button"
                      :disabled="regeneratingKey"
                      @click="regenerateApiKey"
                    >
                      <span
                        v-if="regeneratingKey"
                        class="spinner-border spinner-border-sm me-1"
                      ></span>
                      <i v-else class="ri-refresh-line me-1"></i>
                      Generate Ulang
                    </button>
                  </div>
                  <small class="text-muted">
                    Kirim di header <code>X-API-Key</code> atau
                    <code>Authorization: Bearer &lt;API_KEY&gt;</code>.
                  </small>
                </div>
              </div>

              <h6 class="fw-semibold mb-2">1. Daftar Step Key Resmi (Urutan Timeline)</h6>
              <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm fs-13">
                  <thead class="table-light">
                    <tr>
                      <th width="70">Urutan</th>
                      <th width="160">step_key</th>
                      <th>Jabatan / Title pada Surat SK 6</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="s in apiConfig.step_keys" :key="s.step_key">
                      <td>{{ s.step_order }}</td>
                      <td><code>{{ s.step_key }}</code></td>
                      <td>{{ s.title }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <h6 class="fw-semibold mb-2">2. Daftar Endpoint API Eksternal</h6>
              <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm fs-13">
                  <thead class="table-light">
                    <tr>
                      <th width="90">Method</th>
                      <th>Endpoint</th>
                      <th>Fungsi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><span class="badge bg-info">GET</span></td>
                      <td><code>{{ apiConfig.base_url }}</code></td>
                      <td>
                        List SK 6 (query opsional: <code>?nim=...</code>,
                        <code>?prodi_id=...</code>,
                        <code>?jenis_kelamin=L|P</code> (atau <code>putra|putri</code>),
                        <code>?search=...</code>,
                        <code>?step_key=baku_kabag&amp;step_status=pending</code>,
                        <code>?ready_for_step=baku_kabag</code>,
                        <code>?approved_steps=kaprodi</code>,
                        <code>?pending_steps=baku_kabag</code>,
                        <code>?require_previous_approved=1</code>,
                        <code>?limit=15</code>)
                      </td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-info">GET</span></td>
                      <td><code>{{ apiConfig.base_url }}/by-nim/{nim}</code></td>
                      <td>Ambil detail &amp; timeline TTD SK 6 berdasarkan NIM</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-info">GET</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}</code></td>
                      <td>Ambil detail &amp; timeline TTD SK 6 berdasarkan ID</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-success">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/by-nim/{nim}/sign</code></td>
                      <td>Konfirmasi / centang TTD QR berdasarkan NIM mahasiswa</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-success">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}/sign</code></td>
                      <td>Konfirmasi / centang TTD QR berdasarkan ID SK 6</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-danger">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}/revoke</code></td>
                      <td>Batalkan konfirmasi TTD QR (body: <code>{"step_key":"baku_kabag"}</code>)</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <h6 class="fw-semibold mb-2">3. Filter Berjenjang &amp; Anti-Loncat Step (Opsional via Call API)</h6>
              <div class="alert alert-info fs-12 mb-3">
                <div class="fw-semibold mb-1">Agar data baru muncul &amp; bisa di-ACC setelah step sebelumnya disetujui:</div>
                <ul class="mb-0 ps-3">
                  <li>
                    <strong>Filter list siap proses (contoh KKN setelah Kaprodi ACC):</strong>
                    <code>GET {{ apiConfig.base_url }}?ready_for_step=kkn</code>
                    (sama dengan <code>?step_key=kkn&amp;step_status=pending&amp;require_previous_approved=1</code>)
                  </li>
                  <li>
                    <strong>Filter syarat step tertentu:</strong>
                    <code>?approved_steps=kaprodi</code> atau <code>?approved_steps=kaprodi,kkn&amp;pending_steps=keamanan</code>
                  </li>
                  <li>
                    <strong>Filter Putra / Putri (Jenis Kelamin):</strong>
                    <code>?jenis_kelamin=L</code> (atau <code>putra</code>) dan <code>?jenis_kelamin=P</code> (atau <code>putri</code>)
                  </li>
                  <li>
                    <strong>Kunci anti-loncat saat konfirmasi (POST /sign):</strong>
                    tambahkan <code>"require_previous_approved": true</code> (atau <code>"required_steps": ["kaprodi"]</code>) di JSON body; server akan menolak (<code>422</code>) jika step sebelumnya masih <code>pending</code>.
                  </li>
                </ul>
              </div>

              <h6 class="fw-semibold mb-2">4. Contoh Request Konfirmasi TTD (Misal dari Web Keuangan / BAKU Kabag &amp; BAKU AUPK)</h6>
              <pre class="bg-dark text-light p-3 rounded fs-12 mb-0" style="white-space: pre-wrap;">curl -X POST "{{ apiConfig.base_url }}/by-nim/202285290001/sign" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: {{ maskedApiKey(apiConfig.api_key) }}" \
  -d '{
    "step_key": "baku_kabag",
    "signer_name": "Kabag BAKU",
    "source": "web-keuangan",
    "notes": "Verifikasi BAKU Kabag selesai",
    "require_previous_approved": true
  }'</pre>
            </template>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showApiModal = false"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DEFAULT PENANDATANGAN (1 MODUL SK 6) -->
    <div
      v-if="showSignerDefaultsModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0, 0, 0, 0.5); overflow-y: auto"
    >
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form
          @submit.prevent="saveSignerDefaults"
          class="modal-content"
          style="max-height: calc(100vh - 3.5rem)"
        >
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-semibold mb-0">
                Pengaturan Default Penandatangan — SK 6 (1 Modul)
              </h5>
              <small class="text-muted">
                Atur pejabat &amp; jabatan default untuk 7 tahap SK 6 dan terapkan ke seluruh data modul ini.
              </small>
            </div>
            <button
              type="button"
              class="btn-close"
              @click="showSignerDefaultsModal = false"
            ></button>
          </div>

          <div class="modal-body" style="overflow-y: auto">
              <div v-if="loadingSignerDefaults" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 mb-0">Memuat data default penandatangan...</p>
              </div>

              <template v-else>
                <div
                  v-for="step in signerDefaults"
                  :key="step.step_key"
                  class="card border shadow-none mb-3"
                >
                  <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="fw-semibold fs-14">
                        {{ step.step_order }}. {{ step.short_label }}
                      </span>
                      <span class="badge bg-light text-dark border">
                        {{ step.step_key }}
                      </span>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-5">
                        <label class="form-label fs-12 mb-1">Jabatan / Title di Surat</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="step.title"
                          required
                        />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fs-12 mb-1">Nama Pejabat Default</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="step.signer_name"
                          :placeholder="step.auto_per_prodi_hint ? 'Kosongkan = Otomatis sesuai Prodi' : 'Nama lengkap + gelar'"
                        />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fs-12 mb-1">NIDN / NIY (Opsional)</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="step.signer_nidn"
                          placeholder="NIDN / NIY"
                        />
                      </div>
                    </div>
                    <small v-if="step.auto_per_prodi_hint" class="text-muted d-block mt-1">
                      Kosongkan Nama Pejabat agar tetap mengikuti Kaprodi masing-masing Prodi secara otomatis, atau isi untuk menimpa default seluruh data.
                    </small>
                  </div>
                </div>

                <div class="p-3 bg-light rounded border">
                  <div class="form-check form-switch mb-2">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="sk6ApplyDefaultsToExistingSwitch"
                      v-model="applyDefaultsToExisting"
                    />
                    <label
                      class="form-check-label fw-semibold"
                      for="sk6ApplyDefaultsToExistingSwitch"
                    >
                      Terapkan langsung ke seluruh data yang sudah ada di modul SK 6
                    </label>
                  </div>
                  <div v-if="applyDefaultsToExisting" class="d-flex gap-3 ps-4 flex-wrap">
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        id="sk6ScopeAll"
                        value="all"
                        v-model="applyDefaultsScope"
                      />
                      <label class="form-check-label fs-13" for="sk6ScopeAll">
                        Semua data (Pending &amp; Sudah TTD)
                      </label>
                    </div>
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        id="sk6ScopePending"
                        value="pending"
                        v-model="applyDefaultsScope"
                      />
                      <label class="form-check-label fs-13" for="sk6ScopePending">
                        Hanya tahap yang masih Pending / Menunggu
                      </label>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="showSignerDefaultsModal = false"
              >
                Batal
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="loadingSignerDefaults || savingSignerDefaults"
              >
                <span
                  v-if="savingSignerDefaults"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                Simpan &amp; Terapkan Default
              </button>
            </div>
        </form>
      </div>
    </div>
  </div>
</template>
