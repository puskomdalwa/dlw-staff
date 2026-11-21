<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormComponent from "../../../components/daftar_sidang_skripsi/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPost } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    FormComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const formData = ref({
      id: "",
      prodi_id: 0,
      nim: "",
      nama: "",
      nik: "",
      prodi: "",
      judul_skripsi: "",
      periode_sidang: "",
      dokumen: [] as any[],
    });

    async function getData() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/daftar-sidang-skripsi/${id}`);
        if (response.success && response.data?.status) {
          const d = response.data.data;
          formData.value = {
            id: d.id,
            prodi_id: d.prodi_id || 0,
            nim: d.nim || "",
            nama: d.nama || "",
            nik: d.nik || "",
            prodi: d.prodi || "",
            judul_skripsi: d.judul_skripsi || "",
            periode_sidang: d.periode_sidang || "",
            dokumen: d.dokumen || [],
          };
        } else {
          toast.error("Data tidak ditemukan");
          router.push({ path: "/daftar-sidang-skripsi" });
        }
      } catch (error) {
        toast.error("Gagal mengambil data pendaftaran sidang");
      } finally {
        loading.value = false;
      }
    }

    async function submit(payload: { formData: FormData; rawForm: any }) {
      try {
        errors.value = {};
        loading.value = true;
        const id = payload.rawForm.id || route.params.id;

        // Gunakan POST dengan _method=PUT agar upload file multipart tetap berjalan di PHP/Laravel
        payload.formData.append("_method", "PUT");

        const response = await apiPost(`/daftar-sidang-skripsi/${id}`, payload.formData);
        if (response.success == true && response.data?.status) {
          toast.success("Pendaftaran Sidang berhasil diperbarui", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/daftar-sidang-skripsi" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
            toast.error("Validasi gagal, silakan periksa inputan Anda");
          } else {
            toast.error(response.data?.message || "Gagal memperbarui data", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat memperbarui data");
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      getData();
    });

    return {
      formData,
      loading,
      submit,
      errors,
    };
  },
});
</script>

<template>
  <FormComponent
    v-if="formData.id"
    :modelValue="formData"
    @submit="submit"
    :isEdit="true"
    :errors="errors"
    :btnLoading="loading"
  />
  <div v-else class="text-center py-5">
    <div class="spinner-border text-primary" role="status"></div>
    <div class="mt-2 text-muted">Memuat data pendaftaran sidang...</div>
  </div>
</template>

