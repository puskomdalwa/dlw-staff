<script lang="ts" setup>
import { reactive, watch } from "vue";

const props = defineProps({
  modelValue: Object,
  isEdit: Boolean,
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const defaultForm = {
  id: "",
  nama_kategori: "",
};

const form = reactive({ ...defaultForm });

watch(
  () => props.modelValue,
  async (val) => {
    if (props.isEdit && val) {
      if (!val.nama_kategori) {
        return;
      }
      Object.assign(form, defaultForm);
      Object.assign(form, val);
    }
  },
  { immediate: true }
);

const emit = defineEmits(["submit"]);

function submitForm() {
  emit("submit", form);
}
</script>

<template>
  <div class="row">
    <form @submit.prevent="submitForm">
      <div class="col-xl-12">
        <div class="card custom-card">
          <div class="card-header">
            <div class="card-title">{{ isEdit ? "Edit" : "Tambah" }} Kategori Berkas</div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-12">
                <label for="input-nama-kategori" class="form-label">Nama Kategori :</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <input
                  type="text"
                  v-model="form.nama_kategori"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_kategori }"
                  id="input-nama-kategori"
                  placeholder="Isikan nama kategori berkas"
                  required
                />
                <div v-if="errors?.nama_kategori" class="invalid-feedback">
                  {{ errors.nama_kategori[0] }}
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <router-link to="/kategori-berkas" class="btn btn-secondary-light btn-wave me-2">
              Batal
            </router-link>
            <button class="btn btn-primary-light btn-wave float-end">
              {{ isEdit ? "Update" : "Simpan" }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>
