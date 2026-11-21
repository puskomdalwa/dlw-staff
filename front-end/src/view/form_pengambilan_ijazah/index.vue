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
      { text: "No", value: "no", sortable: false, width: 60 },
      { text: "Nama Mahasiswa", value: "nama_mhs", sortable: true },
      { text: "NIM/NRIM", value: "nim", sortable: true, width: 130 },
      { text: "Program Studi", value: "prodi", sortable: true },
      { text: "Dosen Pembimbing", value: "dosen_pembimbing", sortable: true },
      { text: "Tanggal Sidang", value: "tanggal_sidang", sortable: true, width: 130 },
      { text: "Timeline TTD (QR)", value: "signatures", sortable: false, width: 280 },
      { text: "URL Drive", value: "drive_link", sortable: false, width: 240 },
      { text: "Aksi", value: "action", sortable: false, width: 170 },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "id",
      sortType: "desc",
    });

    const loading = ref(false);

    // Timeline TTD QR State
    const showTimelineModal = ref(false);
    const timelineLoading = ref(false);
    const activeForm = ref<any>(null);
    const timelineSteps = ref<any[]>([]);
    const actionStepLoading = ref<string | null>(null);
    const editingStepKey = ref<string | null>(null);
    const customSignerName = ref("");
    const customNotes = ref("");

    // API Integration State
    const showApiModal = ref(false);
    const apiConfigLoading = ref(false);
    const regeneratingKey = ref(false);
    const showRawApiKey = ref(false);
    const apiConfig = ref<any>({
      base_url: "",
      verify_base_url: "",
      api_key: "",
      step_keys: [],
    });

    // Signer Defaults State (Module-wide default pejabat/penandatangan)
    const showSignerDefaultsModal = ref(false);
    const loadingSignerDefaults = ref(false);
    const savingSignerDefaults = ref(false);
    const signerDefaults = ref<any[]>([]);
    const applyDefaultsToExisting = ref(true);
    const applyDefaultsScope = ref<"all" | "pending">("all");

    // Setting Form Publik State
    const settingModalRef = ref<HTMLElement | null>(null);
    let settingModal: Modal | null = null;
    const loadingSetting = ref(false);
    const savingSetting = ref(false);
    const publicSetting = ref<any>({
      is_active: true,
      slug: "pengambilan-ijazah",
      title: "Formulir Pengambilan Ijazah (Bebas Tanggungan)",
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
        const res = await apiGet("/form-pengambilan-ijazah/public-setting");
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
        const res = await apiPost("/form-pengambilan-ijazah/public-setting", {
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
      return `${base}/form-public/pengambilan-ijazah/${publicSetting.value.slug || 'pengambilan-ijazah'}`;
    }

    function copyPublicLink() {
      const link = getFullPublicUrl();
      navigator.clipboard.writeText(link).then(() => {
        toast.success("Tautan form publik berhasil disalin ke clipboard!");
      }).catch(() => {
        toast.info(link);
      });
    }

    function testOpenPublicLink() {
      window.open(getFullPublicUrl(), "_blank");
    }

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/form-pengambilan-ijazah", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
        });

        const rows = Array.isArray(response.data?.data?.data)
          ? response.data.data.data
          : [];

        const startNo =
          (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
        items.value = rows.map((row: any, idx: number) => ({
          ...row,
          no: startNo + idx + 1,
        }));

        total.value = response.data?.data?.total || 0;
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
          `/form-pengambilan-ijazah/download-pdf/${params.id}`,
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

    function goAdd() {
      router.push("/form-pengambilan-ijazah/add");
    }

    function edit(params: any) {
      router.push(`/form-pengambilan-ijazah/edit/${params.id}`);
    }

    async function openTimeline(item: any) {
      showTimelineModal.value = true;
      timelineLoading.value = true;
      editingStepKey.value = null;
      activeForm.value = {
        id: item.id,
        nama_mhs: item.nama_mhs,
        nim: item.nim,
        prodi: item.prodi || "-",
        signed_count: item.signed_count ?? 0,
        total_steps: item.total_steps ?? 4,
        is_completed: Boolean(item.is_completed),
      };
      try {
        const res = await apiGet(`/form-pengambilan-ijazah/${item.id}/signatures`);
        if (res.success && res.data?.status) {
          activeForm.value = res.data.data.form;
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

    function toggleEditStep(step: any) {
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

    async function confirmSignature(step: any) {
      if (!activeForm.value) return;
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
          `/form-pengambilan-ijazah/${activeForm.value.id}/signatures/${step.step_key}/confirm`,
          payload
        );
        if (res.success && res.data?.status) {
          timelineSteps.value = res.data.data.steps || [];
          activeForm.value = {
            ...activeForm.value,
            signed_count: res.data.data.signed_count,
            total_steps: res.data.data.total_steps,
            is_completed: res.data.data.is_completed,
          };
          editingStepKey.value = null;
          toast.success(`TTD QR ${step.title} berhasil dikonfirmasi`);
          getData();
        } else {
          toast.error(res.data?.message || "Gagal mengkonfirmasi TTD QR");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat konfirmasi TTD QR");
      } finally {
        actionStepLoading.value = null;
      }
    }

    async function revokeSignature(step: any) {
      if (!activeForm.value) return;
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
          `/form-pengambilan-ijazah/${activeForm.value.id}/signatures/${step.step_key}/revoke`,
          {}
        );
        if (res.success && res.data?.status) {
          timelineSteps.value = res.data.data.steps || [];
          activeForm.value = {
            ...activeForm.value,
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
        const res = await apiGet("/form-pengambilan-ijazah/integration-config");
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
        const res = await apiPost(
          "/form-pengambilan-ijazah/integration-config/regenerate",
          {}
        );
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
        const res = await apiGet("/form-pengambilan-ijazah/signer-defaults");
        if (res.success && res.data?.status) {
          signerDefaults.value = (res.data.data || []).map((s: any) => ({
            ...s,
            signer_nidn: s.signer_nidn ?? "",
          }));
        } else {
          toast.error("Gagal mengambil default penandatangan");
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat memuat default penandatangan");
      } finally {
        loadingSignerDefaults.value = false;
      }
    }

    async function saveSignerDefaults() {
      savingSignerDefaults.value = true;
      try {
        const res = await apiPost("/form-pengambilan-ijazah/signer-defaults", {
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
            res.data.message || "Default penandatangan berhasil disimpan"
          );
          showSignerDefaultsModal.value = false;
          getData();
        } else {
          toast.error(
            res.data?.message || "Gagal menyimpan default penandatangan"
          );
        }
      } catch (e) {
        toast.error("Terjadi kesalahan saat menyimpan default penandatangan");
      } finally {
        savingSignerDefaults.value = false;
      }
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: "Data formulir yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete(
              "/form-pengambilan-ijazah/" + params.id
            );
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
              toast.error("Data gagal dihapus");
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus data");
          }
        }
      });
    }

    watch(searchValue, () => {
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
      getData();
      getPublicSetting();
    });

    return {
      headers,
      items,
      total,
      loading,
      searchValue,
      serverOptions,
      getData,
      goAdd,
      edit,
      remove,
      download,
      // Public Form Setting
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
      // Timeline TTD & Integration
      showTimelineModal,
      timelineLoading,
      activeForm,
      timelineSteps,
      actionStepLoading,
      editingStepKey,
      customSignerName,
      customNotes,
      openTimeline,
      closeTimelineModal,
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
      // Signer Defaults
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
        Formulir Pengambilan Ijazah
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Ijazah</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Formulir Pengambilan Ijazah
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
              <i class="ri-add-line align-middle me-1"></i> Tambah Formulir
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
            <button
              class="btn btn-info-light btn-wave shadow-sm"
              @click="openApiDocsModal"
            >
              <i class="ri-key-2-line align-middle me-1"></i> API &amp; Integrasi TTD
            </button>
            <button
              class="btn btn-warning-light btn-wave shadow-sm"
              @click="openSignerDefaultsModal"
            >
              <i class="ri-user-settings-line align-middle me-1"></i> Default Penandatangan
            </button>
          </div>
          <div class="col-12 col-md-4 ms-auto">
            <div class="input-group">
              <input
                type="text"
                class="form-control form-control-sm"
                v-model="searchValue"
                placeholder="Cari (Nama/NIM/Prodi/Dosen)..."
              />
              <button class="btn btn-primary btn-sm" type="button">
                <i class="ri-search-line"></i>
              </button>
            </div>
          </div>
        </div>
      </template>

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
          <div class="text-center py-4">
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
                  (item.signed_count || 0) === (item.total_steps || 4)
                    ? 'bg-success-transparent'
                    : (item.signed_count || 0) > 0
                    ? 'bg-info-transparent'
                    : 'bg-warning-transparent'
                "
              >
                {{ item.signed_count || 0 }}/{{ item.total_steps || 4 }} TTD QR
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
                :title="`${step.title}: ${step.signer_name || '-'}`"
              >
                <i
                  :class="
                    step.is_signed ? 'ri-check-line me-1' : 'ri-time-line me-1'
                  "
                ></i>
                {{ step.short_label || step.step_key }}
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
              Tanpa TTD: {{ item.status === 'failed' ? 'gagal upload' : 'proses upload' }}
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
              Ber-TTD: {{ item.signed_count || 0 }}/{{ item.total_steps || 4 }}
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
              <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="ri-global-line fs-20 text-white"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0 text-white" id="publicSettingModalLabel">
                  Pengaturan Formulir Publik
                </h5>
                <small class="text-white-50 fs-12">
                  Atur tautan dan masa aktif formulir agar mahasiswa/publik bisa mengisi data tanpa login
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
                      <i :class="publicSetting.is_active && publicSetting.is_available ? 'ri-check-line' : 'ri-close-line'" class="me-1"></i>
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
                  <span class="fs-13 text-muted ms-1">pengisian</span>
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
                    Jika dinonaktifkan, mahasiswa yang mengakses tautan akan melihat halaman penutupan formulir.
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
                  <span class="input-group-text bg-light text-muted fs-13">/form-public/pengambilan-ijazah/</span>
                  <input
                    type="text"
                    class="form-control"
                    v-model="publicSetting.slug"
                    placeholder="pengambilan-ijazah"
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
                  <i class="ri-timer-line me-1 text-primary"></i> Atur Masa Aktif Formulir
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

                  <!-- Input Nilai Durasi (Menit, Jam, Hari) -->
                  <div
                    v-if="['minutes', 'hours', 'days'].includes(publicSetting.duration_type)"
                    class="col-md-6"
                  >
                    <label class="form-label text-muted fs-12">Jumlah Durasi Aktif:</label>
                    <div class="input-group">
                      <input
                        type="number"
                        min="1"
                        class="form-control"
                        v-model.number="publicSetting.duration_value"
                        placeholder="Contoh: 30"
                        required
                      />
                      <span class="input-group-text bg-light text-muted">
                        {{ publicSetting.duration_type === 'minutes' ? 'Menit' : (publicSetting.duration_type === 'hours' ? 'Jam' : 'Hari') }}
                      </span>
                    </div>
                    <small class="text-muted fs-11 mt-1 d-block">
                      * Dihitung mulai dari waktu tombol "Simpan Pengaturan" ditekan.
                    </small>
                  </div>

                  <!-- Input Custom Tanggal & Waktu -->
                  <div
                    v-if="publicSetting.duration_type === 'custom'"
                    class="col-md-6"
                  >
                    <label class="form-label text-muted fs-12">Berlaku Sampai Tanggal & Jam:</label>
                    <input
                      type="datetime-local"
                      class="form-control"
                      v-model="publicSetting.custom_expired_at"
                      required
                    />
                  </div>
                </div>
              </div>

              <!-- Footer Buttons -->
              <div class="d-flex justify-content-end gap-2 pt-2">
                <button
                  type="button"
                  class="btn btn-light px-4"
                  @click="closeSettingModal"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="btn btn-primary px-4 fw-semibold"
                  :disabled="savingSetting"
                >
                  <span v-if="savingSetting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  <i v-else class="ri-save-line me-1"></i>
                  Simpan Pengaturan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- ==================== END MODAL PENGATURAN ==================== -->

    <!-- ==================== MODAL TIMELINE TTD QR ==================== -->
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
                Timeline Tanda Tangan QR — Form Pengambilan Ijazah
              </h5>
              <small class="text-muted" v-if="activeForm">
                {{ activeForm.nama_mhs }} ({{ activeForm.nim || "-" }})
                <span v-if="activeForm.prodi">
                  • {{ activeForm.prodi }}
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
                v-if="activeForm"
                class="card border bg-light-subtle mb-3 shadow-none"
              >
                <div class="card-body py-2 px-3">
                  <div
                    class="d-flex justify-content-between align-items-center mb-1"
                  >
                    <span class="fw-semibold fs-13">Progress Konfirmasi TTD</span>
                    <span class="badge bg-primary">
                      {{ activeForm.signed_count || 0 }} /
                      {{ activeForm.total_steps || 4 }} Terkonfirmasi
                    </span>
                  </div>
                  <div class="progress" style="height: 8px">
                    <div
                      class="progress-bar bg-success"
                      role="progressbar"
                      :style="{
                        width: `${
                          ((activeForm.signed_count || 0) /
                            (activeForm.total_steps || 4)) *
                          100
                        }%`,
                      }"
                    ></div>
                  </div>
                </div>
              </div>

              <div class="pij-timeline">
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
                                {{ step.title }}
                              </span>
                              <span class="badge bg-light text-dark border">
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
                                (NIDN/NIY: {{ step.signer_nidn }})
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
                          v-if="editingStepKey === step.step_key"
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
              v-if="activeForm && activeForm.is_completed"
              type="button"
              class="btn btn-info-light"
              @click="download({ id: activeForm.id })"
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

    <!-- ==================== MODAL API & DOKUMENTASI INTEGRASI ==================== -->
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
                API &amp; Dokumentasi Integrasi TTD QR Form Pengambilan Ijazah
              </h5>
              <small class="text-muted">
                Hubungkan website eksternal (Keuangan Pondok, BAKU, BAAK, Wakil Rektor I) untuk konfirmasi TTD QR.
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
                      <th width="180">step_key</th>
                      <th>Jabatan / Unit pada Form Pengambilan Ijazah</th>
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
                        List Form Pengambilan Ijazah (query opsional: <code>?nim=...</code>,
                        <code>?search=...</code>,
                        <code>?step_key=keuangan_pondok&amp;step_status=pending</code>,
                        <code>?limit=15</code>)
                      </td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-info">GET</span></td>
                      <td><code>{{ apiConfig.base_url }}/by-nim/{nim}</code></td>
                      <td>Ambil detail &amp; timeline TTD berdasarkan NIM</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-info">GET</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}</code></td>
                      <td>Ambil detail &amp; timeline TTD berdasarkan ID</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-success">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/by-nim/{nim}/sign</code></td>
                      <td>Konfirmasi / centang TTD QR berdasarkan NIM mahasiswa</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-success">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}/sign</code></td>
                      <td>Konfirmasi / centang TTD QR berdasarkan ID</td>
                    </tr>
                    <tr>
                      <td><span class="badge bg-danger">POST</span></td>
                      <td><code>{{ apiConfig.base_url }}/{id}/revoke</code></td>
                      <td>Batalkan konfirmasi TTD QR (body: <code>{"step_key":"keuangan_pondok"}</code>)</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <h6 class="fw-semibold mb-2">3. Contoh Request Konfirmasi TTD (Misal dari Web Keuangan Pondok)</h6>
              <pre class="bg-dark text-light p-3 rounded fs-12 mb-0" style="white-space: pre-wrap;">curl -X POST "{{ apiConfig.base_url }}/by-nim/202285290001/sign" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: {{ maskedApiKey(apiConfig.api_key) }}" \
  -d '{
    "step_key": "keuangan_pondok",
    "signer_name": "Ust. Ahmad",
    "source": "web-keuangan-pondok",
    "notes": "Bebas tanggungan keuangan pondok"
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

    <!-- ==================== MODAL DEFAULT PENANDATANGAN MODUL ==================== -->
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
                Pengaturan Default Penandatangan (1 Modul)
              </h5>
              <small class="text-muted">
                Atur pejabat &amp; jabatan default untuk 4 tahap Form Pengambilan Ijazah dan terapkan ke seluruh data modul ini.
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
                        <label class="form-label fs-12 mb-1">Jabatan / Title di PDF</label>
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
                          placeholder="Nama lengkap + gelar"
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
                  </div>
                </div>

                <div class="p-3 bg-light rounded border">
                  <div class="form-check form-switch mb-2">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="applyDefaultsToExistingSwitch"
                      v-model="applyDefaultsToExisting"
                    />
                    <label
                      class="form-check-label fw-semibold"
                      for="applyDefaultsToExistingSwitch"
                    >
                      Terapkan langsung ke seluruh data yang sudah ada di modul ini
                    </label>
                  </div>
                  <div v-if="applyDefaultsToExisting" class="d-flex gap-3 ps-4 flex-wrap">
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        id="scopeAll"
                        value="all"
                        v-model="applyDefaultsScope"
                      />
                      <label class="form-check-label fs-13" for="scopeAll">
                        Semua data (Pending &amp; Sudah TTD)
                      </label>
                    </div>
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        id="scopePending"
                        value="pending"
                        v-model="applyDefaultsScope"
                      />
                      <label class="form-check-label fs-13" for="scopePending">
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

<style scoped>
.btn-icon {
  width: 2rem;
  height: 2rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>
