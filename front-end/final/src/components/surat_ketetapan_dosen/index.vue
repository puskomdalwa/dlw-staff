<script lang="ts" setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import { debounce } from "vuetify/lib/util/helpers.mjs";
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
  institusi_id: "" as string | number,
  no_surat: "",
  nomor: "",
  nama_dosen: "",
  jabatan: "Dosen Tetap",
  nidn: "",
  instansi: "",
  jenis_kelamin: "L",
  alamat_penandatangan: "Jl. Raya Raci No. 51 Bangil-Pasuruan",
  tempat_ditetapkan: "Bangil",
  tanggal: new Date().toISOString().slice(0, 10),
  petanda_tangan: "ya",
};

const form = reactive({ ...defaultForm });

const listInstitusi = ref<any[]>([]);
const listJenisSurat = ref<any[]>([]);
const optionsDosen = ref<any[]>([]);
const loadingDosen = ref(false);
const selectedDosen = ref<any>(null);
const disableDosenWatcher = ref(false);
const disableInstitusiWatcher = ref(false);
const isLoadingData = ref(false);

const selectedInstitusi = computed(() =>
  listInstitusi.value.find(
    (item) => Number(item.id) === Number(form.institusi_id)
  )
);

const selectedAlias = computed(() => {
  const nama = (selectedInstitusi.value?.nama_institusi || "").toLowerCase();
  if (nama.includes("universitas islam internasional") || nama.includes("uii")) {
    return "SKD-UII";
  }
  if (nama.includes("institut agama islam") || nama.includes("iai")) {
    return "SKD-IAI";
  }
  return "SKD-UII";
});

const formatParts = computed(() => {
  const js = listJenisSurat.value.find(
    (x: any) => String(x.alias || "").toUpperCase() === selectedAlias.value
  );
  if (!js || !js.format_surat) {
    return { prefix: "SU-", suffix: "" };
  }

  const dateObj = form.tanggal ? new Date(form.tanggal) : new Date();
  const dd = String(dateObj.getDate()).padStart(2, "0");
  const mm = String(dateObj.getMonth() + 1).padStart(2, "0");
  const yyyy = String(dateObj.getFullYear());

  const formatted = String(js.format_surat)
    .replace(/\{TGL\}/g, dd)
    .replace(/\{BULAN\}/g, mm)
    .replace(/\{TAHUN\}/g, yyyy);

  const splitted = formatted.split(/\{NOMOR\}|\{NO\}/);
  return {
    prefix: splitted[0] || "",
    suffix: splitted[1] || "",
  };
});

function extractNo(fullStr: string): string {
  if (!fullStr) return "";
  const firstPart = fullStr.split("/")[0];
  if (firstPart.includes("-")) {
    return firstPart.substring(firstPart.indexOf("-") + 1).trim();
  }
  return firstPart.trim();
}

function onNoSuratInput(event: Event) {
  const input = event.target as HTMLInputElement;
  form.no_surat = input.value.replace(/[^0-9]/g, "");
}

async function getJenisSurat() {
  try {
    const response = await apiGet("/jenis-surat", { search: "SKD-", limit: 50 });
    if (response.success) {
      const paginatorOrData = response.data?.data ?? response.data;
      const rows = Array.isArray(paginatorOrData?.data)
        ? paginatorOrData.data
        : paginatorOrData;
      listJenisSurat.value = Array.isArray(rows) ? rows : rows ? [rows] : [];
    }
  } catch (error) {}
}

function customDosenLabel(item: any) {
  if (!item) return "";
  return item.nidn ? `${item.nama} - ${item.nidn}` : item.nama || "";
}

async function getInstitusi() {
  try {
    const response = await apiGet("/institusi", { all: true });
    if (response.success) {
      const rawData = response.data?.data ?? response.data;
      listInstitusi.value = Array.isArray(rawData)
        ? rawData
        : rawData
          ? [rawData]
          : [];
      if (!props.isEdit && listInstitusi.value.length === 1 && !form.institusi_id) {
        form.institusi_id = Number(listInstitusi.value[0].id);
        if (!form.instansi) {
          form.instansi = listInstitusi.value[0].nama_institusi || "";
        }
      }
    }
  } catch (error) {}
}

watch(
  () => form.institusi_id,
  (newId, oldId) => {
    if (disableInstitusiWatcher.value || !newId) return;
    const found = listInstitusi.value.find(
      (i) => Number(i.id) === Number(newId)
    );
    const prev = listInstitusi.value.find(
      (i) => Number(i.id) === Number(oldId)
    );
    if (
      found &&
      (!form.instansi || (prev && form.instansi === prev.nama_institusi))
    ) {
      form.instansi = found.nama_institusi || "";
    }
  }
);

watch(selectedDosen, async (val) => {
  if (!val || disableDosenWatcher.value) return;

  isLoadingData.value = true;
  await new Promise((resolve) => setTimeout(resolve, 200));

  form.nama_dosen = val.nama_gelar || val.nama || "";
  if (val.nidn) {
    form.nidn = String(val.nidn);
  }
  if (val.jenis_kelamin || val.jk) {
    const jk = String(val.jenis_kelamin || val.jk).toUpperCase();
    form.jenis_kelamin = jk.startsWith("P") ? "P" : "L";
  }

  isLoadingData.value = false;
});

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

watch(
  () => props.modelValue,
  async (val) => {
    if (!props.isEdit) return;
    if (!val || !val.nama_dosen) {
      isLoadingData.value = true;
      return;
    }

    disableDosenWatcher.value = true;
    disableInstitusiWatcher.value = true;
    isLoadingData.value = true;

    await new Promise((resolve) => setTimeout(resolve, 250));

    form.id = val.id ?? "";
    form.institusi_id = val.institusi_id ? Number(val.institusi_id) : "";
    form.no_surat = val.no_surat || extractNo(val.nomor || "");
    form.nomor = val.nomor ?? "";
    form.nama_dosen = val.nama_dosen ?? "";
    form.jabatan = val.jabatan ?? "Dosen Tetap";
    form.nidn = val.nidn ?? "";
    form.instansi = val.instansi ?? "";
    form.jenis_kelamin = val.jenis_kelamin ?? "L";
    form.alamat_penandatangan =
      val.alamat_penandatangan ?? "Jl. Raya Raci No. 51 Bangil-Pasuruan";
    form.tempat_ditetapkan = val.tempat_ditetapkan ?? "Bangil";
    form.tanggal = val.tanggal
      ? String(val.tanggal).slice(0, 10)
      : new Date().toISOString().slice(0, 10);
    form.petanda_tangan = val.petanda_tangan ?? "ya";

    if (form.nama_dosen) {
      selectedDosen.value = {
        nama: form.nama_dosen,
        nidn: form.nidn,
      };
      optionsDosen.value = [selectedDosen.value];
    }

    isLoadingData.value = false;
    await nextTick();
    disableDosenWatcher.value = false;
    disableInstitusiWatcher.value = false;
  },
  { immediate: true }
);

onMounted(() => {
  getJenisSurat();
  getInstitusi();
});

const emit = defineEmits(["submit"]);

function submitForm() {
  emit("submit", { ...form });
}
</script>

<template>
  <div class="row">
    <form @submit.prevent="submitForm">
      <div class="col-xl-12">
        <div class="card custom-card">
          <div class="card-header">
            <div class="card-title">
              {{ isEdit ? "Edit" : "Tambah" }} Surat Ketetapan Dosen
            </div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-6">
                <label class="form-label">
                  Institusi <span class="text-danger">*</span>
                </label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.institusi_id }"
                  v-model="form.institusi_id"
                  required
                >
                  <option value="" disabled>-- Pilih Institusi --</option>
                  <option
                    v-for="inst in listInstitusi"
                    :key="inst.id"
                    :value="Number(inst.id)"
                  >
                    {{ inst.nama_institusi }}
                  </option>
                </select>
                <small v-if="selectedInstitusi?.rektor" class="text-muted d-block mt-1">
                  Penandatangan (Rektor): <strong>{{ selectedInstitusi.rektor }}</strong>
                </small>
                <div v-if="errors?.institusi_id" class="invalid-feedback">
                  {{ errors.institusi_id[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Pencarian Dosen (Opsional) :</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <Multiselect
                  :options="optionsDosen"
                  v-model="selectedDosen"
                  :internal-search="false"
                  @search-change="getDosen"
                  label="nama"
                  track-by="nama"
                  :searchable="true"
                  :loading="loadingDosen"
                  :custom-label="customDosenLabel"
                  placeholder="Ketik Nama atau NIDN Dosen..."
                ></Multiselect>
                <small class="text-muted d-block mt-1">
                  Pilih dari daftar untuk mengisi Nama &amp; NIDN otomatis, atau isi manual di bawah.
                </small>
              </div>

              <hr />
              <div class="card-title mb-0">Informasi Surat &amp; Data Dosen</div>

              <div class="col-xl-6">
                <label class="form-label">
                  Nomor Surat <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <template v-else>
                  <div class="input-group">
                    <span class="input-group-text" v-if="formatParts.prefix">{{ formatParts.prefix }}</span>
                    <input
                      type="text"
                      inputmode="numeric"
                      :value="form.no_surat"
                      @input="onNoSuratInput"
                      class="form-control"
                      :class="{ 'is-invalid': errors?.no_surat || errors?.nomor }"
                      placeholder="Masukkan angka nomor surat (misal: 3593)"
                      required
                    />
                    <span class="input-group-text" v-if="formatParts.suffix">{{ formatParts.suffix }}</span>
                  </div>
                  <small v-if="form.institusi_id" class="text-muted d-block mt-1">
                    Preview ({{ selectedAlias }}):
                    <strong>{{ formatParts.prefix }}{{ form.no_surat || "..." }}{{ formatParts.suffix }}</strong>
                  </small>
                  <small v-else class="text-muted d-block mt-1">
                    Pilih institusi terlebih dahulu untuk melihat format nomor surat.
                  </small>
                  <div v-if="errors?.no_surat || errors?.nomor" class="invalid-feedback d-block">
                    {{ errors?.no_surat ? errors.no_surat[0] : errors?.nomor?.[0] }}
                  </div>
                </template>
              </div>

              <div class="col-xl-6">
                <label class="form-label">
                  Nama Dosen <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nama_dosen"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_dosen }"
                  placeholder="Contoh: Dr. Nunuk Indarti, M.M"
                  required
                />
                <div v-if="errors?.nama_dosen" class="invalid-feedback">
                  {{ errors.nama_dosen[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">
                  Jabatan <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.jabatan"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.jabatan }"
                  placeholder="Contoh: Dosen Tetap"
                  required
                />
                <div v-if="errors?.jabatan" class="invalid-feedback">
                  {{ errors.jabatan[0] }}
                </div>
              </div>

              <div class="col-xl-4">
                <label class="form-label">NIDN :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nidn"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nidn }"
                  placeholder="Contoh: 80260"
                />
                <div v-if="errors?.nidn" class="invalid-feedback">
                  {{ errors.nidn[0] }}
                </div>
              </div>

              <div class="col-xl-4">
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

              <div class="col-xl-6">
                <label class="form-label">
                  Instansi <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.instansi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.instansi }"
                  placeholder="Contoh: Institut Agama Islam Darullughah Wadda'wah"
                  required
                />
                <div v-if="errors?.instansi" class="invalid-feedback">
                  {{ errors.instansi[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Alamat Penandatangan :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.alamat_penandatangan"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.alamat_penandatangan }"
                  placeholder="Jl. Raya Raci No. 51 Bangil-Pasuruan"
                />
                <div v-if="errors?.alamat_penandatangan" class="invalid-feedback">
                  {{ errors.alamat_penandatangan[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Ditetapkan di :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.tempat_ditetapkan"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.tempat_ditetapkan }"
                  placeholder="Bangil"
                />
                <div v-if="errors?.tempat_ditetapkan" class="invalid-feedback">
                  {{ errors.tempat_ditetapkan[0] }}
                </div>
              </div>

              <div class="col-xl-6">
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

              <div class="col-xl-12 mt-3">
                <label class="form-label fw-bold">Pakai Tanda Tangan &amp; Stempel :</label>
                <div v-if="isLoadingData" class="skeleton-input" style="width: 150px;"></div>
                <div v-else class="d-flex align-items-center mt-2">
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_skd"
                      id="skd_ttd_ya"
                      value="ya"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="skd_ttd_ya">TTD + Stempel</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_skd"
                      id="skd_ttd_kosong"
                      value="tidak"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="skd_ttd_kosong">Kosong (Tanda Tangan Basah)</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="petanda_tangan_skd"
                      id="skd_ttd_stempel"
                      value="stempel"
                      v-model="form.petanda_tangan"
                    />
                    <label class="form-check-label" for="skd_ttd_stempel">Stempel Saja</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex justify-content-between">
            <router-link to="/surat-ketetapan-dosen" class="btn btn-secondary-light btn-wave">
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
