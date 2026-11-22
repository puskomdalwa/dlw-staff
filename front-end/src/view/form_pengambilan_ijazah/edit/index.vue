<script lang="ts">
import { defineComponent, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import FormPengambilanIjazahComponent from "@/components/form_pengambilan_ijazah/index.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { apiGet, apiPut } from "@/services/api/request";
import router from "@/router";

export default defineComponent({
  components: {
    FormPengambilanIjazahComponent,
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const errors = ref<any>({});
    const formData = ref<any>({
      id: "",
      nama_mhs: "",
      nim: "",
      prodi: "",
      jenis_kelamin: "",
      dosen_pembimbing: "",
      judul_skripsi: "",
      tanggal_sidang: "",
      no_hp: "",
    });

    async function getData() {
      try {
        loading.value = true;
        const id = route.params.id;
        const response = await apiGet(`/form-pengambilan-ijazah/${id}`);
        if (response.data?.status) {
          const data = response.data.data;
          formData.value = {
            id: data.id,
            nama_mhs: data.nama_mhs || "",
            nim: data.nim || "",
            prodi: data.prodi || "",
            jenis_kelamin: data.jenis_kelamin || "",
            dosen_pembimbing: data.dosen_pembimbing || "",
            judul_skripsi: data.judul_skripsi || "",
            tanggal_sidang: data.tanggal_sidang ? data.tanggal_sidang.slice(0, 10) : "",
            no_hp: data.no_hp || "",
          };
        } else {
          toast.error("Gagal mengambil data formulir");
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengambil data");
      } finally {
        loading.value = false;
      }
    }

    async function submit(form: any) {
      try {
        loading.value = true;
        errors.value = {};
        const response = await apiPut(`/form-pengambilan-ijazah/${form.id}`, form);
        if (response.success == true) {
          toast.success("Formulir pengambilan ijazah berhasil diupdate", {
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
            toast.error("Gagal mengupdate formulir", {
              theme: "auto",
              icon: true,
              hideProgressBar: true,
              autoClose: true,
              position: "top-right",
            });
          }
        }
      } catch (error) {
        toast.error("Terjadi kesalahan saat mengupdate data");
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
        Edit Formulir Pengambilan Ijazah
      </h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/form-pengambilan-ijazah">
                Formulir Pengambilan Ijazah
              </router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
          </ol>
        </nav>
      </div>
    </div>

    <FormPengambilanIjazahComponent
      v-if="formData.id"
      :isEdit="true"
      :modelValue="formData"
      @submit="submit"
      :errors="errors"
      :btnLoading="loading"
    />
  </div>
</template>
