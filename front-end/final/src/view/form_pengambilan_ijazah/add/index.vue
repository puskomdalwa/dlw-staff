<script lang="ts">
import { ref, defineComponent } from "vue";
import FormPengambilanIjazahComponent from "@/components/form_pengambilan_ijazah/index.vue";
import { apiPost } from "@/services/api/request";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import router from "@/router";

export default defineComponent({
  components: {
    FormPengambilanIjazahComponent,
  },
  setup() {
    const loading = ref(false);
    const errors = ref<any>({});

    async function submit(params: any) {
      try {
        loading.value = true;
        errors.value = {};
        const response = await apiPost("/form-pengambilan-ijazah", params);
        if (response.success == true) {
          toast.success("Formulir pengambilan ijazah berhasil disimpan", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/form-pengambilan-ijazah" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
          } else {
            toast.error("Gagal menyimpan data formulir", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menyimpan formulir");
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
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">
        Tambah Formulir Pengambilan Ijazah
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/form-pengambilan-ijazah">
                Formulir Pengambilan Ijazah
              </router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
          </ol>
        </nav>
      </div>
    </div>

    <FormPengambilanIjazahComponent
      @submit="submit"
      :errors="errors"
      :btnLoading="loading"
    />
  </div>
</template>
