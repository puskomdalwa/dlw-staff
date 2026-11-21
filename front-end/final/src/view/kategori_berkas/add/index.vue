<script lang="ts">
import { defineComponent, ref } from "vue";
import KategoriBerkasComponent from "../../../components/kategori_berkas/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiPost } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    KategoriBerkasComponent,
  },
  setup() {
    const errors = ref<any>({});

    async function submit(form: any) {
      try {
        errors.value = {};
        const response = await apiPost("/kategori-berkas", form);
        if (response.success && response.data?.status !== false) {
          toast.success("Kategori berkas berhasil ditambahkan", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ name: "kategori-berkas" });
        } else {
          if (response.data?.errors) {
            errors.value = response.data.errors;
          }
          const msg = response.data?.message || "Kategori berkas gagal ditambahkan";
          toast.error(msg, {
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
        const msg = error?.response?.data?.message || "Terjadi kesalahan";
        toast.error(msg, {
          theme: "auto",
          icon: true,
          hideProgressBar: true,
          autoClose: true,
          position: "top-right",
        });
      }
    }

    return {
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
      <h1 class="page-title fw-semibold fs-18 mb-0">Tambah Kategori Berkas</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/kategori-berkas">Kategori Berkas</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
          </ol>
        </nav>
      </div>
    </div>

    <KategoriBerkasComponent @submit="submit" :isEdit="false" :errors="errors" />
  </div>
</template>
