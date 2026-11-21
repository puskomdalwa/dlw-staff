<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormComponent from "../../../components/surat_keterangan_sudah_sidang/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPut } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    FormComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const suratData = ref({
      id: "",
      no_surat: "",
      prodi_id: 0,
      nama: "",
      nim: "",
      prodi: "",
      tanggal_sidang: "",
      tanggal: "",
      kepala: "",
      nidn: "",
      petanda_tangan: "ya",
    });

    async function getSurat() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/skss/${id}`);
        if (response.data && response.data.status) {
          const data = response.data.data;
          suratData.value = {
            id: data.id,
            no_surat: data.no_surat || data.nomor_surat || "",
            prodi_id: data.prodi_id || 0,
            nama: data.nama || "",
            nim: data.nim || "",
            prodi: data.prodi || "",
            tanggal_sidang: data.tanggal_sidang ? data.tanggal_sidang.slice(0, 10) : "",
            tanggal: data.tanggal ? data.tanggal.slice(0, 10) : "",
            kepala: data.kepala || "Kuzairi, M.Pd",
            nidn: data.nidn || "80283",
            petanda_tangan: data.petanda_tangan || "ya",
          };
        }
      } catch (error) {
        toast.error("Gagal mengambil data surat");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPut(`/skss/${form.id}`, form);
        if (response.success == true && response.data?.status !== false) {
          toast.success("Surat berhasil diupdate", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/skss" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
            toast.error("Validasi gagal, silakan periksa formulir", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          } else {
            const msg = response.data?.message || (response.error as any)?.response?.data?.message || "Surat gagal diupdate";
            toast.error(msg, {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengupdate surat");
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      getSurat();
    });

    return {
      suratData,
      submit,
      loading,
      errors,
    };
  },
});
</script>

<template>
  <FormComponent @submit="submit" :modelValue="suratData" :isEdit="true" :errors="errors" :btnLoading="loading" />
</template>

