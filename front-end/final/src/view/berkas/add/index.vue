<script lang="ts">
import { defineComponent, ref } from "vue";
import { useRoute } from "vue-router";
import BerkasFormComponent from "../../../components/berkas/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiPost } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    BerkasFormComponent,
  },
  setup() {
    const route = useRoute();
    const errors = ref<any>({});
    const btnLoading = ref(false);
    const initialTab = ref<"non_skripsi" | "skripsi">(
      (route.query.tab as "non_skripsi" | "skripsi") || "non_skripsi"
    );

    async function submit(formData: FormData) {
      try {
        btnLoading.value = true;
        errors.value = {};
        toast.info("Sedang mengupload dan memproses berkas...", {
          autoClose: 2500,
          position: "top-right",
        });

        const response = await apiPost("/berkas", formData);
        if (response.success && response.data?.status !== false) {
          toast.success(
            response.data?.message || "Berkas berhasil disimpan dan terunggah ke Google Drive",
            {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            }
          );
          router.push({ path: "/berkas" });
        } else {
          if (response.data?.errors) {
            errors.value = response.data.errors;
          }
          toast.error(response.data?.message || "Berkas gagal ditambahkan", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
        }
      } catch (error: any) {
        if (error?.response?.data?.errors) {
          errors.value = error.response.data.errors;
        }
        toast.error(
          error?.response?.data?.message || "Terjadi kesalahan saat mengupload berkas",
          {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          }
        );
      } finally {
        btnLoading.value = false;
      }
    }

    return {
      submit,
      errors,
      initialTab,
      btnLoading,
    };
  },
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Upload / Scan Berkas Mahasiswa</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/berkas">Berkas</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
          </ol>
        </nav>
      </div>
    </div>

    <BerkasFormComponent
      @submit="submit"
      :isEdit="false"
      :errors="errors"
      :initialTab="initialTab"
      :btnLoading="btnLoading"
    />
  </div>
</template>
