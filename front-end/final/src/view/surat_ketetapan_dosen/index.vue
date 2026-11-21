<script lang="ts">
import Swal from "sweetalert2";
import { defineComponent, onMounted, ref, watch } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import router from "../../router";
import { apiDelete, apiGet, apiPdf } from "../../services/api/request";
import SimpleCardComponent from "../../shared/components/@spk/simple-card.vue";

export default defineComponent({
  components: {
    SimpleCardComponent,
  },
  setup() {
    const items = ref<any[]>([]);
    const searchValue = ref("");
    const total = ref(0);
    const loading = ref(false);
    const listInstitusi = ref<any[]>([]);
    const institusiFilter = ref("");

    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "Nomor Surat", value: "nomor", sortable: true },
      { text: "Nama Dosen", value: "nama_dosen", sortable: true },
      { text: "URL", value: "drive_link", sortable: false },
      { text: "Action", value: "action", sortable: false },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "id",
      sortType: "desc",
    });

    async function getInstitusi() {
      try {
        const response = await apiGet("/institusi", { all: true });
        if (response.success) {
          const data = response.data?.data ?? response.data;
          listInstitusi.value = Array.isArray(data) ? data : data ? [data] : [];
        }
      } catch (error) {}
    }

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/surat-ketetapan-dosen", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
          institusi_id: institusiFilter.value,
        });

        if (response.success) {
          const rows = response.data?.data?.data || [];
          const startNo =
            (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
          items.value = rows.map((row: any, idx: number) => ({
            ...row,
            no: startNo + idx + 1,
          }));
          total.value = response.data?.data?.total || 0;
        }
      } catch (error) {
        toast.error("Gagal mengambil data surat ketetapan dosen");
      } finally {
        loading.value = false;
      }
    }

    function openFileExport(blob: any) {
      const pdfBlob =
        blob instanceof Blob && blob.type === "application/pdf"
          ? blob
          : new Blob([blob], { type: "application/pdf" });
      const url = URL.createObjectURL(pdfBlob);
      window.open(url, "_blank", "noopener");
      setTimeout(() => URL.revokeObjectURL(url), 10_000);
    }

    async function download(item: any) {
      Swal.fire({
        title: "Sedang Memproses...",
        text: "Mohon tunggu sejenak, dokumen sedang digenerate.",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      try {
        const res = await apiPdf(
          `/surat-ketetapan-dosen/download-pdf/${item.id}`,
          {},
          { responseType: "blob" }
        );
        openFileExport(res.data);
        Swal.close();
      } catch (error) {
        Swal.close();
        toast.error("Gagal membuka file PDF");
      }
    }

    function edit(item: any) {
      router.push(`/surat-ketetapan-dosen/edit/${item.id}`);
    }

    async function remove(item: any) {
      Swal.fire({
        title: "Konfirmasi Hapus",
        text: `Apakah Anda yakin ingin menghapus surat ketetapan dosen "${item.nama_dosen}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete(
              `/surat-ketetapan-dosen/${item.id}`
            );
            if (response.success) {
              toast.success("Data berhasil dihapus");
              getData();
            } else {
              toast.error("Gagal menghapus data");
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus");
          }
        }
      });
    }

    function goAdd() {
      router.push("/surat-ketetapan-dosen/add");
    }

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;
    watch(
      () => searchValue.value,
      () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          serverOptions.value.page = 1;
          getData();
        }, 350);
      }
    );

    watch(
      () => institusiFilter.value,
      () => {
        serverOptions.value.page = 1;
        getData();
      }
    );

    watch(
      serverOptions,
      () => {
        getData();
      },
      { deep: true }
    );

    onMounted(() => {
      getInstitusi();
      getData();
    });

    return {
      headers,
      items,
      total,
      loading,
      searchValue,
      serverOptions,
      listInstitusi,
      institusiFilter,
      getData,
      edit,
      remove,
      download,
      goAdd,
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
        Surat Ketetapan Dosen
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Surat</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Surat Ketetapan Dosen
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
              @click="goAdd"
            >
              <i class="ri-add-line align-middle me-1"></i> Tambah Data
            </button>
          </div>
          <div class="col-12 col-md-auto ms-auto" v-if="listInstitusi.length > 1">
            <select
              class="form-select form-select-sm"
              style="min-width: 220px"
              v-model="institusiFilter"
            >
              <option value="">Semua Institusi</option>
              <option v-for="item in listInstitusi" :key="item.id" :value="item.id">
                {{ item.nama_institusi }}
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
              placeholder="Cari (Nama Dosen / Nomor Surat)..."
            />
            <button class="btn btn-primary btn-sm" type="button" @click="getData">
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

        <template #item-nomor="item">
          <span>{{ item.nomor || "-" }}</span>
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
            <span class="badge bg-warning-transparent">proses upload google drive</span>
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
