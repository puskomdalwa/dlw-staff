<script lang="ts">
import { ref, defineComponent } from "vue";
import FormComponent from "../../../components/pengajuan_judul/index.vue";
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
        const response = await apiPost("/pengajuan-judul", params);

        if (response.success == true) {
          toast.success("Pengajuan Judul Berhasil Ditambahkan", {
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
            toast.error("Pengajuan Judul gagal ditambahkan", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menambahkan data");
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

