<script lang="ts" setup>
import { reactive, ref, watch, onMounted, nextTick } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import { apiGet, apiDelete, apiPost } from "../../services/api/request";
import { debounce } from "vuetify/lib/util/helpers.mjs";
import { toast } from "vue3-toastify";
import Swal from "sweetalert2";

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
  nama: "",
  nik: "",
  prodi: "",
  judul_skripsi: "",
  dospem1: "",
  dospem2: "",
  pembimbing1: "",
  pembimbing2: "",
  periode_sidang: new Date().toISOString().slice(0, 10),
};

const form = reactive({ ...defaultForm });

// List Dokumen Baru yang akan diunggah
interface NewDocItem {
  nama_dokumen: string;
  file: File | null;
  fileName?: string;
  required?: boolean;
  hint?: string;
  accept?: string;
  isPreset?: boolean;
}

const newDocuments = ref<NewDocItem[]>([]);
const existingDocuments = ref<any[]>([]);

// 6 Dokumen Standar Sidang Skripsi
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
    nama_dokumen: "Lembar Konsultasi Dospem 2 (Boleh tidak diisi)",
    required: false,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Opsional jika hanya memiliki 1 pembimbing (boleh tidak diisi)",
  },
  {
    nama_dokumen: "FILE FOTO 3X4 (BERDASI, BACKGROUND MERAH, TANPA AKSESORIS)",
    required: true,
    accept: ".jpg,.jpeg,.png",
    hint: "Pasfoto resmi 3x4 berdasi latar belakang merah tanpa aksesoris",
  },
  {
    nama_dokumen: "Scan Nota Pembimbing (Bukti ACC Skripsi)",
    required: true,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Bukti persetujuan/ACC ujian skripsi dari pembimbing",
  },
  {
    nama_dokumen: "Scan Ijazah Asli",
    required: false,
    accept: ".pdf,.jpg,.jpeg,.png",
    hint: "Scan ijazah asli (opsional / boleh tidak diisi)",
  },
];

const showDropdown = ref(false);

function toggleDropdown() {
  showDropdown.value = !showDropdown.value;
}

function addPresetRow(preset: typeof standardSidangDocs[0]) {
  const existing = newDocuments.value.find(
    (d) => d.nama_dokumen.toLowerCase().trim() === preset.nama_dokumen.toLowerCase().trim()
  );
  if (existing) {
    toast.info(`Dokumen "${preset.nama_dokumen}" sudah ada di daftar.`);
  } else {
    newDocuments.value.push({
      nama_dokumen: preset.nama_dokumen,
      file: null,
      fileName: "",
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
  newDocuments.value.push({
    nama_dokumen: "",
    file: null,
    fileName: "",
    required: false,
    hint: "Dokumen persyaratan tambahan",
    accept: "*",
    isPreset: false,
  });
  showDropdown.value = false;
}

function addDocumentRow(presetName = "") {
  if (!presetName) {
    addCustomRow();
    return;
  }
  const matched = standardSidangDocs.find(
    (s) => s.nama_dokumen.toLowerCase().trim() === presetName.toLowerCase().trim()
  );
  if (matched) {
    addPresetRow(matched);
  } else {
    newDocuments.value.push({
      nama_dokumen: presetName,
      file: null,
      fileName: "",
      required: false,
      accept: "*",
      isPreset: false,
    });
  }
}

function removeDocumentRow(index: number) {
  newDocuments.value.splice(index, 1);
}

function onFileChange(e: Event, index: number) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    newDocuments.value[index].file = file;
    newDocuments.value[index].fileName = file.name;
    if (!newDocuments.value[index].nama_dokumen) {
      newDocuments.value[index].nama_dokumen = file.name.replace(/\.[^/.]+$/, "");
    }
  }
}

// Quick Add Dokumen saat Edit
const uploadingExtraDoc = ref(false);
const quickDocName = ref("");
const quickDocFile = ref<File | null>(null);
const quickDocFileInput = ref<HTMLInputElement | null>(null);

function onQuickFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    quickDocFile.value = target.files[0];
    if (!quickDocName.value) {
      quickDocName.value = target.files[0].name.replace(/\.[^/.]+$/, "");
    }
  }
}

async function uploadQuickDoc() {
  if (!form.id) return;
  if (!quickDocFile.value) {
    toast.warning("Pilih file dokumen terlebih dahulu");
    return;
  }
  if (!quickDocName.value.trim()) {
    toast.warning("Nama dokumen tidak boleh kosong");
    return;
  }

  try {
    uploadingExtraDoc.value = true;
    const fd = new FormData();
    fd.append("file", quickDocFile.value);
    fd.append("nama_dokumen", quickDocName.value);

    const response = await apiPost(`/daftar-sidang-skripsi/${form.id}/upload-dokumen`, fd);
    if (response.success && response.data?.status) {
      toast.success("Dokumen berhasil diunggah");
      existingDocuments.value.push(response.data.data);
      quickDocName.value = "";
      quickDocFile.value = null;
      if (quickDocFileInput.value) quickDocFileInput.value.value = "";
    } else {
      toast.error(response.data?.message || "Gagal mengunggah dokumen");
    }
  } catch (error) {
    toast.error("Terjadi kesalahan saat mengunggah dokumen");
  } finally {
    uploadingExtraDoc.value = false;
  }
}

async function deleteExistingDoc(doc: any, index: number) {
  const res = await Swal.fire({
    title: "Hapus Dokumen?",
    text: `Dokumen "${doc.nama_dokumen}" akan dihapus permanen.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
  });

  if (res.isConfirmed) {
    try {
      const response = await apiDelete(`/daftar-sidang-skripsi/dokumen/${doc.id}`);
      if (response.success || response.data?.status) {
        toast.success("Dokumen berhasil dihapus");
        existingDocuments.value.splice(index, 1);
      } else {
        toast.error("Gagal menghapus dokumen");
      }
    } catch (error) {
      toast.error("Terjadi kesalahan");
    }
  }
}

// Autocomplete Mahasiswa
const disableListMhsWatcher = ref(false);
const disableListDosenWatcher = ref(false);
const isLoadingData = ref(false);
const options = ref<any[]>([]);
const loading = ref(false);
const listMhs = ref<any>(null);
const listProdi = ref<any[]>([]);

// Autocomplete Dosen Pembimbing 1 & 2
const listDosen1 = ref<any>(null);
const optionsDosen1 = ref<any[]>([]);
const loadingDosen1 = ref(false);

const listDosen2 = ref<any>(null);
const optionsDosen2 = ref<any[]>([]);
const loadingDosen2 = ref(false);

const getDosen1 = debounce(async (params: string) => {
  const keyword = params.trim();
  if (!keyword && !props.isEdit) {
    optionsDosen1.value = [];
    return;
  }

  try {
    loadingDosen1.value = true;
    const response = await apiGet(`/get-dosen`, { search: keyword });
    if (response.success) {
      optionsDosen1.value = response.data?.data || [];
    }
  } catch (error) {
  } finally {
    loadingDosen1.value = false;
  }
}, 300);

const getDosen2 = debounce(async (params: string) => {
  const keyword = params.trim();
  if (!keyword && !props.isEdit) {
    optionsDosen2.value = [];
    return;
  }

  try {
    loadingDosen2.value = true;
    const response = await apiGet(`/get-dosen`, { search: keyword });
    if (response.success) {
      optionsDosen2.value = response.data?.data || [];
    }
  } catch (error) {
  } finally {
    loadingDosen2.value = false;
  }
}, 300);

function toTitleCase(text: string): string {
  return text
    .toLowerCase()
    .split(/\s+/)
    .map((w) => (w ? w.charAt(0).toUpperCase() + w.slice(1) : ""))
    .join(" ");
}

function formatNamaCapitalWithGelarString(name: string): string {
  const str = (name || "").trim().replace(/^[,\s]+|[,\s]+$/g, "");
  if (!str) return "";

  let gelarDepan = "";
  let namaTanpaGelarDepan = str;

  const prefixPattern = /^((?:(?:Prof|Drs|Dra|Dr|Ir|KH|K\.H|Ust|Ustadz|Gus)\.?\s*|(?:H|Hj)\.\s*)+)(.*)$/i;
  const matches = str.match(prefixPattern);
  if (matches && matches[2] && matches[2].trim()) {
    gelarDepan = matches[1].trim();
    namaTanpaGelarDepan = matches[2].trim();
  }

  let namaUtama = "";
  let gelarBelakang = "";

  if (namaTanpaGelarDepan.includes(",")) {
    const idx = namaTanpaGelarDepan.indexOf(",");
    namaUtama = namaTanpaGelarDepan.slice(0, idx).trim();
    gelarBelakang = namaTanpaGelarDepan.slice(idx + 1).replace(/^[,\s]+|[,\s]+$/g, "").trim();
  } else {
    const degreePattern = /\b((?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?)(?:[\s,]+(?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?))*)$/i;
    const dMatches = namaTanpaGelarDepan.match(degreePattern);
    if (dMatches && dMatches.index !== undefined) {
      namaUtama = namaTanpaGelarDepan.slice(0, dMatches.index).trim();
      gelarBelakang = namaTanpaGelarDepan.slice(dMatches.index).replace(/^[,\s]+|[,\s]+$/g, "").trim();
    } else {
      namaUtama = namaTanpaGelarDepan;
      gelarBelakang = "";
    }
  }

  let result = toTitleCase(namaUtama);

  if (gelarDepan) {
    let cleanDepan = gelarDepan.replace(/\s+/g, " ");
    cleanDepan = cleanDepan.replace(/\b(prof|dr|drs|dra|ir|kh|ust|gus)\b/gi, (m) => {
      const lower = m.toLowerCase();
      const map: Record<string, string> = {
        prof: "Prof",
        dr: "Dr",
        drs: "Drs",
        dra: "Dra",
        ir: "Ir",
        kh: "K.H",
        ust: "Ust",
        gus: "Gus",
      };
      return map[lower] || (lower.charAt(0).toUpperCase() + lower.slice(1));
    });
    result = `${cleanDepan} ${result}`;
  }

  if (gelarBelakang) {
    result = `${result.replace(/^[,\s]+|[,\s]+$/g, "")}, ${gelarBelakang}`;
  }

  return result.trim();
}

function formatNamaDosen(dosen: any) {
  if (!dosen) return "";

  if (typeof dosen === "string") {
    return formatNamaCapitalWithGelarString(dosen);
  }

  const gelarDepan = (dosen.gelar_depan || "").trim();
  const gelarBelakang = (dosen.gelar_belakang || "").trim();
  const rawNama = (dosen.nama_asli || dosen.nama || "").trim();

  if (dosen.nama_asli && (gelarDepan || gelarBelakang)) {
    let res = toTitleCase(dosen.nama_asli.trim());
    if (gelarDepan) {
      res = `${gelarDepan} ${res}`;
    }
    if (gelarBelakang) {
      const cleanBelakang = gelarBelakang.replace(/^[,\s]+|[,\s]+$/g, "");
      res = `${res}, ${cleanBelakang}`;
    }
    return res.trim();
  }

  if (dosen.nama_gelar) {
    return formatNamaCapitalWithGelarString(dosen.nama_gelar);
  }

  return formatNamaCapitalWithGelarString(rawNama);
}

function customNameDosen(params: any) {
  const nameWithTitle = formatNamaDosen(params);
  return params.nidn ? `${nameWithTitle} - ${params.nidn}` : nameWithTitle;
}

watch(listDosen1, (val) => {
  if (disableListDosenWatcher.value) return;
  if (val) {
    const formatted = formatNamaDosen(val);
    form.dospem1 = formatted;
    form.pembimbing1 = formatted;
  } else {
    form.dospem1 = "";
    form.pembimbing1 = "";
  }
});

watch(listDosen2, (val) => {
  if (disableListDosenWatcher.value) return;
  if (val) {
    const formatted = formatNamaDosen(val);
    form.dospem2 = formatted;
    form.pembimbing2 = formatted;
  } else {
    form.dospem2 = "";
    form.pembimbing2 = "";
  }
});

async function getProdi() {
  try {
    const response = await apiGet(`/get-prodi`);
    if (response.success) {
      const data = response.data?.data;
      listProdi.value = Array.isArray(data) ? data : [data];
      if (listProdi.value.length === 1 && !props.isEdit) {
        const singleProdi = listProdi.value[0];
        form.prodi_id = Number(singleProdi.id);
        form.prodi = singleProdi.nama;
      }
    }
  } catch (error) {}
}

function customNameMhs(m: any) {
  if (!m) return "";
  const nama = m.nama || m.nama_mhs || "";
  const prodi =
    (typeof m.prodi === "object" && m.prodi !== null
      ? m.prodi?.nama || m.prodi?.alias
      : m.prodi) ||
    m.prodi_mhs ||
    m.nama_prodi ||
    m.alias_prodi ||
    "";

  return prodi ? `${nama} - ${prodi}` : (nama || m.nim || "");
}

const getMhs = debounce(async (keyword: string) => {
  if (!keyword || keyword.length < 2) {
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

watch(
  () => listMhs.value,
  async (val) => {
    if (disableListMhsWatcher.value) return;

    if (val) {
      form.nama = val.nama || val.nama_mhs || "";
      form.nim = val.nim || "";
      if (val.nik) form.nik = val.nik;
      if (val.prodi?.nama) {
        form.prodi = val.prodi.nama;
        form.prodi_id = Number(val.prodi.id);
      } else if (typeof val.prodi === "string" && val.prodi) {
        form.prodi = val.prodi;
      } else if (val.prodi_mhs) {
        form.prodi = val.prodi_mhs;
      } else if (val.nama_prodi) {
        form.prodi = val.nama_prodi;
      }

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

      // Ambil judul skripsi dari pengajuan_judul jika tersedia
      const judulFound = val.judul_skripsi || val.judul || val.judul_proposal_skripsi || "";
      if (judulFound) {
        form.judul_skripsi = judulFound;
      }

      // Jika belum terisi dari payload list, query langsung ke API pengajuan-judul berdasarkan NIM
      if (val.nim) {
        try {
          const resJudul = await apiGet(`/pengajuan-judul/by-nim/${encodeURIComponent(val.nim)}`);
          if (resJudul.success && resJudul.data?.data) {
            const dataJudul = resJudul.data.data;
            if (!form.judul_skripsi && dataJudul.judul_proposal_skripsi) {
              form.judul_skripsi = dataJudul.judul_proposal_skripsi;
            }
            if (dataJudul.dospem1 || dataJudul.pembimbing1) {
              const d1 = dataJudul.dospem1 || dataJudul.pembimbing1;
              form.dospem1 = d1;
              form.pembimbing1 = d1;
              listDosen1.value = { nama: d1, nama_gelar: d1 };
              optionsDosen1.value = [listDosen1.value];
            }
            if (dataJudul.dospem2 || dataJudul.pembimbing2) {
              const d2 = dataJudul.dospem2 || dataJudul.pembimbing2;
              form.dospem2 = d2;
              form.pembimbing2 = d2;
              listDosen2.value = { nama: d2, nama_gelar: d2 };
              optionsDosen2.value = [listDosen2.value];
            }
          }
        } catch (e) {
          // Abaikan jika belum ada di tabel pengajuan_judul
        }
      }
    } else {
      form.nama = "";
      form.nim = "";
      form.nik = "";
      form.dospem1 = "";
      form.pembimbing1 = "";
      form.dospem2 = "";
      form.pembimbing2 = "";
      listDosen1.value = null;
      listDosen2.value = null;
    }
  }
);

watch(
  () => form.prodi_id,
  (val) => {
    if (val && listProdi.value.length > 0) {
      const found = listProdi.value.find((p) => Number(p.id) === Number(val));
      if (found) {
        form.prodi = found.nama;
      }
    }
  }
);

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

    await new Promise((resolve) => setTimeout(resolve, 200));
    Object.assign(form, val);

    form.id = val.id ?? "";
    form.prodi_id = val.prodi_id ? Number(val.prodi_id) : 0;
    form.nim = val.nim ?? "";
    form.nama = val.nama ?? "";
    form.nik = val.nik ?? "";
    form.prodi = val.prodi ?? "";
    form.judul_skripsi = val.judul_skripsi ?? "";
    form.periode_sidang = val.periode_sidang
      ? val.periode_sidang.slice(0, 10)
      : new Date().toISOString().slice(0, 10);

    const initialD1 = val.dospem1 || val.pembimbing1 || "";
    form.dospem1 = initialD1;
    form.pembimbing1 = initialD1;
    if (initialD1) {
      listDosen1.value = {
        nama: initialD1,
        nama_gelar: initialD1,
      };
      optionsDosen1.value = [listDosen1.value];
    } else {
      listDosen1.value = null;
    }

    const initialD2 = val.dospem2 || val.pembimbing2 || "";
    form.dospem2 = initialD2;
    form.pembimbing2 = initialD2;
    if (initialD2) {
      listDosen2.value = {
        nama: initialD2,
        nama_gelar: initialD2,
      };
      optionsDosen2.value = [listDosen2.value];
    } else {
      listDosen2.value = null;
    }

    if (val.dokumen && Array.isArray(val.dokumen)) {
      existingDocuments.value = [...val.dokumen];
    }

    if (form.nim) {
      listMhs.value = {
        nim: form.nim,
        nama: form.nama,
        prodi: form.prodi,
        id: form.nim,
      };
      options.value = [listMhs.value];
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
  if (!props.isEdit && newDocuments.value.length === 0) {
    // Tampilkan 5 dokumen standar sidang skripsi secara default
    newDocuments.value = standardSidangDocs.map((doc) => ({
      nama_dokumen: doc.nama_dokumen,
      file: null,
      fileName: "",
      required: doc.required,
      hint: doc.hint,
      accept: doc.accept,
      isPreset: true,
    }));
  }

  // Tutup dropdown saat klik di luar
  document.addEventListener("click", (e) => {
    const target = e.target as HTMLElement;
    if (!target.closest(".preset-dropdown-container")) {
      showDropdown.value = false;
    }
  });
});

const emit = defineEmits(["submit"]);

function submitForm() {
  const formData = new FormData();
  formData.append("nama", form.nama);
  formData.append("nim", form.nim);
  formData.append("nik", form.nik || "");
  formData.append("prodi", form.prodi);
  formData.append("prodi_id", String(form.prodi_id || 0));
  formData.append("judul_skripsi", form.judul_skripsi);
  formData.append("periode_sidang", form.periode_sidang);
  formData.append("dospem1", form.dospem1 || form.pembimbing1 || "");
  formData.append("pembimbing1", form.dospem1 || form.pembimbing1 || "");
  formData.append("dospem2", form.dospem2 || form.pembimbing2 || "");
  formData.append("pembimbing2", form.dospem2 || form.pembimbing2 || "");

  if (form.id) {
    formData.append("id", String(form.id));
  }

  // Tambahkan dokumen baru yang diunggah
  let validDocIndex = 0;
  newDocuments.value.forEach((item) => {
    if (item.file) {
      formData.append(`dokumen_files[]`, item.file);
      formData.append(`dokumen_names[]`, item.nama_dokumen || item.file.name);
      validDocIndex++;
    }
  });

  emit("submit", { formData, rawForm: form });
}
</script>

<template>
  <div class="row">
    <div class="col-xl-12">
      <form @submit.prevent="submitForm">
        <div class="card custom-card">
          <div class="card-header justify-content-between">
            <div class="card-title">
              {{ isEdit ? "Edit" : "Tambah" }} Pendaftaran Sidang Skripsi
            </div>
            <router-link
              to="/daftar-sidang-skripsi"
              class="btn btn-sm btn-outline-secondary"
            >
              <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar
            </router-link>
          </div>

          <div class="card-body">
            <div class="row gy-3">
              <!-- Program Studi Unit -->
              <div class="col-xl-12">
                <label class="form-label font-weight-bold">Program Studi:</label>
                <input type="hidden" v-if="isEdit" v-model="form.id" />
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id || errors?.prodi }"
                  v-model="form.prodi_id"
                >
                  <option :value="0">-- Pilih Program Studi --</option>
                  <option
                    v-for="prodi in listProdi"
                    :key="prodi.id"
                    :value="Number(prodi.id)"
                  >
                    {{ prodi.nama }} ({{ prodi.alias }})
                  </option>
                </select>
                <div v-if="errors?.prodi_id || errors?.prodi" class="invalid-feedback">
                  {{ (errors?.prodi_id && errors.prodi_id[0]) || (errors?.prodi && errors.prodi[0]) }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0 d-flex align-items-center">
                <i class="ri-user-search-line me-2 text-primary"></i> Data Mahasiswa Peserta Sidang
              </div>

              <!-- Search Autocomplete Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">Cari Mahasiswa (NIM / Nama):</label>
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
                  placeholder="Ketik NIM atau Nama untuk mencari..."
                >
                  <template #noOptions>
                    <span>Ketik minimal 2 huruf untuk mencari</span>
                  </template>
                  <template #noResult>
                    <span>Mahasiswa tidak ditemukan</span>
                  </template>
                </Multiselect>
              </div>

              <!-- NIM -->
              <div class="col-xl-6">
                <label class="form-label">NIM Mahasiswa:</label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  v-model="form.nim"
                  placeholder="Nomor Induk Mahasiswa"
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <!-- Nama Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">Nama Lengkap Mahasiswa:</label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama }"
                  v-model="form.nama"
                  placeholder="Nama Lengkap Mahasiswa"
                />
                <div v-if="errors?.nama" class="invalid-feedback">
                  {{ errors.nama[0] }}
                </div>
              </div>

              <!-- NIK Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">NIK (Nomor Induk Kependudukan):</label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nik }"
                  v-model="form.nik"
                  placeholder="16 digit NIK Mahasiswa"
                  maxlength="20"
                />
                <div v-if="errors?.nik" class="invalid-feedback">
                  {{ errors.nik[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0 d-flex align-items-center">
                <i class="ri-book-read-line me-2 text-primary"></i> Data Skripsi & Jadwal Sidang
              </div>

              <!-- Judul Skripsi -->
              <div class="col-xl-12">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label font-weight-bold mb-0">Judul Skripsi:</label>
                  <span v-if="form.judul_skripsi" class="badge bg-success-transparent text-success small">
                    <i class="ri-check-line me-1"></i> Terisi otomatis dari Pengajuan Judul
                  </span>
                </div>
                <textarea
                  class="form-control"
                  :class="{ 'is-invalid': errors?.judul_skripsi }"
                  v-model="form.judul_skripsi"
                  rows="3"
                  placeholder="Masukkan judul skripsi lengkap yang akan diujikan..."
                ></textarea>
                <div v-if="errors?.judul_skripsi" class="invalid-feedback">
                  {{ errors.judul_skripsi[0] }}
                </div>
              </div>

              <!-- Periode Sidang -->
              <div class="col-xl-6">
                <label class="form-label font-weight-bold">Periode / Tanggal Sidang:</label>
                <input
                  type="date"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.periode_sidang }"
                  v-model="form.periode_sidang"
                />
                <div v-if="errors?.periode_sidang" class="invalid-feedback">
                  {{ errors.periode_sidang[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0 d-flex align-items-center">
                <i class="ri-user-star-line me-2 text-primary"></i> Data Dosen Pembimbing Skripsi
              </div>

              <!-- Dosen Pembimbing 1 -->
              <div class="col-xl-6">
                <label class="form-label font-weight-bold">
                  Cari Dosen Pembimbing 1:
                  <span class="text-danger">*</span>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="optionsDosen1"
                  v-model="listDosen1"
                  :internal-search="false"
                  @search-change="getDosen1"
                  label="nama"
                  track-by="nama"
                  :searchable="true"
                  :loading="loadingDosen1"
                  :custom-label="customNameDosen"
                  placeholder="Ketik nama atau NIDN dosen..."
                >
                  <template #noOptions>
                    <span>Ketik untuk mencari dosen</span>
                  </template>
                  <template #noResult>
                    <span>Dosen tidak ditemukan</span>
                  </template>
                </Multiselect>
              </div>

              <div class="col-xl-6">
                <label class="form-label font-weight-bold">Nama Dosen Pembimbing 1:</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.dospem1"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.dospem1 || errors?.pembimbing1 }"
                  placeholder="Nama dosen pembimbing 1 (otomatis terisi)"
                  readonly
                />
                <div v-if="errors?.dospem1 || errors?.pembimbing1" class="invalid-feedback">
                  {{ (errors?.dospem1 && errors.dospem1[0]) || (errors?.pembimbing1 && errors.pembimbing1[0]) }}
                </div>
              </div>

              <!-- Dosen Pembimbing 2 -->
              <div class="col-xl-6">
                <label class="form-label font-weight-bold">
                  Cari Dosen Pembimbing 2:
                  <small class="text-muted fw-normal">(Opsional jika hanya 1 pembimbing)</small>
                </label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <Multiselect
                  v-else
                  :options="optionsDosen2"
                  v-model="listDosen2"
                  :internal-search="false"
                  @search-change="getDosen2"
                  label="nama"
                  track-by="nama"
                  :searchable="true"
                  :loading="loadingDosen2"
                  :custom-label="customNameDosen"
                  placeholder="Ketik nama atau NIDN dosen..."
                >
                  <template #noOptions>
                    <span>Ketik untuk mencari dosen</span>
                  </template>
                  <template #noResult>
                    <span>Dosen tidak ditemukan</span>
                  </template>
                </Multiselect>
              </div>

              <div class="col-xl-6">
                <label class="form-label font-weight-bold">Nama Dosen Pembimbing 2:</label>
                <div v-if="isLoadingData" class="skeleton-input"></div>
                <input
                  v-else
                  type="text"
                  v-model="form.dospem2"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.dospem2 || errors?.pembimbing2 }"
                  placeholder="Nama dosen pembimbing 2 (otomatis terisi)"
                  readonly
                />
                <div v-if="errors?.dospem2 || errors?.pembimbing2" class="invalid-feedback">
                  {{ (errors?.dospem2 && errors.dospem2[0]) || (errors?.pembimbing2 && errors.pembimbing2[0]) }}
                </div>
              </div>

              <hr />
              <!-- Bagian Dokumen Persyaratan Sidang -->
              <div class="card-title mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                  <i class="ri-file-upload-line me-2 text-primary fs-18"></i>
                  <span class="fw-bold">Dokumen Berkas Sidang Skripsi (6 Dokumen Standar)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
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
                      <i class="ri-folder-add-line me-1"></i> + Tambah Berkas Cepat <i class="ri-arrow-down-s-line ms-1"></i>
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
                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="addPresetRow(preset)">
                          <div class="fw-medium text-dark">{{ preset.nama_dokumen }}</div>
                          <small class="text-muted d-block">{{ preset.hint }}</small>
                        </a>
                      </li>
                      <li><hr class="dropdown-divider" /></li>
                      <li>
                        <a class="dropdown-item text-primary fw-medium" href="javascript:void(0)" @click="addCustomRow">
                          <i class="ri-add-circle-line me-1"></i> + Tambah Dokumen Kustom Lainnya
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Daftar Dokumen yang Sudah Ada (Mode Edit) -->
              <div v-if="isEdit && existingDocuments.length > 0" class="col-xl-12">
                <div class="table-responsive border rounded p-2 bg-light">
                  <div class="fw-semibold text-muted mb-2 px-1">Dokumen Tersimpan Saat Ini:</div>
                  <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Dokumen</th>
                        <th style="width: 130px;">Status Upload</th>
                        <th style="width: 140px;">Drive Link</th>
                        <th style="width: 80px;" class="text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(doc, idx) in existingDocuments" :key="doc.id">
                        <td>{{ idx + 1 }}</td>
                        <td class="fw-medium">{{ doc.nama_dokumen }}</td>
                        <td>
                          <span
                            class="badge"
                            :class="{
                              'bg-success': doc.status === 'uploaded' || doc.status === 'uplouded',
                              'bg-warning text-dark': doc.status === 'pending',
                              'bg-danger': doc.status === 'failed'
                            }"
                          >
                            {{ doc.status }}
                          </span>
                        </td>
                        <td>
                          <a
                            v-if="doc.drive_link"
                            :href="doc.drive_link"
                            target="_blank"
                            class="btn btn-sm btn-outline-info d-inline-flex align-items-center py-1 px-2"
                          >
                            <i class="ri-external-link-line me-1"></i> Buka Drive
                          </a>
                          <span v-else class="text-muted small">Sedang antre</span>
                        </td>
                        <td class="text-center">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger py-1 px-2"
                            @click="deleteExistingDoc(doc, idx)"
                            title="Hapus Dokumen"
                          >
                            <i class="ri-delete-bin-line"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Upload Langsung Dokumen Baru pada Mode Edit -->
              <div v-if="isEdit" class="col-xl-12">
                <div class="card border border-dashed p-3 mb-0">
                  <div class="fw-semibold text-primary mb-2">
                    <i class="ri-upload-cloud-line me-1"></i> Unggah Dokumen Tambahan Langsung:
                  </div>
                  <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Nama Dokumen (Contoh: Bebas Pustaka)"
                        v-model="quickDocName"
                      />
                    </div>
                    <div class="col-md-5">
                      <input
                        ref="quickDocFileInput"
                        type="file"
                        class="form-control form-control-sm"
                        @change="onQuickFileChange"
                      />
                    </div>
                    <div class="col-md-2">
                      <button
                        type="button"
                        class="btn btn-sm btn-success w-100"
                        :disabled="uploadingExtraDoc"
                        @click="uploadQuickDoc"
                      >
                        <span v-if="uploadingExtraDoc" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-upload-2-line me-1"></i> Unggah
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- List Dokumen Baru yang akan Diunggah saat Submit -->
              <div class="col-xl-12">
                <div class="fw-semibold text-muted mb-2">
                  {{ isEdit ? "Tambah File Berkas Baru Sebelum Simpan:" : "Pilih File Dokumen Persyaratan Sidang:" }}
                </div>
                <div v-if="newDocuments.length === 0" class="text-muted small italic py-3 border rounded text-center bg-light">
                  Belum ada dokumen yang ditambahkan. Klik tombol <strong>+ Tambah Berkas Cepat</strong> di atas untuk memunculkan berkas persyaratan.
                </div>
                <div
                  v-for="(item, index) in newDocuments"
                  :key="index"
                  class="border rounded p-3 mb-3 bg-white shadow-sm"
                >
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-primary-transparent text-primary fw-bold px-2 py-1 fs-12">
                        Dokumen #{{ index + 1 }}
                      </span>
                      <span class="fw-semibold fs-13 text-dark">{{ item.nama_dokumen || 'Dokumen Kustom' }}</span>
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
                        Boleh tidak diisi
                      </span>
                    </div>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger py-0 px-2"
                      @click="removeDocumentRow(index)"
                      title="Hapus baris ini"
                    >
                      <i class="ri-close-line me-1"></i> Hapus
                    </button>
                  </div>

                  <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fs-11 text-muted mb-0">Nama Dokumen:</label>
                        <span v-if="item.isPreset !== false" class="badge bg-light text-muted fs-10 border">
                          <i class="ri-lock-line me-1"></i> Standar
                        </span>
                      </div>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        :class="{ 'bg-light text-muted': item.isPreset !== false }"
                        placeholder="Nama Dokumen"
                        v-model="item.nama_dokumen"
                        :disabled="item.isPreset !== false"
                        :readonly="item.isPreset !== false"
                      />
                    </div>
                    <div class="col-md-7">
                      <label class="form-label fs-11 text-muted mb-1">
                        Pilih File: <span v-if="item.hint" class="text-muted fst-italic">({{ item.hint }})</span>
                      </label>
                      <input
                        type="file"
                        class="form-control form-control-sm"
                        :accept="item.accept || '*'"
                        @change="(e) => onFileChange(e, index)"
                      />
                    </div>
                  </div>

                  <div v-if="item.fileName" class="mt-2 text-success small d-flex align-items-center">
                    <i class="ri-checkbox-circle-fill me-1"></i>
                    File terpilih: <strong class="ms-1">{{ item.fileName }}</strong>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="card-footer d-flex justify-content-end gap-2">
            <router-link to="/daftar-sidang-skripsi" class="btn btn-light btn-wave">
              Batal
            </router-link>
            <button
              type="submit"
              class="btn btn-primary-light btn-wave"
              :disabled="btnLoading"
            >
              <span
                v-if="btnLoading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              <i v-else class="ri-save-line me-1"></i>
              {{
                btnLoading
                  ? isEdit
                    ? "Mengupdate..."
                    : "Menyimpan..."
                  : isEdit
                  ? "Update Pendaftaran"
                  : "Simpan Pendaftaran"
              }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.skeleton-input {
  height: 38px;
  background: #e9ecef;
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

