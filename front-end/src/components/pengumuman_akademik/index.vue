<script lang="ts" setup>
import { reactive, ref, watch, onMounted, computed } from "vue";
import { apiGet } from "../../services/api/request";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

const props = defineProps({
  modelValue: Object,
  isEdit: Boolean,
  errors: {
    type: Object,
    default: () => ({}),
  },
  btnLoading: {
    type: Boolean,
    default: false,
  },
});

const defaultForm = {
  id: "",
  no_surat: "",
  nomor_surat: "",
  judul: "",
  kepada: "",
  tempat: "Pasuruan",
  tanggal: new Date().toISOString().slice(0, 10),
  isi: "",
  tanda_tangan_id: "" as string | number,
  petanda_tangan: "tidak",
};

const form = reactive({ ...defaultForm });
const isLoadingData = ref(false);
const listJenisSurat = ref<any[]>([]);

function getRoman(num: number) {
  const roman: any = {
    1: "I", 2: "II", 3: "III", 4: "IV", 5: "V", 6: "VI",
    7: "VII", 8: "VIII", 9: "IX", 10: "X", 11: "XI", 12: "XII"
  };
  return roman[num] || "";
}

async function getJenisSurat() {
  try {
    const response = await apiGet(`/jenis-surat`);
    if (response.success) {
      const data = response.data?.data || response.data;
      listJenisSurat.value = Array.isArray(data) ? data : [data];
    }
  } catch (error) {
  }
}

const formatParts = computed(() => {
  const defaultParts = {
    prefix: "SU-",
    suffix: "/UII.085/BAAK/PP.00/" + String(new Date().getMonth() + 1).padStart(2, "0") + "/" + new Date().getFullYear(),
  };

  const js = listJenisSurat.value.find((x: any) => x.alias === "PA");
  if (!js) return defaultParts;

  let str = js.format_surat;
  const dateObj = form.tanggal ? new Date(form.tanggal) : new Date();
  const dd = String(dateObj.getDate()).padStart(2, "0");
  const romanBulan = getRoman(dateObj.getMonth() + 1);
  const m2 = String(dateObj.getMonth() + 1).padStart(2, "0");
  const yyyy = dateObj.getFullYear();

  str = str.replace(/{TGL}/g, dd)
           .replace(/{BULAN}/g, m2)
           .replace(/{TAHUN}/g, String(yyyy))
           .replace(/{PRODI}/g, "BAAK");

  const splitted = str.split("{NO}");
  return {
    prefix: splitted[0] || "SU-",
    suffix: splitted[1] || "",
  };
});

function extractNo(fullStr: string) {
  if (!fullStr) return "";
  const firstPart = fullStr.split("/")[0];
  return firstPart.replace("SU-", "").trim();
}

watch(
  () => props.modelValue,
  async (val) => {
    if (!props.isEdit || !val) return;

    isLoadingData.value = true;
    if (listJenisSurat.value.length === 0) {
      await getJenisSurat();
    }

    Object.assign(form, val);
    form.id = val.id ?? "";
    form.judul = val.judul ?? "";
    form.kepada = val.kepada ?? "";
    form.isi = val.isi ?? "";
    form.tempat = val.tempat ?? "Pasuruan";
    form.tanggal = val.tanggal ? val.tanggal.slice(0, 10) : new Date().toISOString().slice(0, 10);
    form.petanda_tangan = val.petanda_tangan ?? "tidak";
    form.tanda_tangan_id = val.tanda_tangan_id ?? "";
    form.no_surat = val.no_surat ?? extractNo(val.nomor_surat ?? "");

    isLoadingData.value = false;
  },
  { immediate: true }
);

onMounted(() => {
  getJenisSurat();
});

const emit = defineEmits(["submit"]);

function submitForm() {
  emit("submit", form);
}
</script>

<template>
  <div class="row">
    <div class="col-xl-12">
      <div class="card custom-card">
        <div class="card-header justify-content-between">
          <div class="card-title">
            {{ isEdit ? "Edit Surat Pengumuman Akademik" : "Tambah Surat Pengumuman Akademik" }}
          </div>
        </div>
        <div class="card-body">
          <div class="row gy-3">
            <!-- Informasi Nomor Surat & Tanggal -->
            <div class="col-xl-6">
              <label class="form-label fw-semibold">Nomor Surat (Angka): <span class="text-danger">*</span></label>
              <div v-if="isLoadingData" class="skeleton-input"></div>
              <div class="input-group" v-else>
                <span class="input-group-text" v-if="formatParts.prefix">{{ formatParts.prefix }}</span>
                <input
                  type="text"
                  v-model="form.no_surat"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nomor_surat || errors?.no_surat }"
                  placeholder="Contoh: 001"
                />
                <span class="input-group-text" v-if="formatParts.suffix">{{ formatParts.suffix }}</span>
                <div v-if="errors?.nomor_surat || errors?.no_surat" class="invalid-feedback">
                  {{ errors?.nomor_surat ? errors.nomor_surat[0] : errors?.no_surat[0] }}
                </div>
              </div>
            </div>

            <div class="col-xl-6">
              <label class="form-label fw-semibold">Tanggal Surat: <span class="text-danger">*</span></label>
              <input
                type="date"
                v-model="form.tanggal"
                class="form-control"
                :class="{ 'is-invalid': errors?.tanggal }"
              />
              <div v-if="errors?.tanggal" class="invalid-feedback">
                {{ errors.tanggal[0] }}
              </div>
            </div>

            <!-- Judul / Perihal -->
            <div class="col-xl-12">
              <label class="form-label fw-semibold">Judul / Tentang Pengumuman:</label>
              <input
                type="text"
                v-model="form.judul"
                class="form-control"
                :class="{ 'is-invalid': errors?.judul }"
                placeholder="Contoh: Pelaksanaan Kuliah Perdana Semester Genap (Opsional)"
              />
              <div v-if="errors?.judul" class="invalid-feedback">
                {{ errors.judul[0] }}
              </div>
            </div>

            <!-- Isi Pengumuman -->
            <div class="col-xl-12">
              <label class="form-label fw-semibold">Isi Pengumuman: <span class="text-danger">*</span></label>
              <div :class="{ 'border border-danger rounded': errors?.isi }">
                <QuillEditor
                  v-model:content="form.isi"
                  contentType="html"
                  theme="snow"
                  placeholder="Tuliskan isi pengumuman akademik secara lengkap di sini..."
                  style="min-height: 250px"
                  :toolbar="[
                    [{ font: [] }],
                    [{ size: ['small', false, 'large', 'huge'] }],
                    [{ header: [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ script: 'sub' }, { script: 'super' }],
                    ['blockquote', 'code-block'],
                    [
                      { list: 'ordered' },
                      { list: 'bullet' },
                      { list: 'check' },
                    ],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ direction: 'rtl' }],
                    [{ align: [] }],
                    ['link', 'image', 'video', 'formula'],
                    ['clean'],
                  ]"
                />
              </div>
              <div v-if="errors?.isi" class="text-danger fs-12 mt-1">
                {{ errors.isi[0] }}
              </div>
            </div>

            <!-- Tanda Tangan & Stempel -->
            <div class="col-xl-12 mt-2">
              <label class="form-label fw-bold">Pakai Tanda Tangan & Stempel:</label>
              <div class="d-flex align-items-center mt-1">
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="petanda_tangan_pa"
                    id="pa_ttd_ya"
                    value="ya"
                    v-model="form.petanda_tangan"
                  />
                  <label class="form-check-label" for="pa_ttd_ya">Tanda tangan + Stempel</label>
                </div>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="petanda_tangan_pa"
                    id="pa_ttd_tidak"
                    value="tidak"
                    v-model="form.petanda_tangan"
                  />
                  <label class="form-check-label" for="pa_ttd_tidak">Kosong</label>
                </div>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="petanda_tangan_pa"
                    id="pa_ttd_stempel"
                    value="stempel"
                    v-model="form.petanda_tangan"
                  />
                  <label class="form-check-label" for="pa_ttd_stempel">Stempel Saja</label>
                </div>
              </div>
              <div v-if="errors?.petanda_tangan" class="invalid-feedback d-block">
                {{ errors.petanda_tangan[0] }}
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
          <router-link to="/pengumuman-akademik" class="btn btn-light btn-wave">
            Batal
          </router-link>
          <button
            class="btn btn-primary btn-wave shadow-sm"
            :disabled="btnLoading"
            @click="submitForm"
          >
            <span
              v-if="btnLoading"
              class="spinner-border spinner-border-sm me-1"
              role="status"
              aria-hidden="true"
            ></span>
            <i v-else class="ri-save-line me-1"></i>
            {{ isEdit ? "Update Pengumuman" : "Simpan Pengumuman" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.skeleton-input {
  width: 100%;
  height: 38px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

:deep(.ql-editor) {
  min-height: 200px;
}
</style>
