<script lang="ts" setup>
import { reactive, ref, watch, onMounted, nextTick } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import { apiGet } from "../../services/api/request";
import { debounce } from "vuetify/lib/util/helpers.mjs";

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
  prodi_id: "" as string | number,
  nama_mhs: "",
  nim: "",
  dari_semester: "",
  sampai_semester: "",
  jenis_kelamin: "L",
  alasan: "urusan finansial yang belum terpenuhi",
  tanggal: new Date().toISOString().slice(0, 10),
  petanda_tangan: "ya",
};

const disableListMhsWatcher = ref(false);
const form = reactive({ ...defaultForm });

const options = ref<any[]>([]);
const loading = ref(false);
const isLoadingData = ref(false);
const listMhs = ref<any>(null);
const listProdi = ref<any[]>([]);
const hasUserProdi = ref(false);

function customName(params: any) {
  return `${params.nama} - ${params.nim}`;
}

watch(listMhs, async (val) => {
  if (!val) return;
  if (disableListMhsWatcher.value) return;

  isLoadingData.value = true;
  await new Promise((resolve) => setTimeout(resolve, 250));

  form.nama_mhs = val.nama || val.nama_lengkap || "";
  form.nim = val.nim || "";

  if (val.jenis_kelamin || val.jk) {
    const jk = (val.jenis_kelamin || val.jk).toUpperCase();
    form.jenis_kelamin = jk.startsWith("P") ? "P" : "L";
  }

  // Jika user tidak punya prodi tetap, otomatis isi dari prodi mahasiswa
  if (!hasUserProdi.value && listProdi.value.length > 0) {
    let aliasTarget = (val.alias_prodi || "").toLowerCase().trim();
    if (aliasTarget === "hki") aliasTarget = "as-hk";
    const namaTarget = (
      val.nama_prodi ||
      (typeof val.prodi === "string" ? val.prodi : val.prodi?.nama) ||
      val.prodi_mhs ||
      ""
    )
      .toLowerCase()
      .trim();
    const kodeTarget = (val.kode_prodi || "").toString().trim();

    const matched =
      listProdi.value.find((p) => {
        const pAlias = (p.alias || "").toLowerCase().trim();
        const pNama = (p.nama || "").toLowerCase().trim();
        const pKode = (p.kode || "").toString().trim();
        return (
          (aliasTarget && pAlias === aliasTarget) ||
          (kodeTarget && pKode === kodeTarget) ||
          (namaTarget && (pNama === namaTarget || pAlias === namaTarget))
        );
      }) || listProdi.value.find((p) => Number(p.id) === Number(val.prodi_id));

    if (matched) {
      form.prodi_id = Number(matched.id);
    }
  }

  isLoadingData.value = false;
});

async function getProdi() {
  try {
    const response = await apiGet(`/get-prodi`);
    if (response.success) {
      const rawData = response.data?.data ?? response.data;
      if (Array.isArray(rawData)) {
        listProdi.value = rawData;
        if (rawData.length === 1) {
          hasUserProdi.value = true;
          if (!props.isEdit) {
            form.prodi_id = Number(rawData[0].id);
          }
        } else {
          hasUserProdi.value = false;
        }
      } else if (rawData && typeof rawData === "object") {
        listProdi.value = [rawData];
        hasUserProdi.value = true;
        if (!props.isEdit) {
          form.prodi_id = Number(rawData.id);
        }
      }
    }
  } catch (error) {}
}

onMounted(() => {
  getProdi();
});

watch(
  () => props.modelValue,
  async (val) => {
    if (!props.isEdit) return;
    if (!val || !val.nim) {
      isLoadingData.value = true;
      return;
    }

    disableListMhsWatcher.value = true;
    isLoadingData.value = true;

    await new Promise((resolve) => setTimeout(resolve, 300));

    form.id = val.id ?? "";
    form.prodi_id = val.prodi_id ? Number(val.prodi_id) : "";
    form.nama_mhs = val.nama_mhs ?? "";
    form.nim = val.nim ?? "";
    form.dari_semester = val.dari_semester ?? "";
    form.sampai_semester = val.sampai_semester ?? "";
    form.jenis_kelamin = val.jenis_kelamin ?? "L";
    form.alasan = val.alasan ?? "urusan finansial yang belum terpenuhi";
    form.tanggal = val.tanggal ? val.tanggal.slice(0, 10) : new Date().toISOString().slice(0, 10);
    form.petanda_tangan = val.petanda_tangan ?? "ya";

    if (form.nim) {
      listMhs.value = {
        nim: form.nim,
        nama: form.nama_mhs,
        id: form.nim,
      };
      options.value = [listMhs.value];
    }

    isLoadingData.value = false;
    await nextTick();
    disableListMhsWatcher.value = false;
  },
  { immediate: true }
);

const getMhs = debounce(async (params: string) => {
  const keyword = params.trim();
  if (!keyword && !props.isEdit) {
    options.value = [];
    return;
  }

  try {
    loading.value = true;
    const response = await apiGet(`/get-mhs`, { search: keyword });
    if (response.success) {
      const result = response.data;
      if (result && result.data && Array.isArray(result.data)) {
        options.value = result.data;
      } else if (Array.isArray(result)) {
        options.value = result;
      } else {
        options.value = [];
      }
    }
  } catch (error) {
  } finally {
    loading.value = false;
  }
}, 300);

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
            <div class="card-title">
              {{ isEdit ? "Edit" : "Tambah" }} Surat Keterangan Cuti
            </div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-6">
                <label class="form-label">Program Studi Unit :</label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id }"
                  v-model="form.prodi_id"
                  required
                >
                  <option value="" disabled>-- Pilih Program Studi --</option>
                  <option
                    v-for="prodi in listProdi"
                    :key="prodi.id"
                    :value="Number(prodi.id)"
                  >
                    {{ prodi.nama }}
                  </option>
                </select>
                <div v-if="errors?.prodi_id" class="invalid-feedback">
                  {{ errors.prodi_id[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Pencarian Mahasiswa (NIM / Nama) :</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <Multiselect
                  :options="options"
                  v-model="listMhs"
                  :internal-search="false"
                  @search-change="getMhs"
                  label="nama"
                  track-by="nim"
                  :searchable="true"
                  :loading="loading"
                  :custom-label="customName"
                  :class="{ 'border-danger': errors?.nim || errors?.nama_mhs }"
                  placeholder="Ketik Nama atau NIM..."
                ></Multiselect>
                <div v-if="errors?.nim" class="text-danger small mt-1">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0">Informasi Mahasiswa &amp; Pengajuan Cuti</div>

              <div class="col-xl-5">
                <label class="form-label">Nama Mahasiswa :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nama_mhs"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_mhs }"
                  placeholder="Isikan Nama Mahasiswa"
                  required
                />
                <div v-if="errors?.nama_mhs" class="invalid-feedback">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">NIM :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nim"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  placeholder="Isikan NIM"
                  required
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <div class="col-xl-3">
                <label class="form-label">Jenis Kelamin :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <select
                  v-else
                  class="form-select"
                  :class="{ 'is-invalid': errors?.jenis_kelamin }"
                  v-model="form.jenis_kelamin"
                >
                  <option value="L">Laki-Laki (L)</option>
                  <option value="P">Perempuan (P)</option>
                </select>
                <div v-if="errors?.jenis_kelamin" class="invalid-feedback">
                  {{ errors.jenis_kelamin[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">Dari Semester :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.dari_semester"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.dari_semester }"
                  placeholder="Contoh: II"
                  required
                />
                <div v-if="errors?.dari_semester" class="invalid-feedback">
                  {{ errors.dari_semester[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">Sampai Semester :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.sampai_semester"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.sampai_semester }"
                  placeholder="Contoh: III"
                  required
                />
                <div v-if="errors?.sampai_semester" class="invalid-feedback">
                  {{ errors.sampai_semester[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">Tanggal Surat :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="date"
                  v-model="form.tanggal"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.tanggal }"
                />
                <div v-if="errors?.tanggal" class="invalid-feedback">
                  {{ errors.tanggal[0] }}
                </div>
              </div>

              <div class="col-xl-12">
                <label class="form-label">Alasan Cuti :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <textarea
                  v-else
                  v-model="form.alasan"
                  class="form-control"
                  rows="2"
                  :class="{ 'is-invalid': errors?.alasan }"
                  placeholder="Contoh: urusan finansial yang belum terpenuhi"
                ></textarea>
                <div v-if="errors?.alasan" class="invalid-feedback">
                  {{ errors.alasan[0] }}
                </div>
              </div>

              <div class="col-xl-12 mt-3">
                <label class="form-label fw-bold">Pakai Tanda Tangan &amp; Stempel Dekan :</label>
                <div v-if="isLoadingData" class="skeleton-input" style="width: 150px;"></div>
                <div v-else class="d-flex align-items-center mt-2">
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_cuti"
                      id="cuti_ttd_ya"
                      value="ya"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="cuti_ttd_ya">TTD + Stempel</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_cuti"
                      id="cuti_ttd_kosong"
                      value="tidak"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="cuti_ttd_kosong">Kosong (Tanda Tangan Basah)</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_cuti"
                      id="cuti_ttd_stempel"
                      value="stempel"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="cuti_ttd_stempel">Stempel Saja</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex justify-content-between">
            <router-link to="/skc" class="btn btn-secondary-light btn-wave">
              Batal
            </router-link>
            <button class="btn btn-primary-light btn-wave" :disabled="btnLoading">
              <span
                v-if="btnLoading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              {{ btnLoading ? (isEdit ? "Mengupdate..." : "Menyimpan...") : isEdit ? "Update" : "Simpan" }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.skeleton-input {
  height: 38px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 5px;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
