<script lang="ts" setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import FormInstitusi from "../../../components/institusi/index.vue";
import router from "../../../router";
import { apiGet, apiPost } from "../../../services/api/request";
import SimpleCardComponent from "../../../shared/components/@spk/simple-card.vue";

const route = useRoute();
const id = route.params.id;

const loading = ref(false);
const btnLoading = ref(false);
const errors = ref<Record<string, string[]>>({});
const initialData = ref<any>({});

async function fetchDetail() {
  loading.value = true;
  try {
    const response = await apiGet(`/institusi/${id}`);
    if (response.success) {
      initialData.value = response.data?.data ?? {};
    } else {
      toast.error("Data institusi tidak ditemukan");
      router.push("/institusi");
    }
  } catch (error) {
    console.error(error);
    toast.error("Gagal memuat detail institusi");
  } finally {
    loading.value = false;
  }
}

async function handleSubmit(payload: {
  nama_institusi: string;
  rektor: string;
  kop_surat: File | null;
}) {
  btnLoading.value = true;
  errors.value = {};

  try {
    const formData = new FormData();
    formData.append("_method", "PUT");
    formData.append("nama_institusi", payload.nama_institusi);
    formData.append("rektor", payload.rektor || "");
    if (payload.kop_surat) {
      formData.append("kop_surat", payload.kop_surat);
    }

    const response = await apiPost(`/institusi/${id}`, formData);
    if (response.success) {
      toast.success("Data institusi berhasil diperbarui");
      router.push("/institusi");
    } else {
      const errData = (response as any).error?.response?.data;
      if ((response as any).error?.response?.status === 422 && errData?.errors) {
        errors.value = errData.errors;
        toast.error("Periksa kembali inputan Anda");
      } else {
        toast.error(errData?.message || "Gagal memperbarui data institusi");
      }
    }
  } catch (error) {
    console.error(error);
    toast.error("Terjadi kesalahan sistem");
  } finally {
    btnLoading.value = false;
  }
}

onMounted(() => {
  fetchDetail();
});
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Edit Data Institusi</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/institusi">Institusi</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
          </ol>
        </nav>
      </div>
    </div>

    <SimpleCardComponent title="Form Edit Institusi">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <FormInstitusi
        v-else
        :modelValue="initialData"
        :isEdit="true"
        :btnLoading="btnLoading"
        :errors="errors"
        @submit="handleSubmit"
      />
    </SimpleCardComponent>
  </div>
</template>
