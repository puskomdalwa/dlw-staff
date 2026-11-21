<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import BerkasFormComponent from "../../../components/berkas/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPost } from "../../../services/api/request";
import router from "../../../router";

export default defineComponent({
  components: {
    BerkasFormComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const berkasData = ref<any>(null);

    async function getBerkas() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/berkas/${id}`);
        if (response.success && response.data?.status !== false) {
          berkasData.value = response.data.data;
        }
      } catch (error) {
        console.error(error);
      } finally {
        loading.value = false;
      }
    }

    async function submit(formData: FormData) {
      try {
        loading.value = true;
        errors.value = {};

        toast.info("Sedang mengupdate berkas...", {
          autoClose: 2000,
          position: "top-right",
        });

        // Use method spoofing PUT via POST for FormData upload in Laravel
        formData.append("_method", "PUT");
        const id = route.params.id;
        const response = await apiPost(`/berkas/${id}`, formData);

        if (response.success && response.data?.status !== false) {
          toast.success("Berkas berhasil diperbarui", {
            theme: "auto",
            icon: true,
            hideProgressBar: true,
            autoClose: true,
            position: "top-right",
          });
          router.push({ path: "/berkas" });
        } else {
          if (response.data?.errors) {
            errors.value = response.data.errors;
          }
          toast.error(response.data?.message || "Berkas gagal diperbarui", {
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
        toast.error(error?.response?.data?.message || "Terjadi kesalahan saat mengupdate berkas", {
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
      getBerkas();
    });

    return {
      berkasData,
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
      <h1 class="page-title fw-semibold fs-18 mb-0">Edit Berkas Mahasiswa</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/berkas">Berkas</router-link>
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

    <BerkasFormComponent
      v-else-if="berkasData"
      :modelValue="berkasData"
      @submit="submit"
      :isEdit="true"
      :errors="errors"
    />
  </div>
</template>
