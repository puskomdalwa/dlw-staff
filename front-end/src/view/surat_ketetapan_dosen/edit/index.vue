<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import FormComponent from "../../../components/surat_ketetapan_dosen/index.vue";
import router from "../../../router";
import { apiGet, apiPut } from "../../../services/api/request";

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
      institusi_id: 0,
      no_surat: "",
      nomor: "",
      nama_dosen: "",
      jabatan: "",
      nidn: "",
      instansi: "",
      jenis_kelamin: "L",
      alamat_penandatangan: "",
      tempat_ditetapkan: "",
      tanggal: "",
      petanda_tangan: "ya",
    });

    async function getSurat() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/surat-ketetapan-dosen/${id}`);
        if (response.data && response.data.status) {
          const data = response.data.data;
          suratData.value = {
            id: data.id,
            institusi_id: data.institusi_id || 0,
            no_surat: data.no_surat || "",
            nomor: data.nomor || "",
            nama_dosen: data.nama_dosen || "",
            jabatan: data.jabatan || "",
            nidn: data.nidn || "",
            instansi: data.instansi || "",
            jenis_kelamin: data.jenis_kelamin || "L",
            alamat_penandatangan:
              data.alamat_penandatangan || "Jl. Raya Raci No. 51 Bangil-Pasuruan",
            tempat_ditetapkan: data.tempat_ditetapkan || "Bangil",
            tanggal: data.tanggal ? String(data.tanggal).slice(0, 10) : "",
            petanda_tangan: data.petanda_tangan || "ya",
          };
        } else {
          toast.error("Data surat tidak ditemukan");
          router.push({ path: "/surat-ketetapan-dosen" });
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
        const response = await apiPut(`/surat-ketetapan-dosen/${form.id}`, form);
        if (response.success === true && response.data?.status !== false) {
          toast.success("Surat Ketetapan Dosen berhasil diupdate", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/surat-ketetapan-dosen" });
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
            toast.error(errRes?.message || "Surat gagal diupdate", {
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
