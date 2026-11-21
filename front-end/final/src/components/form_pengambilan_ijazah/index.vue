<script lang="ts" setup>
import { reactive, ref, watch, nextTick } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import { apiGet } from "../../services/api/request";

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
  nama_mhs: "",
  nim: "",
  prodi: "",
  dosen_pembimbing: "",
  judul_skripsi: "",
  tanggal_sidang: "",
  no_hp: "",
};

const form = reactive({ ...defaultForm });

const disableListMhsWatcher = ref(false);
const disableListDosenWatcher = ref(false);
const isLoadingData = ref(false);

// State Multiselect Mahasiswa
const listMhs = ref<any>(null);
const optionsMhs = ref<any[]>([]);
const loadingMhs = ref(false);

// State Multiselect Dosen
const listDosen = ref<any>(null);
const optionsDosen = ref<any[]>([]);
const loadingDosen = ref(false);

function debounce<T extends (...args: any[]) => any>(fn: T, delay = 300) {
  let timer: any = null;
  return function (...args: Parameters<T>) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

// Format nama dosen dengan gelar
function formatNamaDosen(dosen: any) {
  if (!dosen) return "";
  if (dosen.nama_gelar) return dosen.nama_gelar;

  const gelarDepan = (dosen.gelar_depan || "").trim();
  const gelarBelakang = (dosen.gelar_belakang || "").trim();
  let result = (dosen.nama || "").trim();

  if (gelarDepan) {
    const depanCheck = gelarDepan.replace(/\.+$/, "");
    const regex = new RegExp(`^${depanCheck}(\\.|\\s)`, "i");
    if (!regex.test(result)) {
      result = `${gelarDepan} ${result}`;
    }
  }

  if (gelarBelakang) {
    const cleanGelarBelakang = gelarBelakang.replace(/^[,\s]+/, "");
    const belakangNormalized = cleanGelarBelakang.replace(/\./g, "").toLowerCase();
    const resultNormalized = result.replace(/\./g, "").toLowerCase();
    if (!resultNormalized.includes(belakangNormalized)) {
      result = `${result.replace(/^[,\s]+|[,\s]+$/g, "")}, ${cleanGelarBelakang}`;
    }
  }

  return result.trim();
}

function customNameMhs(item: any) {
  if (!item) return "";
  if (item.nim && item.nama) {
    return `${item.nama} - ${item.nim}`;
  }
  return item.nama || item.nim || "";
}

function customNameDosen(item: any) {
  if (!item) return "";
  const nameWithTitle = formatNamaDosen(item);
  return item.nidn ? `${nameWithTitle} - ${item.nidn}` : nameWithTitle;
}

// Fetch Mahasiswa dari API
const getMhs = debounce(async (keyword: string) => {
  const search = keyword ? keyword.trim() : "";
  if (!search && !props.isEdit) {
    optionsMhs.value = [];
    return;
  }

  try {
    loadingMhs.value = true;
    const response = await apiGet(`/get-mhs`, { search });
    if (response.success) {
      const result = response.data;
      if (result && result.data && Array.isArray(result.data)) {
        optionsMhs.value = result.data;
      } else if (Array.isArray(result)) {
        optionsMhs.value = result;
      } else {
        optionsMhs.value = [];
      }
    }
  } catch (error) {
    optionsMhs.value = [];
  } finally {
    loadingMhs.value = false;
  }
}, 300);

// Fetch Dosen dari API
const getDosen = debounce(async (keyword: string) => {
  const search = keyword ? keyword.trim() : "";
  if (!search && !props.isEdit) {
    optionsDosen.value = [];
    return;
  }

  try {
    loadingDosen.value = true;
    const response = await apiGet(`/get-dosen`, { search });
    if (response.success) {
      optionsDosen.value = response.data?.data || [];
    }
  } catch (error) {
    optionsDosen.value = [];
  } finally {
    loadingDosen.value = false;
  }
}, 300);

// Watcher ketika Mahasiswa dipilih: Otomatis mengisi Nama Mahasiswa & Prodi
watch(listMhs, async (val) => {
  if (disableListMhsWatcher.value) return;

  if (val) {
    isLoadingData.value = true;
    form.nama_mhs = val.nama || "";
    form.nim = val.nim || "";

    if (val.alias_prodi) {
      form.prodi = val.alias_prodi;
    } else if (val.prodi_mhs) {
      form.prodi = val.prodi_mhs;
    } else if (val.prodi) {
      form.prodi = typeof val.prodi === "object" ? (val.prodi.nama || val.prodi.alias || "") : val.prodi;
    } else if (val.nama_prodi) {
      form.prodi = val.nama_prodi;
    }

    if (!form.no_hp && (val.hp || val.no_hp || val.telepon)) {
      form.no_hp = val.hp || val.no_hp || val.telepon;
    }

    isLoadingData.value = false;
  } else {
    form.nama_mhs = "";
    form.nim = "";
    form.prodi = "";
  }
});

// Watcher ketika Dosen dipilih: Otomatis mengisi Nama Dosen Pembimbing
watch(listDosen, (val) => {
  if (disableListDosenWatcher.value) return;

  if (val) {
    form.dosen_pembimbing = formatNamaDosen(val);
  } else {
    form.dosen_pembimbing = "";
  }
});

// Watch modelValue untuk mode edit
watch(
  () => props.modelValue,
  async (val) => {
    if (!props.isEdit || !val) return;

    disableListMhsWatcher.value = true;
    disableListDosenWatcher.value = true;
    isLoadingData.value = true;

    await new Promise((resolve) => setTimeout(resolve, 300));
    Object.assign(form, val);

    form.id = val.id ?? "";
    form.nama_mhs = val.nama_mhs ?? "";
    form.nim = val.nim ?? "";
    form.prodi = val.prodi ?? "";
    form.dosen_pembimbing = val.dosen_pembimbing ?? "";
    form.judul_skripsi = val.judul_skripsi ?? "";
    form.tanggal_sidang = val.tanggal_sidang ? val.tanggal_sidang.slice(0, 10) : "";
    form.no_hp = val.no_hp ?? "";

    if (form.nama_mhs) {
      listMhs.value = {
        nama: form.nama_mhs,
        nim: val.nim || "",
        id: form.nama_mhs,
      };
      optionsMhs.value = [listMhs.value];
    }

    if (form.dosen_pembimbing) {
      listDosen.value = {
        nama: form.dosen_pembimbing,
        nama_gelar: form.dosen_pembimbing,
      };
      optionsDosen.value = [listDosen.value];
    }

    isLoadingData.value = false;
    await nextTick();
    disableListMhsWatcher.value = false;
    disableListDosenWatcher.value = false;
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
          <div class="card-header justify-content-between">
            <div class="card-title">
              {{ isEdit ? "Edit" : "Tambah" }} Formulir Pengambilan Ijazah
            </div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <!-- Bagian Pencarian Mahasiswa -->
              <div class="col-xl-12">
                <div class="card-title text-primary fw-semibold mb-1">
                  <i class="ri-user-search-line me-1"></i> Data Mahasiswa
                </div>
              </div>

              <!-- Multiselect Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">
                  Cari Mahasiswa (NIM / Nama) <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="optionsMhs"
                  v-model="listMhs"
                  :internal-search="false"
                  @search-change="getMhs"
                  label="nama"
                  track-by="nim"
                  :searchable="true"
                  :loading="loadingMhs"
                  :custom-label="customNameMhs"
                  placeholder="Ketik NIM atau Nama Mahasiswa..."
                  :class="{ 'border-danger': errors?.nama_mhs }"
                ></Multiselect>
                <div v-if="errors?.nama_mhs" class="text-danger small mt-1">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <!-- Input Nama Mahasiswa (Otomatis Terisi) -->
              <div class="col-xl-6">
                <label class="form-label">
                  Nama Mahasiswa <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nama_mhs"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_mhs }"
                  placeholder="Otomatis terisi saat memilih mahasiswa..."
                />
                <div v-if="errors?.nama_mhs" class="invalid-feedback">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <!-- Input NIM / NRIM (Otomatis Terisi) -->
              <div class="col-xl-6">
                <label class="form-label">
                  NIM / NRIM:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nim"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  placeholder="Otomatis terisi NIM mahasiswa..."
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <!-- Input Prodi (Otomatis Terisi) -->
              <div class="col-xl-6">
                <label class="form-label">
                  Program Studi <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.prodi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.prodi }"
                  placeholder="Otomatis terisi program studi..."
                />
                <div v-if="errors?.prodi" class="invalid-feedback">
                  {{ errors.prodi[0] }}
                </div>
              </div>

              <!-- Input Nomor HP -->
              <div class="col-xl-6">
                <label class="form-label">Nomor HP / WhatsApp:</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.no_hp"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.no_hp }"
                  placeholder="Contoh: 081234567890"
                />
                <div v-if="errors?.no_hp" class="invalid-feedback">
                  {{ errors.no_hp[0] }}
                </div>
              </div>

              <div class="col-xl-12"><hr class="my-2" /></div>

              <!-- Bagian Dosen Pembimbing & Sidang -->
              <div class="col-xl-12">
                <div class="card-title text-primary fw-semibold mb-1">
                  <i class="ri-user-star-line me-1"></i> Data Pembimbing & Sidang
                </div>
              </div>

              <!-- Multiselect Dosen -->
              <div class="col-xl-6">
                <label class="form-label">
                  Cari Dosen Pembimbing:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="optionsDosen"
                  v-model="listDosen"
                  :internal-search="false"
                  @search-change="getDosen"
                  label="nama"
                  track-by="nidn"
                  :searchable="true"
                  :loading="loadingDosen"
                  :custom-label="customNameDosen"
                  placeholder="Ketik Nama Dosen Pembimbing..."
                  :class="{ 'border-danger': errors?.dosen_pembimbing }"
                ></Multiselect>
                <div v-if="errors?.dosen_pembimbing" class="text-danger small mt-1">
                  {{ errors.dosen_pembimbing[0] }}
                </div>
              </div>

              <!-- Input Dosen Pembimbing (Otomatis Terisi) -->
              <div class="col-xl-6">
                <label class="form-label">
                  Nama Dosen Pembimbing <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.dosen_pembimbing"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.dosen_pembimbing }"
                  placeholder="Otomatis terisi saat memilih dosen..."
                />
                <div v-if="errors?.dosen_pembimbing" class="invalid-feedback">
                  {{ errors.dosen_pembimbing[0] }}
                </div>
              </div>

              <!-- Input Tanggal Sidang -->
              <div class="col-xl-6">
                <label class="form-label">
                  Tanggal Sidang <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="date"
                  v-model="form.tanggal_sidang"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.tanggal_sidang }"
                />
                <div v-if="errors?.tanggal_sidang" class="invalid-feedback">
                  {{ errors.tanggal_sidang[0] }}
                </div>
              </div>

              <!-- Input Judul Skripsi -->
              <div class="col-xl-12">
                <label class="form-label">
                  Judul Skripsi <span class="text-danger">*</span>:
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <textarea
                  v-else
                  rows="3"
                  v-model="form.judul_skripsi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.judul_skripsi }"
                  dir="auto"
                  placeholder="Masukkan judul skripsi mahasiswa..."
                ></textarea>
                <div v-if="errors?.judul_skripsi" class="invalid-feedback">
                  {{ errors.judul_skripsi[0] }}
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer text-end">
            <router-link
              to="/form-pengambilan-ijazah"
              class="btn btn-light btn-wave me-2"
            >
              <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
            </router-link>
            <button
              type="submit"
              class="btn btn-primary btn-wave"
              :disabled="btnLoading"
            >
              <span
                v-if="btnLoading"
                class="spinner-border spinner-border-sm align-middle me-1"
                role="status"
              ></span>
              <i v-else class="ri-save-line align-middle me-1"></i>
              {{ isEdit ? "Update Data" : "Simpan Data" }}
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
  background: #f0f2f5;
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    opacity: 0.6;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.6;
  }
}
</style>
