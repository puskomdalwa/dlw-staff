<script lang="ts">
import { ref, defineComponent } from "vue";
import FormComponent from "../../../components/sk_yudisium/index.vue";
import { apiPost } from "../../../services/api/request";
import { toast } from "vue3-toastify";
import Swal from "sweetalert2";
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

        const isExcelMode = params instanceof FormData;
        const endpoint = isExcelMode ? "/sk-yudisium/import" : "/sk-yudisium";

        const response = await apiPost(endpoint, params);

        if (response.success == true && (response.data?.status === true || response.data?.status === undefined)) {
          if (isExcelMode) {
            const dataResult = response.data?.data;
            const successCount = dataResult?.success || 0;
            const failedCount = dataResult?.failed || 0;

            await Swal.fire({
              icon: "success",
              title: "Import Berhasil!",
              html: `
                <div class="text-start">
                  <p class="mb-2"><strong>${successCount}</strong> data SK Yudisium berhasil dibuat beserta dokumen PDF-nya.</p>
                  ${failedCount > 0 ? `<p class="text-danger mb-0"><strong>${failedCount}</strong> baris gagal atau dilewati.</p>` : ""}
                </div>
              `,
              confirmButtonText: "Lihat Data",
            });
          } else {
            toast.success("Data SK Yudisium Berhasil Ditambahkan", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }

          router.push({ path: "/sk-yudisium" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
            toast.error("Validasi gagal, periksa kembali input Anda.");
          } else {
            const errorMsg =
              (response.error as any)?.response?.data?.message ||
              response.data?.message ||
              "Gagal menyimpan data SK Yudisium";
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: errorMsg,
            });
          }
        }
      } catch (error: any) {
        toast.error("Terjadi kesalahan saat memproses data");
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
