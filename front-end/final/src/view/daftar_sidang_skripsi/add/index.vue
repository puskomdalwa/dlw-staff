<script lang="ts">
import { ref, defineComponent } from "vue";
import FormComponent from "../../../components/daftar_sidang_skripsi/index.vue";
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

    async function submit(payload: { formData: FormData; rawForm: any }) {
      try {
        errors.value = {};
        loading.value = true;
        const response = await apiPost("/daftar-sidang-skripsi", payload.formData);

        if (response.success == true && response.data?.status) {
          toast.success("Pendaftaran Sidang Skripsi Berhasil Disimpan", {
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
            toast.error("Validasi formulir gagal, periksa kembali inputan");
          } else {
            toast.error(response.data?.message || "Gagal menyimpan pendaftaran sidang", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menyimpan data");
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
  <FormComponent
    @submit="submit"
    :isEdit="false"
    :errors="errors"
    :btnLoading="loading"
  />
</template>

