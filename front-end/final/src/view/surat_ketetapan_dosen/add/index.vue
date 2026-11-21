<script lang="ts">
import { defineComponent, ref } from "vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import FormComponent from "../../../components/surat_ketetapan_dosen/index.vue";
import router from "../../../router";
import { apiPost } from "../../../services/api/request";

export default defineComponent({
  components: {
    FormComponent,
  },
  setup() {
    const loading = ref(false);
    const errors = ref<any>({});

    async function submit(params: any) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPost("/surat-ketetapan-dosen", params);

        if (response.success === true) {
          toast.success("Surat Ketetapan Dosen berhasil ditambahkan", {
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
            toast.error(errRes?.message || "Surat gagal ditambahkan", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menambahkan surat");
      } finally {
        loading.value = false;
      }
    }

    return {
      loading,
      submit,
      errors,
    };
  },
});
</script>

<template>
  <FormComponent @submit="submit" :isEdit="false" :errors="errors" :btnLoading="loading" />
</template>
