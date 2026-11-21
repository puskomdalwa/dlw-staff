<script lang="ts" setup>
import { ref } from "vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import FormInstitusi from "../../../components/institusi/index.vue";
import router from "../../../router";
import { apiPost } from "../../../services/api/request";
import SimpleCardComponent from "../../../shared/components/@spk/simple-card.vue";

const btnLoading = ref(false);
const errors = ref<Record<string, string[]>>({});

async function handleSubmit(payload: {
  nama_institusi: string;
  rektor: string;
  kop_surat: File | null;
}) {
  btnLoading.value = true;
  errors.value = {};

  try {
    const formData = new FormData();
    formData.append("nama_institusi", payload.nama_institusi);
    formData.append("rektor", payload.rektor || "");
    if (payload.kop_surat) {
      formData.append("kop_surat", payload.kop_surat);
    }

    const response = await apiPost("/institusi", formData);
    if (response.success) {
      toast.success("Data institusi berhasil ditambahkan");
      router.push("/institusi");
    } else {
      const errData = (response as any).error?.response?.data;
      if ((response as any).error?.response?.status === 422 && errData?.errors) {
        errors.value = errData.errors;
        toast.error("Periksa kembali inputan Anda");
      } else {
        toast.error(errData?.message || "Gagal menyimpan data institusi");
      }
    }
  } catch (error) {
    console.error(error);
    toast.error("Terjadi kesalahan sistem");
  } finally {
    btnLoading.value = false;
  }
}
</script>

<template>
  <div class="container-fluid">
    <div
      class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb"
    >
      <h1 class="page-title fw-semibold fs-18 mb-0">Tambah Data Institusi</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/institusi">Institusi</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
          </ol>
        </nav>
      </div>
    </div>

    <SimpleCardComponent title="Form Tambah Institusi">
      <FormInstitusi
        :btnLoading="btnLoading"
        :errors="errors"
        @submit="handleSubmit"
      />
    </SimpleCardComponent>
  </div>
</template>
