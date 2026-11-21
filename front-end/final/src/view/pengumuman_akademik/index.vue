<script lang="ts">
import Swal from "sweetalert2";
import { onMounted, ref, watch } from "vue";
import { defineComponent } from "vue";
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
    const items = ref([]);
    const searchValue = ref("");
    const total = ref(0);
    const headers = [
      { text: "No", value: "no", sortable: false, width: 60 },
      { text: "Nomor Surat", value: "nomor_surat", sortable: true, width: 220 },
      { text: "Judul / Tentang", value: "judul", sortable: true },
      { text: "Kepada", value: "kepada", sortable: true },
      { text: "Tanggal", value: "tanggal", sortable: true, width: 120 },
      { text: "URL Drive", value: "drive_link", sortable: false, width: 140 },
      { text: "Aksi", value: "action", sortable: false, width: 140 },
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
        const response = await apiGet("/pengumuman-akademik", {
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

    function edit(params: any) {
      router.push(`/pengumuman-akademik/edit/${params.id}`);
    }

    async function remove(params: any) {
      Swal.fire({
        title: "Apakah anda yakin?",
        text: "Data pengumuman yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete("/pengumuman-akademik/" + params.id);
            if (response.success && response.data?.status !== false) {
              toast.success("Pengumuman berhasil dihapus", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
              getData();
            } else {
              toast.error("Gagal menghapus pengumuman", {
                theme: "auto",
                icon: true,
                hideProgressBar: true,
                autoClose: true,
                position: "top-right",
              });
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus data");
          }
        }
      });
    }

    watch(searchValue, () => {
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
          `/pengumuman-akademik/download-pdf/${params.id}`,
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
      getData();
    });

    function goAdd() {
      return router.push("/pengumuman-akademik/add");
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
        Pengumuman Akademik
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">BAAK</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Pengumuman Akademik</li>
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
        </div>
      </template>

      <div class="row mb-3">
        <div class="col-md-3 ms-auto">
          <div class="input-group">
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="searchValue"
              placeholder="Cari pengumuman..."
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

        <template #item-judul="item">
          <span class="fw-semibold">{{ item.judul || '-' }}</span>
        </template>

        <template #item-kepada="item">
          <span>{{ item.kepada || '-' }}</span>
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
            <span class="badge bg-warning-transparent">Proses Drive</span>
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
