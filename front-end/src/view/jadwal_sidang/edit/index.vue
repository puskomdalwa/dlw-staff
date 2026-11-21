<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import FormComponent from "../../../components/jadwal_sidang/index.vue";
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
    const jadwalData = ref<any>({
      id: "",
      tgl_sidang: "",
      mahasiswa_sidang: [],
    });

    async function getDetail() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/jadwal-sidang/${id}`);
        if (response.data && response.data.status) {
          jadwalData.value = response.data.data;
        } else {
          toast.error("Data jadwal sidang tidak ditemukan");
          router.push({ path: "/jadwal-sidang" });
        }
      } catch (error) {
        toast.error("Gagal mengambil detail jadwal sidang");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPut(`/jadwal-sidang/${form.id}`, form);
        if (response.success === true && response.data?.status !== false) {
          toast.success("Jadwal Sidang berhasil diupdate", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/jadwal-sidang" });
        } else {
          const errRes = (response as any).error?.response?.data || response.data;
          if ((response.error as any)?.response?.status === 422 && errRes?.errors) {
            errors.value = errRes.errors;
            toast.error("Validasi gagal, silakan periksa formulir");
          } else {
            toast.error(errRes?.message || "Jadwal Sidang gagal diupdate");
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengupdate jadwal sidang");
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      getDetail();
    });

    return {
      jadwalData,
      submit,
      loading,
      errors,
    };
  },
});
</script>

<template>
  <FormComponent @submit="submit" :modelValue="jadwalData" :isEdit="true" :errors="errors" :btnLoading="loading" />
</template>
