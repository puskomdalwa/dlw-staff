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
  prodi_id: 0,
  nim: "",
  nama_mhs: "",
  prodi: "",
  judul_proposal_skripsi: "",
  rumusan_masalah: "",
  dosen_penasehat: "",
  catatan_kprodi: "",
  jenis_kelamin: "",
  tanggal: new Date().toISOString().slice(0, 10),
  petanda_tangan: "tidak",
};

const form = reactive({ ...defaultForm });

const disableListMhsWatcher = ref(false);
const disableListDosenWatcher = ref(false);
const isLoadingData = ref(false);

const options = ref<any[]>([]);
const loading = ref(false);
const listMhs = ref<any>(null);

const listProdi = ref<any[]>([]);

async function getProdi() {
  try {
    const response = await apiGet(`/get-prodi`);
    if (response.success) {
      const data = response.data?.data;
      listProdi.value = Array.isArray(data) ? data : [data];
      if (listProdi.value.length === 1 && !props.isEdit) {
        const singleProdi = listProdi.value[0];
        form.prodi_id = Number(singleProdi.id);
      }
    }
  } catch (error) {}
}

watch(listMhs, (val) => {
  if (disableListMhsWatcher.value) return;

  if (!val) {
    form.nama_mhs = "";
    form.nim = "";
    form.prodi = "";
    form.jenis_kelamin = "";
    return;
  }

  form.nama_mhs = val.nama || "";
  form.nim = val.nim || "";
  form.prodi =
    val.prodi_mhs ||
    val.nama_prodi ||
    (typeof val.prodi === "object"
      ? val.prodi?.nama || val.prodi?.alias || ""
      : val.prodi) ||
    val.alias_prodi ||
    "";

  if (val.jenis_kelamin) {
    form.jenis_kelamin = val.jenis_kelamin;
  }

  // Match prodi_id from listProdi if available and not already selected
  if (listProdi.value.length > 0 && form.prodi && !form.prodi_id) {
    const matchedProdi = listProdi.value.find(
      (p: any) =>
        p.nama?.toLowerCase() === form.prodi.toLowerCase() ||
        p.alias?.toLowerCase() === form.prodi.toLowerCase() ||
        p.alias?.toLowerCase() === (val.alias_prodi || "").toLowerCase()
    );
    if (matchedProdi) {
      form.prodi_id = Number(matchedProdi.id);
    }
  }
});

function customNameMhs(params: any) {
  if (!params) return "";
  const nama = params.nama || params.nama_mhs || "";
  const prodi =
    (typeof params.prodi === "object" && params.prodi !== null
      ? params.prodi?.nama || params.prodi?.alias
      : params.prodi) ||
    params.prodi_mhs ||
    params.nama_prodi ||
    params.alias_prodi ||
    "";
  return prodi ? `${nama} - ${prodi}` : (nama || params.nim || "");
}

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

const listDosen = ref<any>(null);
const optionsDosen = ref<any[]>([]);
const loadingDosen = ref(false);

const getDosen = debounce(async (params: string) => {
  const keyword = params.trim();
  if (!keyword && !props.isEdit) {
    optionsDosen.value = [];
    return;
  }

  try {
    loadingDosen.value = true;
    const response = await apiGet(`/get-dosen`, { search: keyword });
    if (response.success) {
      optionsDosen.value = response.data?.data || [];
    }
  } catch (error) {
  } finally {
    loadingDosen.value = false;
  }
}, 300);

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
    const belakangNormalized = cleanGelarBelakang
      .replace(/\./g, "")
      .toLowerCase();
    const resultNormalized = result.replace(/\./g, "").toLowerCase();
    if (!resultNormalized.includes(belakangNormalized)) {
      result = `${result.replace(/^[,\s]+|[,\s]+$/g, "")}, ${cleanGelarBelakang}`;
    }
  }

  return result.trim();
}

function customNameDosen(params: any) {
  const nameWithTitle = formatNamaDosen(params);
  return params.nidn ? `${nameWithTitle} - ${params.nidn}` : nameWithTitle;
}

watch(listDosen, (val) => {
  if (disableListDosenWatcher.value) return;
  if (val) {
    form.dosen_penasehat = formatNamaDosen(val);
  } else {
    form.dosen_penasehat = "";
  }
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
    disableListDosenWatcher.value = true;
    isLoadingData.value = true;

    await new Promise((resolve) => setTimeout(resolve, 300));
    Object.assign(form, val);

    form.id = val.id ?? "";
    form.prodi_id = val.prodi_id ? Number(val.prodi_id) : 0;
    form.nim = val.nim ?? "";
    form.nama_mhs = val.nama_mhs ?? "";
    form.prodi = val.prodi ?? "";
    form.judul_proposal_skripsi = val.judul_proposal_skripsi ?? "";
    form.rumusan_masalah = val.rumusan_masalah ?? "";
    form.dosen_penasehat = val.dosen_penasehat ?? "";
    form.catatan_kprodi = val.catatan_kprodi ?? "";
    form.jenis_kelamin = val.jenis_kelamin ?? "";
    form.tanggal = val.tanggal
      ? val.tanggal.slice(0, 10)
      : new Date().toISOString().slice(0, 10);
    form.petanda_tangan = val.petanda_tangan ?? "tidak";

    if (form.nim) {
      listMhs.value = {
        nim: form.nim,
        nama: form.nama_mhs,
        prodi: form.prodi,
        id: form.nim,
      };
      options.value = [listMhs.value];
    }

    if (form.dosen_penasehat) {
      listDosen.value = {
        nama: form.dosen_penasehat,
        nama_gelar: form.dosen_penasehat,
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

onMounted(() => {
  getProdi();
});

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
              {{ isEdit ? "Edit" : "Tambah" }} Formulir Pengajuan Judul Skripsi
            </div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-12">
                <label class="form-label">Program Studi Unit:</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id }"
                  v-model="form.prodi_id"
                >
                  <option :value="0">-- Pilih Prodi Unit --</option>
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

              <hr />
              <div class="card-title mb-0">Data Mahasiswa</div>

              <div class="col-xl-6">
                <label class="form-label">Cari Mahasiswa (NIM / Nama) :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="options"
                  v-model="listMhs"
                  :internal-search="false"
                  @search-change="getMhs"
                  label="nama"
                  track-by="nim"
                  :searchable="true"
                  :loading="loading"
                  :custom-label="customNameMhs"
                  placeholder="Cari Mahasiswa (NIM / Nama)"
                  :class="{
                    'border-danger': errors?.nim || errors?.nama_mhs,
                  }"
                ></Multiselect>
                <div v-if="errors?.nim" class="text-danger small">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Nama Mahasiswa :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nama_mhs"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_mhs }"
                  placeholder="Nama Mahasiswa"
                />
                <div v-if="errors?.nama_mhs" class="invalid-feedback">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">NIM :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nim"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  placeholder="NIM Mahasiswa"
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Program Studi :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.prodi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.prodi }"
                  placeholder="Otomatis terisi saat memilih mahasiswa"
                  readonly
                />
                <div v-if="errors?.prodi" class="invalid-feedback">
                  {{ errors.prodi[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0">
                Dosen Penasehat Akademik & Pengajuan Judul
              </div>

              <div class="col-xl-4">
                <label class="form-label">Cari Dosen Penasehat Akademik :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="optionsDosen"
                  v-model="listDosen"
                  :internal-search="false"
                  @search-change="getDosen"
                  label="nama"
                  track-by="nama"
                  :searchable="true"
                  :loading="loadingDosen"
                  :custom-label="customNameDosen"
                  placeholder="Cari Dosen Penasehat Akademik"
                ></Multiselect>
              </div>

              <div class="col-xl-4">
                <label class="form-label">Nama Dosen Penasehat Akademik :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.dosen_penasehat"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.dosen_penasehat }"
                  placeholder="Nama Dosen Penasehat Akademik"
                />
                <div v-if="errors?.dosen_penasehat" class="invalid-feedback">
                  {{ errors.dosen_penasehat[0] }}
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
                <label class="form-label">Judul Proposal Skripsi :</label>
                <div
                  v-if="isLoadingData"
                  class="skeleton-input"
                  style="height: 62px"
                ></div>
                <textarea
                  v-else
                  v-model="form.judul_proposal_skripsi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.judul_proposal_skripsi }"
                  rows="3"
                  dir="auto"
                  placeholder="Isikan Judul Proposal Skripsi"
                ></textarea>
                <div
                  v-if="errors?.judul_proposal_skripsi"
                  class="invalid-feedback"
                >
                  {{ errors.judul_proposal_skripsi[0] }}
                </div>
              </div>

              <div class="col-xl-12">
                <label class="form-label"
                  >Rumusan Masalah / Fokus Penelitian :</label
                >
                <div
                  v-if="isLoadingData"
                  class="skeleton-input"
                  style="height: 80px"
                ></div>
                <textarea
                  v-else
                  v-model="form.rumusan_masalah"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.rumusan_masalah }"
                  rows="4"
                  dir="auto"
                  placeholder="Isikan Rumusan Masalah / Fokus Penelitian"
                ></textarea>
                <div v-if="errors?.rumusan_masalah" class="invalid-feedback">
                  {{ errors.rumusan_masalah[0] }}
                </div>
              </div>

              <div class="col-xl-12">
                <label class="form-label">Catatan Kaprodi :</label>
                <div
                  v-if="isLoadingData"
                  class="skeleton-input"
                  style="height: 62px"
                ></div>
                <textarea
                  v-else
                  v-model="form.catatan_kprodi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.catatan_kprodi }"
                  rows="3"
                  dir="auto"
                  placeholder="Isikan Catatan Kaprodi (Kosongkan jika ingin ditampilkan titik-titik)"
                ></textarea>
                <div v-if="errors?.catatan_kprodi" class="invalid-feedback">
                  {{ errors.catatan_kprodi[0] }}
                </div>
              </div>

              <div class="col-xl-12 mt-3">
                <label class="form-label fw-bold"
                  >Pakai Tanda Tangan & Stempel :</label
                >
                <div
                  v-if="isLoadingData"
                  class="skeleton-input"
                  style="width: 150px"
                ></div>
                <div v-else class="d-flex align-items-center mt-2">
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      :name="'petanda_tangan_' + Date.now()"
                      id="ttd_keduanya"
                      value="ya"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="ttd_keduanya"
                      >tandatangan+stempel</label
                    >
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      :name="'petanda_tangan_' + Date.now()"
                      id="ttd_kosong"
                      value="tidak"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="ttd_kosong"
                      >kosong</label
                    >
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      :name="'petanda_tangan_' + Date.now()"
                      id="ttd_stempel"
                      value="stempel"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="ttd_stempel"
                      >stempel saja</label
                    >
                  </div>
                </div>
                <div
                  v-if="errors?.petanda_tangan"
                  class="invalid-feedback d-block"
                >
                  {{ errors.petanda_tangan[0] }}
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button
              class="btn btn-primary-light btn-wave ms-auto float-end"
              :disabled="btnLoading"
            >
              <span
                v-if="btnLoading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              {{
                btnLoading
                  ? isEdit
                    ? "Mengupdate..."
                    : "Menyimpan..."
                  : isEdit
                    ? "Update"
                    : "Simpan"
              }}
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

