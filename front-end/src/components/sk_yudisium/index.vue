<script lang="ts" setup>
import { reactive, ref, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import { apiGet } from "../../services/api/request";
import { debounce } from "vuetify/lib/util/helpers.mjs";
import * as XLSX from "xlsx";
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

const emit = defineEmits(["submit"]);

const listProdi = ref<any[]>([]);

/* ==========================================================================
   1. MODE IMPORT EXCEL (UNTUK CREATE / TAMBAH DATA BARU)
   ========================================================================== */
const excelForm = reactive({
  prodi_id: 0,
  tanggal: new Date().toISOString().slice(0, 10),
  no_surat: "",
  petanda_tangan: "tidak",
});

const selectedExcelFile = ref<File | null>(null);
const excelFileInput = ref<HTMLInputElement | null>(null);
const excelPreviewData = ref<any[]>([]);
const isDragging = ref(false);

function triggerFileInput() {
  excelFileInput.value?.click();
}

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    processFile(file);
  }
}

function onFileDrop(e: DragEvent) {
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) {
    processFile(file);
  }
}

function processFile(file: File) {
  const validExtensions = [".xlsx", ".xls", ".csv"];
  const fileName = file.name.toLowerCase();
  const isValid = validExtensions.some((ext) => fileName.endsWith(ext));

  if (!isValid) {
    Swal.fire({
      icon: "error",
      title: "Format File Tidak Didukung",
      text: "Silakan pilih file Excel (.xlsx, .xls) atau CSV (.csv).",
    });
    return;
  }

  selectedExcelFile.value = file;
  readExcelPreview(file);
}

function removeSelectedFile() {
  selectedExcelFile.value = null;
  excelPreviewData.value = [];
  if (excelFileInput.value) {
    excelFileInput.value.value = "";
  }
}

function readExcelPreview(file: File) {
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target?.result as ArrayBuffer);
      const workbook = XLSX.read(data, { type: "array" });
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      const json: any[] = XLSX.utils.sheet_to_json(worksheet);

      excelPreviewData.value = json.map((row: any) => {
        return {
          nim: row["nim"] || row["NIM"] || row["nomor_induk_mahasiswa"] || "",
          nama:
            row["nama"] ||
            row["NAMA"] ||
            row["nama_mhs"] ||
            row["nama_mahasiswa"] ||
            row["NAMA MAHASISWA"] ||
            "",
          prodi:
            row["prodi"] ||
            row["PRODI"] ||
            row["program_studi"] ||
            row["PROGRAM STUDI"] ||
            "",
          nilai_skripsi:
            row["nilai_skripsi"] ||
            row["NILAI_SKRIPSI"] ||
            row["nilai"] ||
            row["NILAI"] ||
            row["skripsi"] ||
            row["Nilai skripsi"] ||
            "",
          jenis_kelamin:
            row["jenis_kelamin"] ||
            row["JENIS_KELAMIN"] ||
            row["jk"] ||
            row["JK"] ||
            "L",
        };
      });
    } catch (err) {
      excelPreviewData.value = [];
    }
  };
  reader.readAsArrayBuffer(file);
}

function downloadTemplate() {
  const templateData = [
    ["NIM", "NAMA", "PROGRAM_STUDI", "NILAI_SKRIPSI", "JENIS_KELAMIN"],
    [
      "2015.85.29.0109",
      "AHMAD FAWAID",
      "EKONOMI SYARIAH",
      "83,75 / A-",
      "L",
    ],
    [
      "2018.85.29.0033",
      "MUHAMMAD ABDUH",
      "EKONOMI SYARIAH",
      "80 / B+",
      "L",
    ],
    [
      "2021.85.29.0139",
      "CHAIRUL FALAH",
      "EKONOMI SYARIAH",
      "80 / B+",
      "L",
    ],
    [
      "2019.85.29.0045",
      "ACHMAD ABI SYUJA",
      "EKONOMI SYARIAH",
      "90 / A-",
      "L",
    ],
  ];

  const ws = XLSX.utils.aoa_to_sheet(templateData);
  ws["!cols"] = [
    { wch: 18 },
    { wch: 28 },
    { wch: 22 },
    { wch: 15 },
    { wch: 15 },
  ];
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "SK_Yudisium");
  XLSX.writeFile(wb, "Template_Import_SK_Yudisium.xlsx");
}

function handleExcelSubmit() {
  if (!selectedExcelFile.value) {
    Swal.fire({
      icon: "warning",
      title: "File Excel Belum Dipilih",
      text: "Silakan pilih atau unggah file Excel data mahasiswa terlebih dahulu.",
    });
    return;
  }

  const formData = new FormData();
  formData.append("file", selectedExcelFile.value);
  if (excelForm.prodi_id) {
    formData.append("prodi_id", String(excelForm.prodi_id));
  }
  if (excelForm.tanggal) {
    formData.append("tanggal", excelForm.tanggal);
  }
  if (excelForm.no_surat) {
    formData.append("nomor_surat", excelForm.no_surat);
    formData.append("no_surat", excelForm.no_surat);
  }
  if (excelForm.petanda_tangan) {
    formData.append("petanda_tangan", excelForm.petanda_tangan);
  }
  formData.append("mode", "excel");

  emit("submit", formData);
}

/* ==========================================================================
   2. MODE EDIT DATA (HANYA KETIKA isEdit = true)
   ========================================================================== */
const defaultEditForm = {
  id: "",
  prodi_id: 0,
  nim: "",
  nama_mhs: "",
  prodi: "",
  no_surat: "",
  nomor_surat: "",
  nilai_skripsi: "",
  jenis_kelamin: "L",
  tanggal: new Date().toISOString().slice(0, 10),
  petanda_tangan: "tidak",
};

const editForm = reactive({ ...defaultEditForm });

watch(
  () => props.modelValue,
  (val) => {
    if (!props.isEdit || !val) return;
    editForm.id = val.id ?? "";
    editForm.nama_mhs = val.nama_mhs ?? "";
    editForm.nim = val.nim ?? "";
    editForm.prodi = val.prodi ?? "";
    editForm.prodi_id = val.prodi_id ? Number(val.prodi_id) : 0;
    editForm.no_surat = val.no_surat || (val.nomor_surat ? val.nomor_surat.split('/')[0].replace('SU-', '').trim() : "");
    editForm.nomor_surat = val.nomor_surat ?? "";
    editForm.nilai_skripsi = val.nilai_skripsi ?? "";
    editForm.jenis_kelamin = val.jenis_kelamin ?? "L";
    editForm.tanggal = val.tanggal
      ? String(val.tanggal).slice(0, 10)
      : new Date().toISOString().slice(0, 10);
    editForm.petanda_tangan = val.petanda_tangan ?? "tidak";
  },
  { immediate: true }
);

function handleEditSubmit() {
  emit("submit", { ...editForm, mode: "manual" });
}

async function getProdi() {
  try {
    const response = await apiGet(`/get-prodi`);
    if (response.success) {
      const data = response.data?.data;
      listProdi.value = Array.isArray(data) ? data : [data];
      if (listProdi.value.length === 1 && !props.isEdit) {
        const singleProdi = listProdi.value[0];
        excelForm.prodi_id = Number(singleProdi.id);
        editForm.prodi_id = Number(singleProdi.id);
      }
    }
  } catch (error) {}
}

onMounted(() => {
  getProdi();
});
</script>

<template>
  <div class="row">
    <div class="col-xl-12">
      <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
          <div class="card-title">
            {{ isEdit ? "Edit SK Yudisium" : "Import SK Yudisium (Excel)" }}
          </div>
          <button
            v-if="!isEdit"
            type="button"
            class="btn btn-success btn-sm shadow-sm"
            @click="downloadTemplate"
          >
            <i class="ri-download-2-line me-1 align-middle"></i> Download Template Excel
          </button>
        </div>

        <div class="card-body">
          <!-- ======================================================== -->
          <!-- FORM EDIT (HANYA MUNCUL KETIKA isEdit = true)            -->
          <!-- ======================================================== -->
          <form v-if="isEdit" @submit.prevent="handleEditSubmit">
            <div class="row gy-4">
              <!-- Program Studi Unit -->
              <div class="col-xl-6">
                <label class="form-label">Program Studi Unit :</label>
                <select
                  v-model="editForm.prodi_id"
                  class="form-select"
                  :class="{ 'is-invalid': errors?.prodi_id }"
                >
                  <option :value="0">-- Pilih Program Studi Unit --</option>
                  <option
                    v-for="p in listProdi"
                    :key="p.id"
                    :value="Number(p.id)"
                  >
                    {{ p.nama }} ({{ p.alias }})
                  </option>
                </select>
                <div v-if="errors?.prodi_id" class="invalid-feedback">
                  {{ errors.prodi_id[0] }}
                </div>
              </div>

              <!-- Tanggal Surat -->
              <div class="col-xl-6">
                <label class="form-label">Tanggal Surat :</label>
                <input
                  type="date"
                  v-model="editForm.tanggal"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.tanggal }"
                />
                <div v-if="errors?.tanggal" class="invalid-feedback">
                  {{ errors.tanggal[0] }}
                </div>
              </div>

              <!-- Nomor Surat -->
              <div class="col-xl-6">
                <label class="form-label">Nomor Surat :</label>
                <input
                  type="text"
                  v-model="editForm.no_surat"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.no_surat || errors?.nomor_surat }"
                  placeholder="Contoh: 2346"
                />
                <div class="form-text fs-12 text-muted">
                  Cukup isi nomornya saja (contoh: <strong>2346</strong>). Format resmi akan dibuat otomatis.
                </div>
                <div v-if="errors?.nomor_surat || errors?.no_surat" class="invalid-feedback d-block">
                  {{ errors?.nomor_surat ? errors.nomor_surat[0] : errors?.no_surat[0] }}
                </div>
              </div>

              <!-- Nilai Skripsi -->
              <div class="col-xl-6">
                <label class="form-label">Nilai Skripsi :</label>
                <input
                  type="text"
                  v-model="editForm.nilai_skripsi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nilai_skripsi }"
                  placeholder="Contoh: 83,75 / A-"
                />
                <div v-if="errors?.nilai_skripsi" class="invalid-feedback">
                  {{ errors.nilai_skripsi[0] }}
                </div>
              </div>

              <hr />
              <div class="card-title mb-0">Data Mahasiswa</div>

              <!-- Nama Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">Nama Mahasiswa :</label>
                <input
                  type="text"
                  v-model="editForm.nama_mhs"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nama_mhs }"
                  placeholder="Nama Mahasiswa"
                />
                <div v-if="errors?.nama_mhs" class="invalid-feedback">
                  {{ errors.nama_mhs[0] }}
                </div>
              </div>

              <!-- NIM -->
              <div class="col-xl-6">
                <label class="form-label">NIM :</label>
                <input
                  type="text"
                  v-model="editForm.nim"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.nim }"
                  placeholder="NIM Mahasiswa"
                />
                <div v-if="errors?.nim" class="invalid-feedback">
                  {{ errors.nim[0] }}
                </div>
              </div>

              <!-- Program Studi Mahasiswa -->
              <div class="col-xl-6">
                <label class="form-label">Program Studi Mahasiswa :</label>
                <input
                  type="text"
                  v-model="editForm.prodi"
                  class="form-control"
                  :class="{ 'is-invalid': errors?.prodi }"
                  placeholder="Program Studi"
                />
                <div v-if="errors?.prodi" class="invalid-feedback">
                  {{ errors.prodi[0] }}
                </div>
              </div>

              <!-- Jenis Kelamin -->
              <div class="col-xl-6">
                <label class="form-label">Jenis Kelamin :</label>
                <select
                  v-model="editForm.jenis_kelamin"
                  class="form-select"
                  :class="{ 'is-invalid': errors?.jenis_kelamin }"
                >
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
                <div v-if="errors?.jenis_kelamin" class="invalid-feedback">
                  {{ errors.jenis_kelamin[0] }}
                </div>
              </div>

              <!-- Cetak Tanda Tangan Dekan -->
              <div class="col-xl-6">
                <label class="form-label">Cetak Tanda Tangan & Stempel Dekan :</label>
                <select
                  v-model="editForm.petanda_tangan"
                  class="form-select"
                  :class="{ 'is-invalid': errors?.petanda_tangan }"
                >
                  <option value="tidak">Tidak (Tanda Tangan Manual Basah)</option>
                  <option value="ya">Ya (Tanda Tangan Digital & Stempel)</option>
                  <option value="stempel">Hanya Stempel Digital</option>
                </select>
                <div v-if="errors?.petanda_tangan" class="invalid-feedback">
                  {{ errors.petanda_tangan[0] }}
                </div>
              </div>

              <!-- Tombol Aksi Edit -->
              <div class="col-xl-12 d-flex justify-content-end gap-2 mt-4">
                <router-link to="/sk-yudisium" class="btn btn-light">
                  Batal
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
                  ></span>
                  Simpan Perubahan
                </button>
              </div>
            </div>
          </form>

          <!-- ======================================================== -->
          <!-- FORM CREATE (HANYA IMPORT EXCEL)                        -->
          <!-- ======================================================== -->
          <form v-else @submit.prevent="handleExcelSubmit">
            <div class="alert alert-success-transparent d-flex align-items-center mb-4" role="alert">
              <i class="ri-file-excel-2-fill fs-24 text-success me-2"></i>
              <div>
                <strong>Unggah Berkas Excel:</strong> Silakan unggah berkas Excel yang berisi data mahasiswa yudisium. Sistem akan otomatis membuatkan data SK Yudisium dan men-generate berkas PDF untuk setiap mahasiswa.
              </div>
            </div>

            <div class="row gy-4">
              <!-- Pengaturan Serentak Dokumen SK Yudisium -->
              <div class="col-12">
                <div class="card bg-light border-0 shadow-none rounded-3 p-3">
                  <h6 class="fw-semibold text-dark mb-3">
                    <i class="ri-settings-4-line me-1 text-primary"></i> Pengaturan Dokumen SK Yudisium
                  </h6>
                  <div class="row g-3">
                    <!-- Program Studi Unit -->
                    <div class="col-md-6 col-lg-3">
                      <label class="form-label small fw-semibold">Program Studi Unit :</label>
                      <select
                        v-model="excelForm.prodi_id"
                        class="form-select form-select-sm"
                        :disabled="listProdi.length === 1"
                      >
                        <option :value="0">-- Pilih Prodi Unit --</option>
                        <option
                          v-for="p in listProdi"
                          :key="p.id"
                          :value="Number(p.id)"
                        >
                          {{ p.nama }} ({{ p.alias }})
                        </option>
                      </select>
                      <div class="form-text fs-11 text-muted">
                        Digunakan jika kolom PRODI di Excel kosong.
                      </div>
                    </div>

                    <!-- Tanggal Surat -->
                    <div class="col-md-6 col-lg-3">
                      <label class="form-label small fw-semibold">Tanggal Surat :</label>
                      <input
                        type="date"
                        v-model="excelForm.tanggal"
                        class="form-control form-control-sm"
                      />
                    </div>

                    <!-- Nomor Surat -->
                    <div class="col-md-6 col-lg-3">
                      <label class="form-label small fw-semibold">Nomor Surat :</label>
                      <input
                        type="text"
                        v-model="excelForm.no_surat"
                        class="form-control form-control-sm"
                        placeholder="Contoh: 2346"
                      />
                      <div class="form-text fs-11 text-muted">
                        Cukup isi nomornya saja (contoh: <strong>2346</strong>). Format resmi dibuat otomatis.
                      </div>
                    </div>

                    <!-- Opsi Tanda Tangan -->
                    <div class="col-md-6 col-lg-3">
                      <label class="form-label small fw-semibold">Cetak Tanda Tangan Dekan :</label>
                      <select
                        v-model="excelForm.petanda_tangan"
                        class="form-select form-select-sm"
                      >
                        <option value="tidak">Tidak (Tanda Tangan Basah)</option>
                        <option value="ya">Ya (Tanda Tangan & Stempel)</option>
                        <option value="stempel">Hanya Stempel Digital</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Upload File Box -->
              <div class="col-12">
                <label class="form-label fw-semibold">Unggah File Excel (.xlsx, .xls, .csv) :</label>
                <input
                  type="file"
                  ref="excelFileInput"
                  class="d-none"
                  accept=".xlsx, .xls, .csv"
                  @change="handleFileChange"
                />

                <div
                  class="dropzone-box text-center p-4 border rounded-3 cursor-pointer"
                  :class="{ 'border-primary bg-primary-subtle': isDragging, 'has-file': selectedExcelFile }"
                  @click="triggerFileInput"
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="onFileDrop"
                >
                  <template v-if="!selectedExcelFile">
                    <div class="mb-2">
                      <i class="ri-upload-cloud-2-line fs-48 text-muted"></i>
                    </div>
                    <div class="fw-semibold text-dark fs-15 mb-1">
                      Klik atau seret file Excel ke area ini
                    </div>
                    <div class="text-muted fs-12 mb-2">
                      Mendukung format berkas .xlsx, .xls, atau .csv (Maksimal 10 MB)
                    </div>
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm px-3 rounded-pill"
                    >
                      Pilih Berkas Excel
                    </button>
                  </template>

                  <template v-else>
                    <div class="d-flex align-items-center justify-content-between text-start p-2">
                      <div class="d-flex align-items-center">
                        <div class="excel-icon-box bg-success-subtle text-success me-3 rounded-3 d-flex align-items-center justify-content-center">
                          <i class="ri-file-excel-2-fill fs-28"></i>
                        </div>
                        <div>
                          <div class="fw-semibold text-dark">{{ selectedExcelFile.name }}</div>
                          <div class="small text-muted">
                            {{ (selectedExcelFile.size / 1024).toFixed(1) }} KB &bull;
                            <span class="text-success fw-semibold">
                              {{ excelPreviewData.length }} baris data terdeteksi
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="d-flex gap-2" @click.stop>
                        <button
                          type="button"
                          class="btn btn-outline-secondary btn-sm"
                          @click="triggerFileInput"
                        >
                          <i class="ri-refresh-line me-1"></i> Ganti Berkas
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-danger btn-sm"
                          @click="removeSelectedFile"
                        >
                          <i class="ri-delete-bin-line me-1"></i> Hapus
                        </button>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Preview Tabel Data Excel -->
              <div v-if="excelPreviewData.length > 0" class="col-12">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h6 class="fw-semibold text-dark mb-0">
                    <i class="ri-table-line me-1 text-primary"></i> Preview Data Mahasiswa ({{ excelPreviewData.length }} Mahasiswa)
                  </h6>
                  <span class="badge bg-success-subtle text-success fs-12">
                    Siap Diimport
                  </span>
                </div>

                <div class="table-responsive border rounded-3" style="max-height: 320px; overflow-y: auto;">
                  <table class="table table-bordered table-sm table-striped mb-0 text-nowrap">
                    <thead class="table-light sticky-top">
                      <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Nilai Skripsi</th>
                        <th class="text-center">L/P</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in excelPreviewData" :key="idx">
                        <td class="text-center">{{ idx + 1 }}</td>
                        <td class="fw-semibold text-primary">{{ item.nim || '-' }}</td>
                        <td>{{ item.nama || '-' }}</td>
                        <td>{{ item.prodi || (listProdi.find(p => p.id === excelForm.prodi_id)?.nama || '-') }}</td>
                        <td>{{ item.nilai_skripsi || '-' }}</td>
                        <td class="text-center">
                          <span class="badge" :class="item.jenis_kelamin === 'P' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'">
                            {{ item.jenis_kelamin || 'L' }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Tombol Aksi Excel -->
              <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <router-link to="/sk-yudisium" class="btn btn-light">
                  Batal
                </router-link>
                <button
                  type="submit"
                  class="btn btn-success"
                  :disabled="!selectedExcelFile || btnLoading"
                >
                  <span
                    v-if="btnLoading"
                    class="spinner-border spinner-border-sm me-1"
                    role="status"
                  ></span>
                  <i v-else class="ri-upload-cloud-2-line me-1 align-middle"></i>
                  {{ btnLoading ? "Sedang Mengimpor & Membuat PDF..." : `Import ${excelPreviewData.length > 0 ? excelPreviewData.length + ' Data' : ''} SK Yudisium` }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dropzone-box {
  background-color: #fdfdfd;
  border: 2px dashed #d0d7de !important;
  transition: all 0.2s ease-in-out;
}

.dropzone-box:hover {
  background-color: #f8fafc;
  border-color: #0d6efd !important;
}

.dropzone-box.has-file {
  border-style: solid !important;
  border-color: #198754 !important;
  background-color: #f8fff9;
}

.excel-icon-box {
  width: 48px;
  height: 48px;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
