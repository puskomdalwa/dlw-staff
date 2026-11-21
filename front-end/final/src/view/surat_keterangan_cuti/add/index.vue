<script lang="ts">
import { ref, defineComponent } from "vue";
import FormComponent from "../../../components/surat_keterangan_cuti/index.vue";
import { apiPost } from "../../../services/api/request";
import { toast } from "vue3-toastify";
import router from "../../../router";

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
        const response = await apiPost("/skc", params);

        if (response.success == true) {
          toast.success("Surat Keterangan Cuti Berhasil Ditambahkan", {
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
            const msg = errRes?.message || "Surat gagal ditambahkan";
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
