<script lang="ts">
import Swal from "sweetalert2";
import { defineComponent, onMounted, ref, watch } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import router from "../../router";
import { BASE_URL } from "../../services/api/http";
import { apiDelete, apiGet } from "../../services/api/request";
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

    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "Nama Institusi", value: "nama_institusi", sortable: true },
      { text: "Rektor", value: "rektor", sortable: true },
      { text: "Kop Surat", value: "kop_surat", sortable: false },
      { text: "Action", value: "action", sortable: false },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "id",
      sortType: "desc",
    });

    function getImageUrl(path?: string | null): string {
      if (!path) return "";
      return `${BASE_URL.replace("/api", "")}/${path}`;
    }

    async function getData() {
      loading.value = true;
      try {
        const params = {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
        };
        const response = await apiGet("/institusi", params);
        if (response.success) {
          const paginated = response.data?.data;
          const rows = paginated?.data ?? [];
          const startNo =
            (serverOptions.value.page - 1) * serverOptions.value.rowsPerPage;
          items.value = rows.map((row: any, idx: number) => ({
            ...row,
            no: startNo + idx + 1,
          }));
          total.value = paginated?.total ?? rows.length;
        }
      } catch (error) {
        console.error(error);
        toast.error("Gagal mengambil data institusi");
      } finally {
        loading.value = false;
      }
    }

    function edit(item: any) {
      router.push(`/institusi/edit/${item.id}`);
    }

    function remove(item: any) {
      Swal.fire({
        title: "Hapus Data?",
        text: `Apakah Anda yakin ingin menghapus "${item.nama_institusi}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          const response = await apiDelete(`/institusi/${item.id}`);
          if (response.success) {
            toast.success("Data institusi berhasil dihapus");
            getData();
          } else {
            const errMsg =
              (response as any).error?.response?.data?.message ||
              "Gagal menghapus data institusi";
            toast.error(errMsg);
          }
        }
      });
    }

    function goAdd() {
      router.push("/institusi/add");
    }

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;
    watch(searchValue, () => {
      if (searchTimeout) clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        serverOptions.value.page = 1;
        getData();
      }, 400);
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
    });

    return {
      headers,
      items,
      total,
      loading,
      searchValue,
      serverOptions,
      getData,
      getImageUrl,
      edit,
      remove,
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
      <h1 class="page-title fw-semibold fs-18 mb-0">Data Institusi</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Data</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Institusi</li>
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
              placeholder="Cari institusi / rektor..."
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

        <template #item-rektor="item">
          <span>{{ item.rektor || "-" }}</span>
        </template>

        <template #item-kop_surat="item">
          <template v-if="item.kop_surat">
            <img
              :src="getImageUrl(item.kop_surat)"
              alt="Kop Surat"
              class="img-thumbnail"
              style="max-height: 55px; max-width: 180px; object-fit: contain"
            />
          </template>
          <template v-else>
            <span class="text-muted">-</span>
          </template>
        </template>

        <template #item-action="item">
          <div class="btn-list">
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
