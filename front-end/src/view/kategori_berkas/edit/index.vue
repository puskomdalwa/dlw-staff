<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import KategoriBerkasComponent from "../../../components/kategori_berkas/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPut } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    KategoriBerkasComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const kategoriData = ref({
      id: "",
      nama_kategori: "",
    });

    async function getKategori() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/kategori-berkas/${id}`);
        if (response.success && response.data?.status !== false) {
          const kategori = response.data.data;
          kategoriData.value = {
            id: kategori.id,
            nama_kategori: kategori.nama_kategori || "",
          };
        }
      } catch (error) {
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        loading.value = true;
        errors.value = {};
        const response = await apiPut(`/kategori-berkas/${form.id}`, form);
        if (response.success && response.data?.status !== false) {
          toast.success("Kategori berkas berhasil diupdate", {
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
          const msg = response.data?.message || "Kategori berkas gagal diupdate";
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
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      getKategori();
    });

    return {
      getKategori,
      kategoriData,
      submit,
      loading,
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
      <h1 class="page-title fw-semibold fs-18 mb-0">Edit Kategori Berkas</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/kategori-berkas">Kategori Berkas</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
          </ol>
        </nav>
      </div>
    </div>

    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <KategoriBerkasComponent
      v-else
      :modelValue="kategoriData"
      @submit="submit"
      :isEdit="true"
      :errors="errors"
    />
  </div>
</template>
