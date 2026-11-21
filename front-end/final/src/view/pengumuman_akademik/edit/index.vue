<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import PengumumanAkademikComponent from "@/components/pengumuman_akademik/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPut } from "@/services/api/request";
import router from "@/router";

export default defineComponent({
  components: {
    PengumumanAkademikComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const formData = ref<any>({
      id: "",
      nomor_surat: "",
      no_surat: "",
      judul: "",
      kepada: "",
      tempat: "Pasuruan",
      tanggal: "",
      isi: "",
      tanda_tangan_id: "",
      petanda_tangan: "tidak",
    });

    async function getData() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/pengumuman-akademik/${id}`);
        if (response.data?.status) {
          const data = response.data.data;
          formData.value = {
            id: data.id,
            nomor_surat: data.nomor_surat || "",
            no_surat: data.no_surat || "",
            judul: data.judul || "",
            kepada: data.kepada || "",
            tempat: data.tempat || "Pasuruan",
            tanggal: data.tanggal ? data.tanggal.slice(0, 10) : "",
            isi: data.isi || "",
            tanda_tangan_id: data.tanda_tangan_id || "",
            petanda_tangan: data.petanda_tangan || "tidak",
          };
        } else {
          toast.error("Gagal mengambil data pengumuman");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengambil data pengumuman");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        loading.value = true;
        errors.value = {};
        const response = await apiPut(`/pengumuman-akademik/${form.id}`, form);
        if (response.success == true) {
          toast.success("Pengumuman Akademik berhasil diupdate", {
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
            toast.error("Gagal mengupdate pengumuman", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengupdate pengumuman");
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      getData();
    });

    return {
      formData,
      loading,
      errors,
      submit,
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
        Edit Pengumuman Akademik
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/pengumuman-akademik">Pengumuman Akademik</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
          </ol>
        </nav>
      </div>
    </div>

    <PengumumanAkademikComponent
      v-if="formData.id"
      :isEdit="true"
      :modelValue="formData"
      @submit="submit"
      :errors="errors"
      :btnLoading="loading"
    />
  </div>
</template>
