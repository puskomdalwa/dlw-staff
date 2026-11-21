<script lang="ts" setup>
import { reactive, ref, watch, onMounted, nextTick, computed } from "vue";
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
  no_surat: "",
  prodi_id: 0,
  nama: "",
  nim: "",
  prodi: "",
  tanggal_sidang: "",
  tanggal: new Date().toISOString().slice(0, 10),
  kepala: "Kuzairi, M.Pd",
  nidn: "80283",
  petanda_tangan: "ya",
};

const disableListMhsWatcher = ref(false);
const form = reactive({ ...defaultForm });

const options = ref<any[]>([]);
const loading = ref(false);
const isLoadingData = ref(false);
const listMhs = ref<any>(null);

function customName(params: any) {
  return `${params.nama} - ${params.nim}`;
}

watch(listMhs, async (val) => {
  if (!val) return;
  if (disableListMhsWatcher.value) return;

  isLoadingData.value = true;
  await new Promise((resolve) => setTimeout(resolve, 300));

  form.nama = val.nama || "";
  form.nim = val.nim || "";

  if (val.alias_prodi) {
    form.prodi = val.alias_prodi;
  } else {
    form.prodi = val.prodi_mhs || "";
  }

  if (val.prodi_id) {
    form.prodi_id = Number(val.prodi_id);
  }

  isLoadingData.value = false;
});

const listProdi = ref<any[]>([]);
const listJenisSurat = ref<any[]>([]);

async function getProdi() {
  try {
    const response = await apiGet(`/get-prodi`);
    if (response.success) {
      const data = response.data?.data;
      listProdi.value = Array.isArray(data) ? data : [data];
      if (listProdi.value.length === 1 && !form.prodi_id) {
        form.prodi_id = Number(listProdi.value[0].id);
      }
    }
  } catch (error) {}
}

async function getJenisSurat() {
  try {
    const response = await apiGet(`/jenis-surat`);
    if (response.success) {
      const data = response.data?.data || response.data;
      listJenisSurat.value = Array.isArray(data) ? data : [data];
    }
  } catch (error) {}
}

function getRoman(num: number) {
  const roman: any = { 1: "I", 2: "II", 3: "III", 4: "IV", 5: "V", 6: "VI", 7: "VII", 8: "VIII", 9: "IX", 10: "X", 11: "XI", 12: "XII" };
  return roman[num] || "";
}

const formatParts = computed(() => {
  const js = listJenisSurat.value.find((x: any) => x.alias === "SKSS");
  let str = js ? js.format_surat : "SU-{NO}/UII.085/REK/001/{BULAN}/{TAHUN}";

  const dateObj = form.tanggal ? new Date(form.tanggal) : new Date();
  const dd = String(dateObj.getDate()).padStart(2, "0");
  const romanBulan = getRoman(dateObj.getMonth() + 1);
  const mm = String(dateObj.getMonth() + 1).padStart(2, "0");
  const yyyy = dateObj.getFullYear();

  let aliasProdi = "";
  if (listProdi.value) {
    const prodiItem = listProdi.value.find((p: any) => Number(p.id) === Number(form.prodi_id));
    aliasProdi = prodiItem ? prodiItem.alias : "";
  }

  str = str.replace(/{TGL}/g, dd)
           .replace(/{BULAN}/g, mm)
           .replace(/{TAHUN}/g, String(yyyy))
           .replace(/{PRODI}/g, aliasProdi);

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

onMounted(() => {
  getJenisSurat();
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

    Object.assign(form, val);

    form.id = val.id ?? "";
    form.no_surat = extractNo(val.no_surat ?? val.nomor_surat ?? "");
    form.nama = val.nama ?? val.nama_lengkap ?? "";
    form.nim = val.nim ?? "";
    form.prodi = val.prodi ?? "";
    form.prodi_id = val.prodi_id ?? 0;
    form.tanggal_sidang = val.tanggal_sidang ? val.tanggal_sidang.slice(0, 10) : "";
    form.tanggal = val.tanggal ? val.tanggal.slice(0, 10) : new Date().toISOString().slice(0, 10);
    form.kepala = val.kepala || "Kuzairi, M.Pd";
    form.nidn = val.nidn || "80283";
    form.petanda_tangan = val.petanda_tangan || "ya";

    if (form.nim) {
      listMhs.value = {
        nim: form.nim,
        nama: form.nama,
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
              {{ isEdit ? "Edit" : "Tambah" }} Surat Keterangan Sudah Sidang
            </div>
          </div>
          <div class="card-body">
            <div class="row gy-3">
              <div class="col-xl-6">
                <label class="form-label">Program Studi Unit:</label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id }"
                  v-model="form.prodi_id"
                >
                  <option value="0">Pilih Program Studi (Opsional)</option>
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
                <label class="form-label">Pencarian Mahasiswa (NIM):</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <Multiselect
                  :options="options"
                  v-model="listMhs"
                  :internal-search="false"
                  @search-change="getMhs"
                  label="nama"
                  track-by="id"
                  :searchable="true"
                  :loading="loading"
                  :custom-label="customName"
                  :class="{ 'border-danger': errors?.nim || errors?.nama }"
                  placeholder="Ketik Nama atau NIM..."
                ></Multiselect>
                <div v-if="errors?.nim" class="text-danger small mt-1">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0">Informasi Mahasiswa</div>

              <div class="col-xl-6">
                <label class="form-label">Nama Mahasiswa :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.nama"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama }"
                  placeholder="Isikan Nama Mahasiswa"
                />
                <div v-if="errors?.nama" class="invalid-feedback">
                  {{ errors.nama[0] }}
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
                  placeholder="Isikan NIM"
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
                  placeholder="Isikan Program Studi (Contoh: PBA)"
                />
                <div v-if="errors?.prodi" class="invalid-feedback">
                  {{ errors.prodi[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">Tanggal Sidang Skripsi :</label>
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

              <hr />
              <div class="card-title mb-0">Informasi Surat & Tanda Tangan</div>

              <div class="col-xl-6">
                <label class="form-label">Nomor Surat :</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <div class="input-group" v-else>
                  <span class="input-group-text" v-if="formatParts.prefix">{{ formatParts.prefix }}</span>
                  <input
                    type="text"
                    v-model="form.no_surat"
                    class="form-control"
                    :class="{ 'is-invalid': errors?.nomor_surat || errors?.no_surat }"
                    placeholder="Contoh: 3395"
                  />
                  <span class="input-group-text" v-if="formatParts.suffix">{{ formatParts.suffix }}</span>
                  <div v-if="errors?.nomor_surat || errors?.no_surat" class="invalid-feedback">
                    {{ errors?.nomor_surat ? errors.nomor_surat[0] : errors?.no_surat[0] }}
                  </div>
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

              <div class="col-xl-6">
                <label class="form-label">Nama Penandatangan (Kepala BAAK) :</label>
                <input
                  type="text"
                  v-model="form.kepala"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.kepala }"
                  placeholder="Contoh: Kuzairi, M.Pd"
                />
                <div v-if="errors?.kepala" class="invalid-feedback">
                  {{ errors.kepala[0] }}
                </div>
              </div>

              <div class="col-xl-6">
                <label class="form-label">NIY / NIDN :</label>
                <input
                  type="text"
                  v-model="form.nidn"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nidn }"
                  placeholder="Contoh: 80283"
                />
                <div v-if="errors?.nidn" class="invalid-feedback">
                  {{ errors.nidn[0] }}
                </div>
              </div>

              <div class="col-xl-12 mt-3">
                <label class="form-label fw-bold">Pakai Tanda Tangan / QR Code & Stempel :</label>
                <div v-if="isLoadingData" class="skeleton-input" style="width: 150px;"></div>
                <div v-else class="d-flex align-items-center mt-2">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" :name="'petanda_tangan_' + Date.now()" id="ttd_keduanya" value="ya" v-model="form.petanda_tangan">
                    <label class="form-check-label" for="ttd_keduanya">QR Code / TTD + Stempel</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" :name="'petanda_tangan_' + Date.now()" id="ttd_kosong" value="tidak" v-model="form.petanda_tangan">
                    <label class="form-check-label" for="ttd_kosong">Kosong (Tanda Tangan Basah)</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" :name="'petanda_tangan_' + Date.now()" id="ttd_stempel" value="stempel" v-model="form.petanda_tangan">
                    <label class="form-check-label" for="ttd_stempel">Stempel Saja</label>
                  </div>
                </div>
                <div v-if="errors?.petanda_tangan" class="invalid-feedback d-block">
                  {{ errors.petanda_tangan[0] }}
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-primary-light btn-wave ms-auto float-end" :disabled="btnLoading">
              <span v-if="btnLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ btnLoading ? (isEdit ? "Mengupdate..." : "Menyimpan...") : (isEdit ? "Update" : "Simpan") }}
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

