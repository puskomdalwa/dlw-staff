<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormComponent from "../../../components/sk_yudisium/index.vue";
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
      nim: "",
      nama_mhs: "",
      prodi: "",
      no_surat: "",
      nomor_surat: "",
      nilai_skripsi: "",
      tanda_tangan_id: null as null | number,
      jenis_kelamin: "L",
      tanggal: "",
      petanda_tangan: "tidak",
    });

    async function getSurat() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/sk-yudisium/${id}`);
        if (response.data?.status) {
          const data = response.data.data;
          suratData.value = {
            id: data.id,
            prodi_id: data.prodi_id ? Number(data.prodi_id) : 0,
            nim: data.nim || "",
            nama_mhs: data.nama_mhs || "",
            prodi: data.prodi || "",
            no_surat: data.no_surat || "",
            nomor_surat: data.nomor_surat || "",
            nilai_skripsi: data.nilai_skripsi || "",
            tanda_tangan_id: data.tanda_tangan_id
              ? Number(data.tanda_tangan_id)
              : null,
            jenis_kelamin: data.jenis_kelamin || "L",
            tanggal: data.tanggal || "",
            petanda_tangan: data.petanda_tangan || "tidak",
          };
        }
      } catch (error) {
        toast.error("Gagal mengambil data SK Yudisium");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPut(`/sk-yudisium/${form.id}`, form);
        if (response.success == true) {
          toast.success("Data SK Yudisium berhasil diperbarui", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/sk-yudisium" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
          } else {
            toast.error("Data SK Yudisium gagal diperbarui", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat memperbarui data", {
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

