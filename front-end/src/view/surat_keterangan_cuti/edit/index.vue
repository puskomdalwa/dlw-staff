<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormComponent from "../../../components/surat_keterangan_cuti/index.vue";
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
      prodi_id: 0,
      nama_mhs: "",
      nim: "",
      dari_semester: "",
      sampai_semester: "",
      jenis_kelamin: "L",
      alasan: "",
      tanggal: "",
      petanda_tangan: "ya",
    });

    async function getSurat() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/skc/${id}`);
        if (response.data && response.data.status) {
          const data = response.data.data;
          suratData.value = {
            id: data.id,
            prodi_id: data.prodi_id || 0,
            nama_mhs: data.nama_mhs || "",
            nim: data.nim || "",
            dari_semester: data.dari_semester || "",
            sampai_semester: data.sampai_semester || "",
            jenis_kelamin: data.jenis_kelamin || "L",
            alasan: data.alasan || "",
            tanggal: data.tanggal ? data.tanggal.slice(0, 10) : "",
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
        const response = await apiPut(`/skc/${form.id}`, form);
        if (response.success == true && response.data?.status !== false) {
          toast.success("Surat berhasil diupdate", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/skc" });
        } else {
          const errRes = (response as any).error?.response?.data || response.data;
          if ((response.error as any)?.response?.status === 422 && errRes?.errors) {
            errors.value = errRes.errors;
            toast.error("Validasi gagal, silakan periksa formulir", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          } else {
            const msg = errRes?.message || "Surat gagal diupdate";
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
