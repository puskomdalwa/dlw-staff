<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch } from "vue";
import { defineComponent } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { apiDelete, apiGet } from "../../services/api/request";
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
    const activeTab = ref<"all" | "non_skripsi" | "skripsi">("all");
    const total = ref(0);

    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "NIM", value: "nim", sortable: true },
      { text: "Nama Mahasiswa", value: "nama_mhs", sortable: true },
      { text: "Program Studi", value: "prodi.nama", sortable: false },
      { text: "Nama Dokumen / Kategori", value: "dokumen_info", sortable: false },
      { text: "Tipe Berkas", value: "tipe_berkas", sortable: false },
      { text: "Google Drive", value: "drive_link", sortable: false },
      { text: "Status", value: "status", sortable: false },
      { text: "Action", value: "action", sortable: false },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "id",
      sortType: "desc",
    });

    const loading = ref(false);

    async function getData() {
      try {
        loading.value = true;
        const params: any = {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
        };

        if (activeTab.value !== "all") {
          params.tipe_berkas = activeTab.value;
        }

        const response = await apiGet("/berkas", params);

        const rows = Array.isArray(response.data?.data?.data)
          ? response.data.data.data
          : Array.isArray(response.data?.data)
          ? response.data.data
          : [];

        const startNo =
          (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
        items.value = rows.map((row: any, idx: number) => ({
          ...row,
          no: startNo + idx + 1,
        }));

        total.value = response.data?.data?.total ?? rows.length;
      } catch (error) {
        console.error(error);
      } finally {
        loading.value = false;
      }
    }

    function edit(params: any) {
      router.push(`/berkas/edit/${params.id}`);
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: "Data berkas dan file di Google Drive akan dihapus!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete("/berkas/" + params.id);
            if (response.success && response.data?.status !== false) {
              toast.success("Berkas berhasil dihapus", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
              getData();
            } else {
              toast.error(response.data?.message || "Berkas gagal dihapus", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
            }
          } catch (error: any) {
            toast.error(error?.response?.data?.message || "Terjadi kesalahan saat menghapus data", {
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

    function setTab(tab: "all" | "non_skripsi" | "skripsi") {
      activeTab.value = tab;
      serverOptions.value.page = 1;
      getData();
    }

    function goAdd() {
      const tabParam = activeTab.value === "skripsi" ? "skripsi" : "non_skripsi";
      router.push({ path: "/berkas/add", query: { tab: tabParam } });
    }

    watch([searchValue, serverOptions], () => {
      getData();
    });

    onMounted(() => {
      getData();
    });

    return {
      headers,
      items,
      getData,
      edit,
      remove,
      total,
      goAdd,
      serverOptions,
      searchValue,
      loading,
      activeTab,
      setTab,
    };
  },
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Manajemen Berkas Mahasiswa</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Berkas</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Data Berkas</li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- 2 Tabs Filter + All -->
    <div class="card custom-card">
      <div class="card-header pb-0 border-bottom-0">
        <ul class="nav nav-tabs tab-style-2" role="tablist">
          <li class="nav-item">
            <a
              class="nav-link fs-14 py-2 px-4"
              :class="{ active: activeTab === 'all' }"
              href="javascript:void(0);"
              @click="setTab('all')"
            >
              <i class="ri-folder-shared-line me-1 align-middle"></i>
              Semua Berkas
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link fs-14 py-2 px-4"
              :class="{ active: activeTab === 'non_skripsi' }"
              href="javascript:void(0);"
              @click="setTab('non_skripsi')"
            >
              <i class="ri-file-list-3-line me-1 align-middle"></i>
              1. Non Skripsi
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link fs-14 py-2 px-4"
              :class="{ active: activeTab === 'skripsi' }"
              href="javascript:void(0);"
              @click="setTab('skripsi')"
            >
              <i class="ri-book-read-line me-1 align-middle"></i>
              2. Berkas Skripsi
            </a>
          </li>
        </ul>
      </div>

      <div class="card-body pt-3">
        <!-- Action Toolbar -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <button class="btn btn-primary btn-wave shadow-sm" @click="goAdd">
            <i class="ri-scan-2-line align-middle me-1"></i> Scan / Tambah Berkas
          </button>

          <div class="d-flex align-items-center gap-2">
            <div class="input-group" style="min-width: 280px;">
              <input
                type="text"
                class="form-control form-control-sm"
                v-model="searchValue"
                placeholder="Cari NIM, Nama, atau Kategori..."
              />
              <button class="btn btn-primary btn-sm" type="button" @click="getData">
                <i class="ri-search-line"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- DataTable -->
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

          <template #item="{ item, column }">
            <template v-if="column === 'dokumen_info'">
              <div>
                <div class="fw-semibold text-dark">
                  {{ item.nama_dokumen || item.kategori_berkas?.nama_kategori || "-" }}
                </div>
                <small
                  v-if="item.kategori_berkas?.nama_kategori && item.nama_dokumen !== item.kategori_berkas?.nama_kategori"
                  class="text-muted"
                >
                  Kategori: {{ item.kategori_berkas.nama_kategori }}
                </small>
              </div>
            </template>

            <template v-else-if="column === 'tipe_berkas'">
              <span
                class="badge"
                :class="item.tipe_berkas === 'skripsi' ? 'bg-success-transparent' : 'bg-primary-transparent'"
              >
                {{ item.tipe_berkas === 'skripsi' ? 'Berkas Skripsi' : 'Non Skripsi' }}
              </span>
            </template>

            <template v-else-if="column === 'drive_link'">
              <a
                v-if="item.drive_link"
                :href="item.drive_link"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-sm btn-outline-info rounded-pill px-2 py-1"
                title="Buka File di Google Drive"
              >
                <i class="ri-google-drive-line align-middle me-1"></i> Buka di Drive
              </a>
              <span v-else class="text-muted fs-12">-</span>
            </template>

            <template v-else-if="column === 'status'">
              <span v-if="item.status === 'uploaded'" class="badge bg-success">
                <i class="ri-check-line me-1"></i> Terunggah
              </span>
              <span v-else-if="item.status === 'failed'" class="badge bg-danger">
                <i class="ri-close-line me-1"></i> Gagal
              </span>
              <span v-else class="badge bg-warning">
                <i class="ri-time-line me-1"></i> Pending
              </span>
            </template>

            <template v-else-if="column === 'action'">
              <div class="btn-list">
                <button
                  class="btn btn-sm btn-icon btn-primary-light btn-wave"
                  title="Edit Berkas"
                  @click="edit(item)"
                >
                  <i class="ri-edit-line"></i>
                </button>
                <button
                  class="btn btn-sm btn-icon btn-danger-light btn-wave"
                  title="Hapus Berkas"
                  @click="remove(item)"
                >
                  <i class="ri-delete-bin-line"></i>
                </button>
              </div>
            </template>

            <template v-else>
              {{ item[column] }}
            </template>
          </template>
        </EasyDataTable>
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
