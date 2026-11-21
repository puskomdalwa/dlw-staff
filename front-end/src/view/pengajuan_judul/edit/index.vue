<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormComponent from "../../../components/pengajuan_judul/index.vue";
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
      prodi_id: "",
      nim: "",
      nama_mhs: "",
      prodi: "",
      judul_proposal_skripsi: "",
      rumusan_masalah: "",
      dosen_penasehat: "",
      catatan_kprodi: "",
      tanda_tangan_id: null as null | number,
      jenis_kelamin: "",
      tanggal: "",
      petanda_tangan: "tidak",
    });

    async function getSurat() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/pengajuan-judul/${id}`);
        if (response.data?.status) {
          const data = response.data.data;
          suratData.value = {
            id: data.id,
            prodi_id: data.prodi_id || "",
            nim: data.nim || "",
            nama_mhs: data.nama_mhs || "",
            prodi: data.prodi || "",
            judul_proposal_skripsi: data.judul_proposal_skripsi || "",
            rumusan_masalah: data.rumusan_masalah || "",
            dosen_penasehat: data.dosen_penasehat || "",
            catatan_kprodi: data.catatan_kprodi || "",
            tanda_tangan_id: data.tanda_tangan_id
              ? Number(data.tanda_tangan_id)
              : null,
            jenis_kelamin: data.jenis_kelamin || "",
            tanggal: data.tanggal || "",
            petanda_tangan: data.petanda_tangan || "tidak",
          };
        }
      } catch (error) {
        toast.error("Gagal mengambil data pengajuan judul");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPut(`/pengajuan-judul/${form.id}`, form);
        if (response.success == true) {
          toast.success("Pengajuan Judul berhasil diupdate", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/pengajuan-judul" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
          } else {
            toast.error("Pengajuan Judul gagal diupdate", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengupdate data", {
          theme: "auto",
          icon: true,
          hideProgressBar: true,
          autoClose: true,
          position: "top-right",
        });
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
  <FormComponent
    @submit="submit"
    :modelValue="suratData"
    :isEdit="true"
    :errors="errors"
    :btnLoading="loading"
  />
</template>

