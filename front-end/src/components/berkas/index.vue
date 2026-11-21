<script lang="ts" setup>
import { ref, reactive, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import CameraScanner from "./CameraScanner.vue";
import { apiGet } from "../../services/api/request";
import { toast } from "vue3-toastify";

const props = defineProps<{
  modelValue?: any;
  isEdit?: boolean;
  errors?: Record<string, string[]>;
  initialTab?: "non_skripsi" | "skripsi";
  btnLoading?: boolean;
}>();

const emit = defineEmits<{
  (e: "submit", formData: FormData): void;
}>();

// Form Model
const form = reactive({
  id: "",
  nama_mhs: "",
  nim: "",
  prodi_id: "" as string | number,
  kategori_berkas_id: "" as string | number,
  nama_dokumen: "",
  jenis_kelamin: "L",
  tipe_berkas: (props.initialTab || "non_skripsi") as "non_skripsi" | "skripsi",
  file_base64: "",
  file: null as File | null,
});

// Category list and Prodi list
const listKategori = ref<any[]>([]);
const listProdi = ref<any[]>([]);
const hasUserProdi = ref(false);

// Multiselect for Mahasiswa
const listMhs = ref<any>(null);
const optionsMhs = ref<any[]>([]);
const isSearchingMhs = ref(false);

// Scanner Modal State
const showScanner = ref(false);
const activeScannerRowIndex = ref<number | null>(null); // null = Tab 1 single file, number = Tab 2 doc row index
const previewDocument = ref<string | null>(null);
const selectedFileName = ref<string | null>(null);

// ==========================================
// TAB 2: BERKAS SKRIPSI (Mencontoh Form Pendaftaran Sidang Skripsi)
// ==========================================
interface SkripsiDocItem {
  nama_dokumen: string;
  file: File | null;
  file_base64: string;
  fileName?: string;
  preview?: string | null;
  required?: boolean;
  hint?: string;
  accept?: string;
  isPreset?: boolean;
}

const standardSidangDocs = [
  {
    nama_dokumen: "File Skripsi",
    required: true,
    accept: ".pdf,.doc,.docx",
    hint: "File naskah skripsi lengkap",
  },
  {
    nama_dokumen: "Lembar Konsultasi Dospem 1",
    required: true,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Bukti lembar bimbingan dosen pembimbing 1",
  },
  {
    nama_dokumen: "Lembar Konsultasi Dospem 2",
    required: false,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Opsional jika hanya memiliki 1 pembimbing (boleh tidak diisi)",
  },
  {
    nama_dokumen: "File Foto 3x4",
    required: true,
    accept: ".jpg,.jpeg,.png",
    hint: "Pasfoto resmi 3x4 berdasi latar belakang merah tanpa aksesoris",
  },
  {
    nama_dokumen: "File Nota Pembimbing",
    required: true,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Bukti persetujuan/ACC ujian skripsi dari pembimbing",
  },
];

const skripsiDocuments = ref<SkripsiDocItem[]>(
  standardSidangDocs.map((d) => ({
    nama_dokumen: d.nama_dokumen,
    file: null,
    file_base64: "",
    fileName: "",
    preview: null,
    required: d.required,
    hint: d.hint,
    accept: d.accept,
    isPreset: true,
  }))
);

const showDropdown = ref(false);

function toggleDropdown() {
  showDropdown.value = !showDropdown.value;
}

function addPresetRow(preset: (typeof standardSidangDocs)[0]) {
  const existing = skripsiDocuments.value.find(
    (d) =>
      d.nama_dokumen.toLowerCase().trim() ===
      preset.nama_dokumen.toLowerCase().trim()
  );
  if (existing) {
    toast.info(`Dokumen "${preset.nama_dokumen}" sudah ada di daftar.`);
  } else {
    skripsiDocuments.value.push({
      nama_dokumen: preset.nama_dokumen,
      file: null,
      file_base64: "",
      fileName: "",
      preview: null,
      required: preset.required,
      hint: preset.hint,
      accept: preset.accept,
      isPreset: true,
    });
    toast.success(`Dokumen "${preset.nama_dokumen}" ditambahkan.`);
  }
  showDropdown.value = false;
}

function addCustomRow() {
  skripsiDocuments.value.push({
    nama_dokumen: "",
    file: null,
    file_base64: "",
    fileName: "",
    preview: null,
    required: false,
    hint: "Dokumen persyaratan tambahan",
    accept: "*",
    isPreset: false,
  });
  showDropdown.value = false;
}

function loadAllStandardDocs() {
  let addedCount = 0;
  standardSidangDocs.forEach((preset) => {
    const exists = skripsiDocuments.value.some(
      (d) =>
        d.nama_dokumen.toLowerCase().trim() ===
        preset.nama_dokumen.toLowerCase().trim()
    );
    if (!exists) {
      skripsiDocuments.value.push({
        nama_dokumen: preset.nama_dokumen,
        file: null,
        file_base64: "",
        fileName: "",
        preview: null,
        required: preset.required,
        hint: preset.hint,
        accept: preset.accept,
        isPreset: true,
      });
      addedCount++;
    }
  });
  if (addedCount > 0) {
    toast.success(`${addedCount} dokumen standar sidang berhasil dimuat.`);
  } else {
    toast.info("Semua dokumen standar sidang sudah ada di daftar.");
  }
}

function removeSkripsiRow(index: number) {
  skripsiDocuments.value.splice(index, 1);
}

function onSkripsiFileChange(e: Event, index: number) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    skripsiDocuments.value[index].file = file;
    skripsiDocuments.value[index].file_base64 = "";
    skripsiDocuments.value[index].fileName = file.name;
    if (!skripsiDocuments.value[index].nama_dokumen) {
      skripsiDocuments.value[index].nama_dokumen = file.name.replace(
        /\.[^/.]+$/,
        ""
      );
    }
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = () => {
        skripsiDocuments.value[index].preview = reader.result as string;
      };
      reader.readAsDataURL(file);
    } else {
      skripsiDocuments.value[index].preview = null;
    }
  }
}

function clearSkripsiRowFile(index: number) {
  skripsiDocuments.value[index].file = null;
  skripsiDocuments.value[index].file_base64 = "";
  skripsiDocuments.value[index].fileName = "";
  skripsiDocuments.value[index].preview = null;
}

// ==========================================
// MAHASISWA AUTOCOMPLETE & MASTERS
// ==========================================
let searchTimeout: any = null;
function onSearchMhs(query: string) {
  clearTimeout(searchTimeout);
  if (!query || query.trim().length < 2) {
    optionsMhs.value = [];
    return;
  }

  searchTimeout = setTimeout(async () => {
    try {
      isSearchingMhs.value = true;
      const res = await apiGet("/get-mhs", { search: query.trim() });
      if (res.success && res.data) {
        optionsMhs.value = Array.isArray(res.data)
          ? res.data
          : res.data.data || [];
      }
    } catch (e) {
      console.error(e);
    } finally {
      isSearchingMhs.value = false;
    }
  }, 350);
}

// Watch selected Mahasiswa
watch(listMhs, (val) => {
  if (val) {
    form.nama_mhs = val.nama || val.nama_lengkap || "";
    form.nim = val.nim || "";

    // Jika user yang login tidak punya prodi tetap, otomatis isi prodi berdasarkan prodi mahasiswa yang dipilih
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
        }) ||
        listProdi.value.find((p) => Number(p.id) === Number(val.prodi_id));

      if (matched) {
        form.prodi_id = Number(matched.id);
      }
    }

    if (val.jenis_kelamin || val.jk) {
      const jk = (val.jenis_kelamin || val.jk).toUpperCase();
      form.jenis_kelamin = jk.startsWith("P") ? "P" : "L";
    }
  }
});

// Load Master Kategori & Prodi
async function fetchMasters() {
  try {
    const [resKategori, resProdi] = await Promise.all([
      apiGet("/kategori-berkas", { all: true }),
      apiGet("/get-prodi"),
    ]);

    if (resKategori.success && resKategori.data) {
      listKategori.value = Array.isArray(resKategori.data)
        ? resKategori.data
        : resKategori.data.data || [];
    }

    if (resProdi.success && resProdi.data) {
      const rawData = resProdi.data?.data ?? resProdi.data;
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
        // User memiliki prodi (endpoint /get-prodi mengembalikan 1 object Prodi)
        listProdi.value = [rawData];
        hasUserProdi.value = true;
        if (!props.isEdit) {
          form.prodi_id = Number(rawData.id);
        }
      }
    }
  } catch (error) {
    console.error("Failed to load master data:", error);
  }
}

// Switch Tab
function switchTab(tab: "non_skripsi" | "skripsi") {
  if (props.isEdit) return; // Pada edit single record, tab dikunci sesuai record
  form.tipe_berkas = tab;
}

// Scanner Handlers
function openScannerForSingle() {
  activeScannerRowIndex.value = null;
  showScanner.value = true;
}

function openScannerForRow(index: number) {
  activeScannerRowIndex.value = index;
  showScanner.value = true;
}

function handleCapturedScan(base64Image: string) {
  if (activeScannerRowIndex.value === null) {
    form.file_base64 = base64Image;
    form.file = null;
    previewDocument.value = base64Image;
    selectedFileName.value = "Hasil_Scan_Kamera.jpg";
  } else {
    const idx = activeScannerRowIndex.value;
    if (skripsiDocuments.value[idx]) {
      skripsiDocuments.value[idx].file_base64 = base64Image;
      skripsiDocuments.value[idx].file = null;
      skripsiDocuments.value[idx].preview = base64Image;
      skripsiDocuments.value[idx].fileName = `${
        skripsiDocuments.value[idx].nama_dokumen || "Dokumen"
      }_Scan.jpg`;
    }
  }
}

// Tab 1 File Input
function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (!target.files || target.files.length === 0) return;

  const file = target.files[0];
  form.file = file;
  form.file_base64 = "";
  selectedFileName.value = file.name;

  if (file.type.startsWith("image/")) {
    const reader = new FileReader();
    reader.onload = () => {
      previewDocument.value = reader.result as string;
    };
    reader.readAsDataURL(file);
  } else {
    previewDocument.value = null;
  }
}

function clearDocument() {
  form.file = null;
  form.file_base64 = "";
  previewDocument.value = null;
  selectedFileName.value = null;
}

// Form Submit
function handleSubmit() {
  if (!form.nim || !form.nama_mhs) {
    toast.warning("Data mahasiswa (NIM dan Nama) wajib diisi.");
    return;
  }

  const payload = new FormData();
  payload.append("nama_mhs", form.nama_mhs);
  payload.append("nim", form.nim);
  if (form.prodi_id) {
    payload.append("prodi_id", String(form.prodi_id));
  }
  if (form.kategori_berkas_id) {
    payload.append("kategori_berkas_id", String(form.kategori_berkas_id));
  }
  if (form.nama_dokumen) {
    payload.append("nama_dokumen", form.nama_dokumen);
  }
  payload.append("jenis_kelamin", form.jenis_kelamin);
  payload.append("tipe_berkas", form.tipe_berkas);

  if (form.id) {
    payload.append("id", String(form.id));
  }

  // Tab 2 Multiple Skripsi Documents (mode tambah)
  if (!props.isEdit && form.tipe_berkas === "skripsi") {
    let validFileCount = 0;
    skripsiDocuments.value.forEach((doc, idx) => {
      if (doc.file || doc.file_base64) {
        payload.append(`dokumen[${idx}][nama_dokumen]`, doc.nama_dokumen);
        if (doc.file) {
          payload.append(`dokumen_files[${idx}]`, doc.file);
        } else if (doc.file_base64) {
          payload.append(`dokumen_base64[${idx}]`, doc.file_base64);
        }
        validFileCount++;
      }
    });

    if (validFileCount === 0) {
      toast.warning("Minimal satu file atau hasil scan dokumen skripsi harus dilampirkan.");
      return;
    }
  } else {
    // Tab 1 Single Document atau Mode Edit
    if (form.file) {
      payload.append("file", form.file);
    } else if (form.file_base64) {
      payload.append("file_base64", form.file_base64);
    }
  }

  emit("submit", payload);
}

// Watch modelValue for Edit mode
watch(
  () => props.modelValue,
  (val) => {
    if (val && props.isEdit) {
      form.id = val.id || "";
      form.nama_mhs = val.nama_mhs || "";
      form.nim = val.nim || "";
      form.prodi_id = val.prodi_id ? Number(val.prodi_id) : "";
      form.kategori_berkas_id = val.kategori_berkas_id ? Number(val.kategori_berkas_id) : "";
      form.nama_dokumen = val.nama_dokumen || "";
      form.jenis_kelamin = val.jenis_kelamin || "L";
      form.tipe_berkas = val.tipe_berkas || "non_skripsi";

      if (val.nim) {
        listMhs.value = {
          nim: val.nim,
          nama: val.nama_mhs,
          prodi_id: val.prodi_id,
        };
      }

      if (val.drive_link || val.local_path) {
        selectedFileName.value = val.nama_dokumen || "Dokumen Tersimpan";
        if (val.local_path && !val.local_path.endsWith(".pdf")) {
          previewDocument.value = null;
        }
      }
    }
  },
  { immediate: true }
);

onMounted(() => {
  fetchMasters();
});
</script>

<template>
  <div class="row">
    <!-- Camera Scanner Modal Component -->
    <CameraScanner
      :show="showScanner"
      @close="showScanner = false"
      @captured="handleCapturedScan"
    />

    <div class="col-xl-12">
      <div class="card custom-card">
        <!-- Card Header with 2 Tabs -->
        <div class="card-header border-bottom-0 pb-0">
          <ul class="nav nav-tabs tab-style-2 nav-justified mb-0" role="tablist">
            <li class="nav-item">
              <a
                class="nav-link fs-15 py-3 text-center"
                :class="{
                  active: form.tipe_berkas === 'non_skripsi',
                  disabled: isEdit && form.tipe_berkas !== 'non_skripsi',
                }"
                href="javascript:void(0);"
                @click="switchTab('non_skripsi')"
              >
                <i class="ri-file-list-3-line me-2 align-middle fs-18"></i>
                <span class="fw-semibold">1. Non Skripsi</span>
              </a>
            </li>
            <li class="nav-item">
              <a
                class="nav-link fs-15 py-3 text-center"
                :class="{
                  active: form.tipe_berkas === 'skripsi',
                  disabled: isEdit && form.tipe_berkas !== 'skripsi',
                }"
                href="javascript:void(0);"
                @click="switchTab('skripsi')"
              >
                <i class="ri-book-read-line me-2 align-middle fs-18"></i>
                <span class="fw-semibold">2. Berkas Skripsi</span>
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body pt-4">
          <form @submit.prevent="handleSubmit">
            <!-- Alert Banner Sesuai Tab -->
            <div
              class="alert alert-primary-transparent d-flex align-items-center mb-4 py-2"
              v-if="form.tipe_berkas === 'non_skripsi'"
            >
              <i class="ri-information-line fs-18 me-2"></i>
              <span>
                <strong>Tab Non Skripsi:</strong> Unggah atau scan dokumen surat / berkas mahasiswa (contoh: <em>Surat Keterangan Aktif Mahasiswa</em>). File masuk otomatis ke folder kategori per prodi di Google Drive.
              </span>
            </div>
            <div
              class="alert alert-success-transparent d-flex align-items-center mb-4 py-2"
              v-else
            >
              <i class="ri-booklet-line fs-18 me-2"></i>
              <span>
                <strong>Tab Berkas Skripsi:</strong> Unggah atau scan 5 dokumen persyaratan sidang skripsi (seperti Form Pendaftaran Sidang Skripsi) beserta berkas tambahan kustom.
              </span>
            </div>

            <div class="row gy-3">
              <!-- Autocomplete Search Mahasiswa (NIM) -->
              <div class="col-xl-12">
                <label class="form-label fw-semibold">
                  Cari Mahasiswa (NIM / Nama) : <span class="text-danger">*</span>
                </label>
                <Multiselect
                  v-model="listMhs"
                  :options="optionsMhs"
                  :loading="isSearchingMhs"
                  :internal-search="false"
                  :clear-on-select="false"
                  :close-on-select="true"
                  placeholder="Ketik minimal 2 karakter NIM atau Nama Mahasiswa..."
                  label="nama"
                  track-by="nim"
                  @search-change="onSearchMhs"
                >
                  <template #noResult>
                    <span class="text-muted p-2">Mahasiswa tidak ditemukan.</span>
                  </template>
                  <template #singleLabel="{ option }">
                    <strong>{{ option.nim }}</strong> - {{ option.nama }} ({{
                      option.prodi || option.nama_prodi || "Prodi"
                    }})
                  </template>
                  <template #option="{ option }">
                    <div class="d-flex flex-column">
                      <span><strong>{{ option.nim }}</strong> - {{ option.nama }}</span>
                      <small class="text-muted">{{
                        option.prodi || option.nama_prodi || "Mahasiswa"
                      }}</small>
                    </div>
                  </template>
                </Multiselect>
              </div>

              <!-- Manual Fallback / Display Input NIM & Nama -->
              <div class="col-md-4">
                <label class="form-label fw-semibold">
                  NIM Mahasiswa : <span class="text-danger">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  v-model="form.nim"
                  placeholder="NIM Mahasiswa"
                  required
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <div class="col-md-5">
                <label class="form-label fw-semibold">
                  Nama Mahasiswa : <span class="text-danger">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_mhs }"
                  v-model="form.nama_mhs"
                  placeholder="Nama Lengkap Mahasiswa"
                  required
                />
                <div v-if="errors?.nama_mhs" class="invalid-feedback">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Jenis Kelamin :</label>
                <select class="form-select" v-model="form.jenis_kelamin">
                  <option value="L">Laki-Laki (L)</option>
                  <option value="P">Perempuan (P)</option>
                </select>
              </div>

              <!-- Program Studi -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">
                  Program Studi (Acuan Folder Drive) : <span class="text-danger">*</span>
                </label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id }"
                  v-model="form.prodi_id"
                  required
                >
                  <option value="" disabled>-- Pilih Program Studi --</option>
                  <option v-for="p in listProdi" :key="p.id" :value="Number(p.id)">
                    {{ p.nama }}
                  </option>
                </select>
                <div v-if="errors?.prodi_id" class="invalid-feedback">
                  {{ errors.prodi_id[0] }}
                </div>
              </div>

              <!-- Kategori Berkas (Wajib di Tab 1, Opsional / Default 'Dokumen Sidang Skripsi' di Tab 2) -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">
                  Kategori Berkas :
                  <span v-if="form.tipe_berkas === 'non_skripsi'" class="text-danger">*</span>
                  <small v-else class="text-muted fw-normal">(Opsional, default: Dokumen Sidang Skripsi)</small>
                </label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.kategori_berkas_id }"
                  v-model="form.kategori_berkas_id"
                  :required="form.tipe_berkas === 'non_skripsi'"
                >
                  <option value="" :disabled="form.tipe_berkas === 'non_skripsi'">
                    {{
                      form.tipe_berkas === "non_skripsi"
                        ? "-- Pilih Kategori Berkas --"
                        : "-- Otomatis: Dokumen Sidang Skripsi --"
                    }}
                  </option>
                  <option v-for="k in listKategori" :key="k.id" :value="Number(k.id)">
                    {{ k.nama_kategori }}
                  </option>
                </select>
                <div v-if="errors?.kategori_berkas_id" class="invalid-feedback">
                  {{ errors.kategori_berkas_id[0] }}
                </div>
                <small class="text-muted fs-11">
                  Jika folder dengan nama kategori ini sudah ada di Google Drive prodi terkait, berkas akan langsung masuk ke folder tersebut.
                </small>
              </div>

              <!-- Field Nama Dokumen Saat Mode Edit (Khusus Skripsi; Non Skripsi otomatis sama dengan Kategori) -->
              <div v-if="isEdit && form.tipe_berkas === 'skripsi'" class="col-xl-12">
                <label class="form-label fw-semibold">Nama Dokumen Skripsi :</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.nama_dokumen"
                  placeholder="Contoh: File Skripsi / Lembar Konsultasi Dospem 1 / File Foto 3x4"
                />
              </div>

              <!-- =========================================================== -->
              <!-- KONTEN TAB 1: NON SKRIPSI (atau Edit Single Berkas)          -->
              <!-- =========================================================== -->
              <div v-if="form.tipe_berkas === 'non_skripsi' || isEdit" class="col-xl-12 mt-4">
                <label class="form-label fw-semibold fs-14 mb-2">
                  Lampiran Berkas (Kamera Scan Realtime / Upload File) :
                  <span v-if="!isEdit" class="text-danger">*</span>
                </label>

                <div
                  v-if="!previewDocument && !selectedFileName"
                  class="p-4 border border-dashed rounded text-center bg-light"
                >
                  <div class="d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="avatar avatar-xl bg-primary-transparent rounded-circle mb-3">
                      <i class="ri-scan-2-line fs-28 text-primary"></i>
                    </div>
                    <h6 class="fw-bold mb-1">
                      Scan Berkas via Kamera Realtime (CamScanner)
                    </h6>
                    <p class="text-muted fs-12 mb-3 max-w-400">
                      Gunakan kamera realtime untuk memindai surat/dokumen dengan pengaturan kecerahan, kontras, dan efek Magic Color.
                    </p>

                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                      <button
                        type="button"
                        class="btn btn-primary btn-wave shadow-sm px-4"
                        @click="openScannerForSingle"
                      >
                        <i class="ri-camera-lens-fill me-1"></i> Buka Kamera Scanner
                      </button>

                      <label class="btn btn-outline-secondary btn-wave px-3 mb-0">
                        <i class="ri-upload-2-line me-1"></i> Upload File (Gambar/PDF)
                        <input
                          type="file"
                          accept="image/*,.pdf"
                          class="d-none"
                          @change="handleFileChange"
                        />
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Preview Area When Document Exists -->
                <div v-else class="card border border-success mb-0 bg-success-transparent">
                  <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-3">
                        <div v-if="previewDocument" class="scan-thumbnail-box">
                          <img :src="previewDocument" alt="Preview Dokumen" class="scan-thumbnail" />
                        </div>
                        <div v-else class="avatar avatar-lg bg-info-transparent text-info rounded">
                          <i class="ri-file-text-line fs-24"></i>
                        </div>
                        <div>
                          <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark fs-14">{{ selectedFileName }}</span>
                            <span class="badge bg-success-transparent">Siap Disimpan</span>
                          </div>
                          <small class="text-muted">
                            {{
                              form.file_base64
                                ? "Hasil Scan Kamera Realtime (CamScanner)"
                                : form.file
                                ? "File Upload Baru Siap Dikirim"
                                : "File Tersimpan Saat Ini"
                            }}
                          </small>
                        </div>
                      </div>

                      <div class="d-flex gap-2">
                        <button
                          type="button"
                          class="btn btn-sm btn-primary-light btn-wave"
                          @click="openScannerForSingle"
                        >
                          <i class="ri-camera-switch-line me-1"></i> Scan Baru
                        </button>
                        <label class="btn btn-sm btn-outline-secondary btn-wave mb-0">
                          <i class="ri-upload-line me-1"></i> Ganti File
                          <input
                            type="file"
                            accept="image/*,.pdf"
                            class="d-none"
                            @change="handleFileChange"
                          />
                        </label>
                        <button
                          v-if="form.file || form.file_base64"
                          type="button"
                          class="btn btn-sm btn-danger-light btn-wave"
                          @click="clearDocument"
                        >
                          <i class="ri-delete-bin-line me-1"></i> Hapus
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="errors?.file" class="text-danger fs-12 mt-1">
                  {{ errors.file[0] }}
                </div>
              </div>

              <!-- =========================================================== -->
              <!-- KONTEN TAB 2: BERKAS SKRIPSI (Contoh Form Sidang Skripsi)   -->
              <!-- =========================================================== -->
              <div v-else-if="form.tipe_berkas === 'skripsi' && !isEdit" class="col-xl-12 mt-4">
                <hr class="mt-0" />
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <div class="d-flex align-items-center">
                    <i class="ri-file-upload-line me-2 text-primary fs-18"></i>
                    <span class="fw-bold fs-15">Dokumen Berkas Skripsi (5 Dokumen Standar Sidang)</span>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-success"
                      @click="loadAllStandardDocs"
                    >
                      <i class="ri-list-check-2 me-1"></i> Muat 5 Standar
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary"
                      @click="addCustomRow"
                    >
                      <i class="ri-add-line me-1"></i> + Berkas Kustom
                    </button>

                    <div class="dropdown preset-dropdown-container position-relative">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        @click.prevent.stop="toggleDropdown"
                      >
                        <i class="ri-folder-add-line me-1"></i> + Tambah Berkas Cepat
                        <i class="ri-arrow-down-s-line ms-1"></i>
                      </button>
                      <ul
                        class="dropdown-menu dropdown-menu-end shadow"
                        :class="{ show: showDropdown }"
                        style="min-width: 330px;"
                        v-if="showDropdown"
                      >
                        <li class="dropdown-header text-uppercase fs-11 text-muted fw-bold">
                          Pilih Dokumen Standar Sidang
                        </li>
                        <li v-for="preset in standardSidangDocs" :key="preset.nama_dokumen">
                          <a
                            class="dropdown-item py-2"
                            href="javascript:void(0)"
                            @click="addPresetRow(preset)"
                          >
                            <div class="fw-medium text-dark">{{ preset.nama_dokumen }}</div>
                            <small class="text-muted d-block">{{ preset.hint }}</small>
                          </a>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                          <a
                            class="dropdown-item text-primary fw-medium"
                            href="javascript:void(0)"
                            @click="addCustomRow"
                          >
                            <i class="ri-add-circle-line me-1"></i> + Tambah Dokumen Kustom Lainnya
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- List Dokumen Skripsi -->
                <div
                  v-if="skripsiDocuments.length === 0"
                  class="text-muted small py-4 border rounded text-center bg-light"
                >
                  Belum ada daftar dokumen. Klik <strong>Muat 5 Standar</strong> atau <strong>+ Berkas Kustom</strong> di atas.
                </div>

                <div
                  v-for="(item, index) in skripsiDocuments"
                  :key="index"
                  class="border rounded p-3 mb-3 bg-white shadow-sm"
                >
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-primary-transparent text-primary fw-bold px-2 py-1 fs-12">
                        Dokumen #{{ index + 1 }}
                      </span>
                      <span class="fw-semibold fs-13 text-dark">
                        {{ item.nama_dokumen || "Dokumen Kustom" }}
                      </span>
                      <span
                        v-if="item.required"
                        class="badge bg-danger-transparent text-danger fw-semibold"
                      >
                        Wajib
                      </span>
                      <span
                        v-else
                        class="badge bg-secondary-transparent text-secondary"
                      >
                        Opsional
                      </span>
                    </div>

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger py-0 px-2"
                      @click="removeSkripsiRow(index)"
                      title="Hapus baris ini"
                    >
                      <i class="ri-close-line me-1"></i> Hapus
                    </button>
                  </div>

                  <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fs-11 text-muted mb-0">Nama Dokumen:</label>
                        <span
                          v-if="item.isPreset !== false"
                          class="badge bg-light text-muted fs-10 border"
                        >
                          <i class="ri-lock-line me-1"></i> Standar
                        </span>
                      </div>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        :class="{ 'bg-light text-muted': item.isPreset !== false }"
                        placeholder="Nama Dokumen (contoh: Surat Bebas Pustaka)"
                        v-model="item.nama_dokumen"
                        :disabled="item.isPreset !== false"
                        :readonly="item.isPreset !== false"
                      />
                    </div>

                    <div class="col-md-7">
                      <label class="form-label fs-11 text-muted mb-1">
                        Pilih Metode Input File:
                        <span v-if="item.hint" class="fst-italic">({{ item.hint }})</span>
                      </label>

                      <div class="d-flex gap-2 align-items-center flex-wrap">
                        <!-- Tombol Scan Kamera Realtime (CamScanner) -->
                        <button
                          type="button"
                          class="btn btn-sm btn-primary btn-wave"
                          @click="openScannerForRow(index)"
                        >
                          <i class="ri-camera-lens-line me-1"></i> Scan Kamera
                        </button>

                        <!-- Input File Biasa -->
                        <div class="flex-grow-1">
                          <input
                            type="file"
                            class="form-control form-control-sm"
                            :accept="item.accept || '*'"
                            @change="(e) => onSkripsiFileChange(e, index)"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Status & Preview File Baris Terpilih -->
                  <div
                    v-if="item.fileName || item.file_base64"
                    class="mt-2 p-2 bg-success-transparent border border-success-light rounded d-flex align-items-center justify-content-between"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <img
                        v-if="item.preview"
                        :src="item.preview"
                        alt="Preview"
                        class="row-scan-thumb"
                      />
                      <i v-else class="ri-checkbox-circle-fill text-success fs-18"></i>
                      <div class="fs-12 text-success fw-medium">
                        Siap diunggah: <strong>{{ item.fileName }}</strong>
                        <span v-if="item.file_base64" class="badge bg-primary ms-1">Hasil Scan Kamera</span>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="btn btn-xs btn-danger-light"
                      @click="clearSkripsiRowFile(index)"
                    >
                      <i class="ri-close-line"></i> Batal File
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-4 mt-3 border-top">
              <router-link to="/berkas" class="btn btn-secondary-light btn-wave">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
              </router-link>

              <button
                type="submit"
                class="btn btn-primary btn-wave shadow-sm px-4"
                :disabled="btnLoading"
              >
                <span
                  v-if="btnLoading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                ></span>
                <i v-else class="ri-save-line me-1"></i>
                {{
                  btnLoading
                    ? "Mengunggah ke Drive..."
                    : isEdit
                    ? "Update Berkas"
                    : "Simpan & Upload ke Drive"
                }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scan-thumbnail-box {
  width: 58px;
  height: 58px;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #c3e6cb;
  background-color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scan-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.row-scan-thumb {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  object-fit: cover;
  border: 1px solid #198754;
}

.max-w-400 {
  max-width: 460px;
}
</style>
