<script lang="ts">
import Swal from "sweetalert2";
import { defineComponent, onMounted, ref, watch } from "vue";
import type { ServerOptions } from "vue3-easy-data-table";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import router from "../../router";
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

    const showDetailModal = ref(false);
    const loadingDetail = ref(false);
    const selectedJadwal = ref<any>(null);
    const pesertaList = ref<any[]>([]);

    const headers = [
      { text: "No", value: "no", sortable: false },
      { text: "Tanggal Sidang", value: "tgl_sidang", sortable: true },
      { text: "Jumlah Mahasiswa", value: "mahasiswa_sidang_count", sortable: true },
      { text: "Action", value: "action", sortable: false },
    ];

    const serverOptions = ref<ServerOptions>({
      page: 1,
      rowsPerPage: 10,
      sortBy: "tgl_sidang",
      sortType: "desc",
    });

    function formatTanggal(dateStr?: string) {
      if (!dateStr) return "-";
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
      });
    }

    async function getData() {
      try {
        loading.value = true;
        const response = await apiGet("/jadwal-sidang", {
          page: serverOptions.value.page,
          limit: serverOptions.value.rowsPerPage,
          sortBy: serverOptions.value.sortBy,
          sortType: serverOptions.value.sortType,
          search: searchValue.value,
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
        toast.error("Gagal mengambil data jadwal sidang");
      } finally {
        loading.value = false;
      }
    }

    async function viewPeserta(item: any) {
      selectedJadwal.value = item;
      showDetailModal.value = true;
      loadingDetail.value = true;
      pesertaList.value = [];
      try {
        const res = await apiGet(`/jadwal-sidang/${item.id}`);
        if (res.success && res.data?.status) {
          pesertaList.value = res.data.data?.mahasiswa_sidang || [];
        } else {
          toast.error("Gagal memuat peserta sidang");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat memuat peserta sidang");
      } finally {
        loadingDetail.value = false;
      }
    }

    function edit(item: any) {
      router.push(`/jadwal-sidang/edit/${item.id}`);
    }

    function goEditFromModal(jadwalId: number) {
      showDetailModal.value = false;
      router.push(`/jadwal-sidang/edit/${jadwalId}`);
    }

    async function remove(item: any) {
      Swal.fire({
        title: "Konfirmasi Hapus",
        text: `Apakah Anda yakin ingin menghapus jadwal sidang ${formatTanggal(item.tgl_sidang)} beserta daftar mahasiswanya?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await apiDelete(`/jadwal-sidang/${item.id}`);
            if (response.success) {
              toast.success("Jadwal sidang berhasil dihapus");
              getData();
            } else {
              toast.error("Gagal menghapus jadwal sidang");
            }
          } catch (error) {
            toast.error("Terjadi kesalahan saat menghapus");
          }
        }
      });
    }

    function goAdd() {
      router.push("/jadwal-sidang/add");
    }

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;
    watch(searchValue, () => {
      if (searchTimeout) clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        serverOptions.value.page = 1;
        getData();
      }, 350);
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
      showDetailModal,
      loadingDetail,
      selectedJadwal,
      pesertaList,
      formatTanggal,
      getData,
      viewPeserta,
      edit,
      remove,
      goAdd,
      goEditFromModal,
    };
  },
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Jadwal Sidang</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="javascript:void(0);">Skripsi</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Jadwal Sidang
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
              <i class="ri-add-line align-middle me-1"></i> Tambah Jadwal
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
              placeholder="Cari tanggal / mahasiswa..."
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

        <template #item-tgl_sidang="item">
          <div>
            <strong>{{ formatTanggal(item.tgl_sidang) }}</strong>
            <div class="text-muted fs-11">{{ item.tgl_sidang }}</div>
          </div>
        </template>

        <template #item-mahasiswa_sidang_count="item">
          <button
            class="btn btn-sm btn-info-transparent"
            @click="viewPeserta(item)"
          >
            <i class="ri-group-line me-1"></i>
            {{ item.mahasiswa_sidang_count ?? 0 }} Mahasiswa
          </button>
        </template>

        <template #item-action="item">
          <div class="btn-list">
            <button
              class="btn btn-sm btn-icon btn-info-light btn-wave"
              title="Lihat Peserta"
              @click="viewPeserta(item)"
            >
              <i class="ri-eye-line"></i>
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

    <!-- Modal Peserta Sidang -->
    <div
      v-if="showDetailModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background-color: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Peserta Sidang — {{ formatTanggal(selectedJadwal?.tgl_sidang) }}
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="showDetailModal = false"
            ></button>
          </div>
          <div class="modal-body">
            <div v-if="loadingDetail" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="pesertaList.length === 0" class="text-center text-muted py-4">
              Belum ada mahasiswa terdaftar pada jadwal sidang ini.
            </div>
            <div v-else class="table-responsive">
              <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width: 45px">No</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Prodi</th>
                    <th>Judul Proposal Skripsi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ms, idx) in pesertaList" :key="ms.id">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ ms.pengajuan_judul?.nim || "-" }}</td>
                    <td>{{ ms.pengajuan_judul?.nama_mhs || "-" }}</td>
                    <td>{{ ms.pengajuan_judul?.prodi_rel?.nama || ms.pengajuan_judul?.prodi || "-" }}</td>
                    <td>{{ ms.pengajuan_judul?.judul_proposal_skripsi || "-" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button
              v-if="selectedJadwal?.id"
              type="button"
              class="btn btn-primary btn-sm"
              @click="goEditFromModal(selectedJadwal.id)"
            >
              <i class="ri-edit-line me-1"></i> Atur / Checklist Mahasiswa
            </button>
            <button
              type="button"
              class="btn btn-light btn-sm ms-auto"
              @click="showDetailModal = false"
            >
              Tutup
            </button>
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
</style>
