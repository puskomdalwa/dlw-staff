<script lang="ts" setup>
import { ref, watch } from "vue";
import { BASE_URL } from "../../services/api/http";

const props = defineProps<{
  modelValue?: {
    id?: number;
    nama_institusi?: string;
    rektor?: string;
    kop_surat?: string | null;
  };
  isEdit?: boolean;
  btnLoading?: boolean;
  errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
  (e: "submit", payload: { nama_institusi: string; rektor: string; kop_surat: File | null }): void;
}>();

const form = ref<{
  nama_institusi: string;
  rektor: string;
  kop_surat: File | null;
}>({
  nama_institusi: "",
  rektor: "",
  kop_surat: null,
});

const previewUrl = ref<string>("");

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      form.value.nama_institusi = val.nama_institusi ?? "";
      form.value.rektor = val.rektor ?? "";
      form.value.kop_surat = null;
      if (val.kop_surat) {
        previewUrl.value = `${BASE_URL.replace("/api", "")}/${val.kop_surat}`;
      } else {
        previewUrl.value = "";
      }
    }
  },
  { immediate: true, deep: true }
);

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  form.value.kop_surat = file;
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewUrl.value = (e.target?.result as string) || "";
    };
    reader.readAsDataURL(file);
  } else if (props.modelValue?.kop_surat) {
    previewUrl.value = `${BASE_URL.replace("/api", "")}/${props.modelValue.kop_surat}`;
  } else {
    previewUrl.value = "";
  }
}

function handleSubmit() {
  emit("submit", {
    nama_institusi: form.value.nama_institusi,
    rektor: form.value.rektor,
    kop_surat: form.value.kop_surat,
  });
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="row gy-3">
      <div class="col-md-12">
        <label for="nama_institusi" class="form-label">
          Nama Institusi <span class="text-danger">*</span>
        </label>
        <input
          id="nama_institusi"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': errors?.nama_institusi }"
          v-model="form.nama_institusi"
          placeholder="Masukkan nama institusi"
          required
        />
        <div class="invalid-feedback" v-if="errors?.nama_institusi">
          {{ errors.nama_institusi[0] }}
        </div>
      </div>

      <div class="col-md-12">
        <label for="rektor" class="form-label">Rektor</label>
        <input
          id="rektor"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': errors?.rektor }"
          v-model="form.rektor"
          placeholder="Masukkan nama rektor (opsional)"
        />
        <div class="invalid-feedback" v-if="errors?.rektor">
          {{ errors.rektor[0] }}
        </div>
      </div>

      <div class="col-md-12">
        <label for="kop_surat" class="form-label">Kop Surat (Gambar)</label>
        <input
          id="kop_surat"
          type="file"
          class="form-control"
          :class="{ 'is-invalid': errors?.kop_surat }"
          accept="image/*"
          @change="handleFileChange"
        />
        <small class="text-muted d-block mt-1">
          Format yang didukung: JPG, JPEG, PNG, GIF, SVG, WEBP (Maks. 5MB).
        </small>
        <div class="invalid-feedback d-block" v-if="errors?.kop_surat">
          {{ errors.kop_surat[0] }}
        </div>

        <div v-if="previewUrl" class="mt-3">
          <p class="mb-1 text-muted fs-12">Preview Kop Surat:</p>
          <img
            :src="previewUrl"
            alt="Preview Kop Surat"
            class="img-thumbnail"
            style="max-height: 180px; max-width: 100%; object-fit: contain"
          />
        </div>
      </div>

      <div class="col-12 mt-4 d-flex gap-2">
        <router-link to="/institusi" class="btn btn-light">
          <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
        </router-link>
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="btnLoading"
        >
          <span
            v-if="btnLoading"
            class="spinner-border spinner-border-sm me-1"
            role="status"
            aria-hidden="true"
          ></span>
          {{ isEdit ? "Update" : "Simpan" }}
        </button>
      </div>
    </div>
  </form>
</template>
