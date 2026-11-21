<script lang="ts">
import { ref } from "vue";
import { defineComponent } from "vue";
import PengumumanAkademikComponent from "@/components/pengumuman_akademik/index.vue";
import { apiPost } from "@/services/api/request";
import { toast } from "vue3-toastify";
import router from "@/router";

export default defineComponent({
  components: {
    PengumumanAkademikComponent,
  },
  setup() {
    const loading = ref(false);
    const errors = ref<any>({});

    async function submit(params: any) {
      try {
        loading.value = true;
        errors.value = {};
        const response = await apiPost("/pengumuman-akademik", params);
        if (response.success == true) {
          toast.success("Pengumuman Akademik Berhasil Ditambahkan", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/pengumuman-akademik" });
        } else {
          if ((response.error as any)?.response?.status === 422) {
            errors.value = (response.error as any).response.data.errors;
          } else {
            toast.error("Pengumuman Akademik gagal ditambahkan", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat menambahkan pengumuman");
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
        Tambah Pengumuman Akademik
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/pengumuman-akademik">Pengumuman Akademik</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
          </ol>
        </nav>
      </div>
    </div>

    <PengumumanAkademikComponent
      @submit="submit"
      :errors="errors"
      :btnLoading="loading"
    />
  </div>
</template>
