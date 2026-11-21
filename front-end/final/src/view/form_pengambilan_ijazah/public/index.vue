<template>
  <div class="public-form-container min-vh-100 py-4 py-md-5">
    <div class="container">
      <!-- Loading State -->
      <div v-if="loadingInit" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
          <span class="visually-hidden">Memuat...</span>
        </div>
        <h5 class="text-muted fw-normal">Memuat formulir pengambilan ijazah...</h5>
      </div>

      <!-- Closed / Expired State -->
      <div v-else-if="!isAvailable" class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-4 p-md-5">
            <div class="mb-4">
              <div class="status-icon-circle bg-danger-subtle text-danger mx-auto">
                <i class="ri-lock-line fs-1"></i>
              </div>
            </div>
            <span class="badge bg-danger-subtle text-danger fs-13 px-3 py-2 rounded-pill mx-auto mb-3">
              <i class="ri-time-line me-1"></i> Formulir Sedang Ditutup
            </span>
            <h3 class="fw-bold text-dark mb-2">{{ settingInfo.title || 'Formulir Pengambilan Ijazah' }}</h3>
            <p class="text-muted mb-4 fs-15">
              Mohon maaf, tautan formulir ini saat ini tidak menerima pengisian baru karena masa aktif formulir telah berakhir atau sedang dinonaktifkan oleh bagian BAAK.
            </p>

            <div class="alert alert-light border rounded-3 text-start mb-4 fs-14">
              <div class="d-flex align-items-center mb-1 text-muted">
                <i class="ri-information-line me-2 fs-16 text-primary"></i>
                <span class="fw-semibold">Informasi:</span>
              </div>
              <ul class="mb-0 ps-3 text-secondary">
                <li>Biro Administrasi Akademik & Kemahasiswaan (BAAK)</li>
                <li>Universitas Islam Internasional Darullughah Wadda'wah</li>
                <li>Silakan hubungi staf BAAK untuk informasi pembukaan formulir kembali.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Success State (After Submit) -->
      <div v-else-if="submittedSuccess" class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-4 p-md-5 animate__animated animate__fadeIn">
            <div class="mb-4">
              <div class="status-icon-circle bg-success-subtle text-success mx-auto">
                <i class="ri-checkbox-circle-line fs-1"></i>
              </div>
            </div>
            <span class="badge bg-success-subtle text-success fs-13 px-3 py-2 rounded-pill mx-auto mb-3">
              <i class="ri-check-line me-1"></i> Formulir Berhasil Dikirim
            </span>
            <h3 class="fw-bold text-dark mb-2">Terima Kasih, {{ submittedData.nama_mhs }}!</h3>
            <p class="text-muted mb-4 fs-15">
              Data formulir bebas tanggungan Anda telah berhasil disimpan di sistem BAAK dan otomatis disinkronkan.
            </p>

            <!-- Ringkasan Data -->
            <div class="card bg-light border-0 rounded-3 text-start p-3 mb-4 fs-14">
              <div class="row g-2">
                <div class="col-sm-4 text-muted">Nama Lengkap:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nama_mhs }}</div>

                <div class="col-sm-4 text-muted">NIM / NRIM:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nim }}</div>

                <div class="col-sm-4 text-muted">Program Studi:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.prodi }}</div>

                <div class="col-sm-4 text-muted">Tanggal Sidang:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.tanggal_sidang }}</div>

                <div class="col-sm-4 text-muted">Dosen Pembimbing:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.dosen_pembimbing }}</div>
              </div>
            </div>

            <!-- Tombol Unduh PDF -->
            <div class="d-grid gap-2 mb-3">
              <button
                class="btn btn-primary btn-lg rounded-3 fw-semibold shadow-sm py-3"
                @click="downloadPdf"
                :disabled="downloadingPdf"
              >
                <span v-if="downloadingPdf" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-else class="ri-file-download-line align-middle me-2 fs-18"></i>
                Unduh Formulir Bebas Tanggungan (PDF)
              </button>
            </div>

            <p class="text-muted fs-13 mb-4">
              * Silakan cetak dokumen PDF tersebut untuk keperluan pengesahan tanda tangan di loket BAAK.
            </p>

            <button
              type="button"
              class="btn btn-outline-secondary btn-sm rounded-pill mx-auto px-4"
              @click="resetForm"
            >
              <i class="ri-refresh-line me-1"></i> Isi Formulir Baru
            </button>
          </div>
        </div>
      </div>

      <!-- Active Form State -->
      <div v-else class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <!-- Main Form Card -->
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Header Brand -->
            <div class="header-brand-banner p-4 p-md-5 text-white position-relative">
              <div class="row align-items-center">
                <div class="col-auto">
                  <img
                    src="/logo_uii.png"
                    alt="Logo UII Dalwa"
                    class="brand-logo bg-white p-2 rounded-circle shadow-sm"
                    style="width: 72px; height: 72px; object-fit: contain;"
                    @error="handleLogoError"
                  />
                </div>
                <div class="col">
                  <span class="badge bg-white text-primary fw-semibold px-3 py-1 rounded-pill mb-2 fs-12">
                    <i class="ri-time-line me-1"></i> {{ settingInfo.remaining_text || 'Formulir Aktif' }}
                  </span>
                  <h2 class="fw-bold mb-1 fs-22 fs-md-26">FORMULIR PENGAMBILAN IJAZAH</h2>
                  <p class="mb-0 text-white-50 fs-14">
                    Biro Administrasi Akademik & Kemahasiswaan (BAAK) - UII DALWA
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Content -->
            <div class="card-body p-4 p-md-5">
              <div class="alert alert-info border-0 rounded-3 d-flex align-items-start mb-4 py-3">
                <i class="ri-information-fill fs-20 text-info me-2 mt-1"></i>
                <div class="fs-13 text-secondary">
                  Silakan cari <strong>Nama atau NIM</strong> Anda pada kolom pencarian mahasiswa. Sistem akan otomatis melengkapi biodata dan program studi Anda.
                </div>
              </div>

              <form @submit.prevent="handleSubmit">
                <!-- 1. PENCARIAN MAHASISWA -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-dark fs-14">
                    <i class="ri-user-search-line me-1 text-primary"></i> Cari Mahasiswa <span class="text-danger">*</span>
                  </label>
                  <div class="position-relative">
                    <div class="input-group">
                      <span class="input-group-text bg-white border-end-0">
                        <i class="ri-search-line text-muted"></i>
                      </span>
                      <input
                        type="text"
                        class="form-control border-start-0"
                        v-model="searchMhsQuery"
                        @input="handleSearchMhs"
                        @focus="showMhsDropdown = mhsOptions.length > 0"
                        placeholder="Ketik Nama atau NIM mahasiswa..."
                        autocomplete="off"
                      />
                      <span v-if="loadingMhs" class="input-group-text bg-white border-start-0">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                      </span>
                    </div>

                    <!-- Dropdown Hasil Pencarian Mahasiswa -->
                    <div
                      v-if="showMhsDropdown && mhsOptions.length > 0"
                      class="autocomplete-dropdown shadow-lg rounded-3 border bg-white position-absolute w-100 mt-1"
                    >
                      <div
                        v-for="mhs in mhsOptions"
                        :key="mhs.id"
                        class="autocomplete-item p-3 border-bottom cursor-pointer"
                        @click="selectMahasiswa(mhs)"
                      >
                        <div class="fw-semibold text-dark fs-14">{{ mhs.nama }}</div>
                        <div class="text-muted fs-12 d-flex gap-3 mt-1">
                          <span><i class="ri-id-card-line me-1"></i>NIM: {{ mhs.nim }}</span>
                          <span><i class="ri-book-open-line me-1"></i>Prodi: {{ mhs.prodi }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BIODATA OTOMATIS -->
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">Nama Lengkap</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      v-model="form.nama_mhs"
                      placeholder="Nama Mahasiswa"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">NIM / NRIM</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      v-model="form.nim"
                      placeholder="NIM Mahasiswa"
                      required
                    />
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-semibold text-dark fs-13">Program Studi</label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      v-model="form.prodi"
                      placeholder="Program Studi"
                      required
                    />
                  </div>
                </div>

                <hr class="my-4 text-muted opacity-25" />

                <!-- 2. PENCARIAN DOSEN PEMBIMBING -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-dark fs-14">
                    <i class="ri-user-star-line me-1 text-primary"></i> Dosen Pembimbing <span class="text-danger">*</span>
                  </label>
                  <div class="position-relative">
                    <div class="input-group">
                      <span class="input-group-text bg-white border-end-0">
                        <i class="ri-search-line text-muted"></i>
                      </span>
                      <input
                        type="text"
                        class="form-control border-start-0"
                        v-model="searchDosenQuery"
                        @input="handleSearchDosen"
                        @focus="showDosenDropdown = dosenOptions.length > 0"
                        placeholder="Ketik Nama Dosen Pembimbing..."
                        autocomplete="off"
                      />
                      <span v-if="loadingDosen" class="input-group-text bg-white border-start-0">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                      </span>
                    </div>

                    <!-- Dropdown Hasil Pencarian Dosen -->
                    <div
                      v-if="showDosenDropdown && dosenOptions.length > 0"
                      class="autocomplete-dropdown shadow-lg rounded-3 border bg-white position-absolute w-100 mt-1"
                    >
                      <div
                        v-for="dosen in dosenOptions"
                        :key="dosen.id"
                        class="autocomplete-item p-3 border-bottom cursor-pointer"
                        @click="selectDosen(dosen)"
                      >
                        <div class="fw-semibold text-dark fs-14">{{ dosen.nama }}</div>
                        <div class="text-muted fs-12 mt-1">
                          <span v-if="dosen.nidn"><i class="ri-shield-user-line me-1"></i>NIDN: {{ dosen.nidn }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <small class="text-muted fs-12 mt-1 d-block">
                    * Anda juga dapat mengetik langsung nama pembimbing jika tidak tercantum dalam pencarian.
                  </small>
                </div>

                <!-- 3. KONTAK & SIDANG -->
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">
                      <i class="ri-whatsapp-line text-success me-1"></i> No. HP / WhatsApp <span class="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.no_hp"
                      placeholder="Contoh: 081234567890"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">
                      <i class="ri-calendar-line text-primary me-1"></i> Tanggal Sidang Skripsi <span class="text-danger">*</span>
                    </label>
                    <input
                      type="date"
                      class="form-control"
                      v-model="form.tanggal_sidang"
                      required
                    />
                  </div>
                </div>

                <!-- 4. JUDUL SKRIPSI -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-dark fs-13">
                    <i class="ri-article-line text-primary me-1"></i> Judul Skripsi <span class="text-danger">*</span>
                  </label>
                  <textarea
                    class="form-control"
                    v-model="form.judul_skripsi"
                    rows="3"
                    dir="auto"
                    placeholder="Masukkan judul skripsi lengkap sesuai naskah..."
                    required
                  ></textarea>
                </div>

                <!-- KOTAK PERSYARATAN BEBAS TANGGUNGAN -->
                <div class="card bg-primary-subtle border-0 rounded-3 p-3 mb-4">
                  <div class="d-flex align-items-center mb-2">
                    <i class="ri-checkbox-circle-fill text-primary fs-18 me-2"></i>
                    <span class="fw-bold text-primary fs-14">Kewajiban Bebas Tanggungan:</span>
                  </div>
                  <div class="row g-2 text-dark fs-13 ps-3">
                    <div class="col-6 col-md-3">1. Keuangan Pondok</div>
                    <div class="col-6 col-md-3">2. Keuangan Jamiah</div>
                    <div class="col-6 col-md-3">3. Jilid Skripsi</div>
                    <div class="col-6 col-md-3">4. TTD Yayasan</div>
                  </div>
                </div>

                <!-- TOMBOL SUBMIT -->
                <div class="d-grid gap-2">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg rounded-3 fw-semibold shadow-sm py-3"
                    :disabled="submitting"
                  >
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i v-else class="ri-send-plane-fill align-middle me-2"></i>
                    {{ submitting ? 'Sedang Menyimpan...' : 'Kirim Formulir Pengambilan Ijazah' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Footer Info -->
          <div class="text-center text-muted mt-4 fs-13">
            &copy; {{ new Date().getFullYear() }} BAAK Universitas Islam Internasional Darullughah Wadda'wah. All rights reserved.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import Swal from 'sweetalert2';
import axios from 'axios';
import { BASE_URL } from '../../../services/api/http';

export default defineComponent({
  name: 'PublicFormPengambilanIjazah',
  setup() {
    const route = useRoute();
    const slug = (route.params.slug as string) || 'pengambilan-ijazah';

    const loadingInit = ref(true);
    const isAvailable = ref(true);
    const settingInfo = ref<any>({});

    const submitting = ref(false);
    const submittedSuccess = ref(false);
    const submittedData = ref<any>({});
    const downloadingPdf = ref(false);

    // Form inputs
    const form = ref({
      nama_mhs: '',
      nim: '',
      prodi: '',
      dosen_pembimbing: '',
      judul_skripsi: '',
      tanggal_sidang: '',
      no_hp: '',
    });

    // Autocomplete Mahasiswa
    const searchMhsQuery = ref('');
    const mhsOptions = ref<any[]>([]);
    const loadingMhs = ref(false);
    const showMhsDropdown = ref(false);
    let mhsTimeout: any = null;

    // Autocomplete Dosen
    const searchDosenQuery = ref('');
    const dosenOptions = ref<any[]>([]);
    const loadingDosen = ref(false);
    const showDosenDropdown = ref(false);
    let dosenTimeout: any = null;

    // Cek ketersediaan form publik
    async function checkFormAvailability() {
      try {
        loadingInit.value = true;
        const res = await axios.get(`${BASE_URL}/public/form-pengambilan-ijazah/check/${slug}`);
        if (res.data?.status && res.data?.is_available) {
          isAvailable.value = true;
          settingInfo.value = res.data.data || {};
        } else {
          isAvailable.value = false;
          settingInfo.value = res.data?.data || {};
        }
      } catch (error: any) {
        isAvailable.value = false;
        settingInfo.value = {};
      } finally {
        loadingInit.value = false;
      }
    }

    // Pencarian Mahasiswa
    function handleSearchMhs() {
      clearTimeout(mhsTimeout);
      if (!searchMhsQuery.value || searchMhsQuery.value.trim().length < 2) {
        mhsOptions.value = [];
        showMhsDropdown.value = false;
        return;
      }

      mhsTimeout = setTimeout(async () => {
        try {
          loadingMhs.value = true;
          const res = await axios.get(`${BASE_URL}/public/form-pengambilan-ijazah/get-mhs`, {
            params: {
              search: searchMhsQuery.value.trim(),
              slug: slug,
            },
          });
          const list = Array.isArray(res.data?.data) ? res.data.data : [];
          mhsOptions.value = list;
          showMhsDropdown.value = list.length > 0;
        } catch (error) {
          mhsOptions.value = [];
        } finally {
          loadingMhs.value = false;
        }
      }, 300);
    }

    function selectMahasiswa(mhs: any) {
      form.value.nama_mhs = mhs.nama || '';
      form.value.nim = mhs.nim || '';
      form.value.prodi = mhs.prodi || '';
      searchMhsQuery.value = `${mhs.nama} (${mhs.nim})`;
      showMhsDropdown.value = false;
    }

    // Pencarian Dosen
    function handleSearchDosen() {
      clearTimeout(dosenTimeout);
      form.value.dosen_pembimbing = searchDosenQuery.value;

      if (!searchDosenQuery.value || searchDosenQuery.value.trim().length < 2) {
        dosenOptions.value = [];
        showDosenDropdown.value = false;
        return;
      }

      dosenTimeout = setTimeout(async () => {
        try {
          loadingDosen.value = true;
          const res = await axios.get(`${BASE_URL}/public/form-pengambilan-ijazah/get-dosen`, {
            params: {
              search: searchDosenQuery.value.trim(),
              slug: slug,
            },
          });
          const list = Array.isArray(res.data?.data) ? res.data.data : [];
          dosenOptions.value = list;
          showDosenDropdown.value = list.length > 0;
        } catch (error) {
          dosenOptions.value = [];
        } finally {
          loadingDosen.value = false;
        }
      }, 300);
    }

    function selectDosen(dosen: any) {
      form.value.dosen_pembimbing = dosen.nama || '';
      searchDosenQuery.value = dosen.nama || '';
      showDosenDropdown.value = false;
    }

    // Close dropdown on click outside
    function handleClickOutside(event: MouseEvent) {
      const target = event.target as HTMLElement;
      if (!target.closest('.autocomplete-dropdown') && !target.closest('.input-group')) {
        showMhsDropdown.value = false;
        showDosenDropdown.value = false;
      }
    }

    // Submit Form
    async function handleSubmit() {
      if (!form.value.nama_mhs || !form.value.nim || !form.value.prodi) {
        Swal.fire({
          icon: 'warning',
          title: 'Periksa Data Mahasiswa',
          text: 'Silakan pilih atau isi data Mahasiswa, NIM, dan Program Studi terlebih dahulu.',
        });
        return;
      }

      if (!form.value.dosen_pembimbing) {
        Swal.fire({
          icon: 'warning',
          title: 'Dosen Pembimbing Kosong',
          text: 'Silakan pilih atau masukkan nama Dosen Pembimbing.',
        });
        return;
      }

      try {
        submitting.value = true;
        const res = await axios.post(
          `${BASE_URL}/public/form-pengambilan-ijazah/submit/${slug}`,
          form.value
        );

        if (res.data?.status) {
          submittedSuccess.value = true;
          submittedData.value = res.data.data;
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan',
            text: res.data?.message || 'Terjadi kesalahan saat menyimpan formulir.',
          });
        }
      } catch (error: any) {
        const msg = error.response?.data?.message || 'Gagal mengirim formulir. Silakan coba lagi.';
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan',
          text: msg,
        });
      } finally {
        submitting.value = false;
      }
    }

    // Download PDF
    async function downloadPdf() {
      if (!submittedData.value?.id) return;
      try {
        downloadingPdf.value = true;
        const res = await axios.get(
          `${BASE_URL}/public/form-pengambilan-ijazah/download/${submittedData.value.id}`,
          { responseType: 'blob' }
        );

        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `form_bebas_tanggungan_${submittedData.value.nim || submittedData.value.id}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => window.URL.revokeObjectURL(url), 10000);
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal Unduh',
          text: 'File PDF gagal diunduh. Silakan coba beberapa saat lagi.',
        });
      } finally {
        downloadingPdf.value = false;
      }
    }

    function resetForm() {
      form.value = {
        nama_mhs: '',
        nim: '',
        prodi: '',
        dosen_pembimbing: '',
        judul_skripsi: '',
        tanggal_sidang: '',
        no_hp: '',
      };
      searchMhsQuery.value = '';
      searchDosenQuery.value = '';
      submittedSuccess.value = false;
      submittedData.value = {};
    }

    function handleLogoError(e: Event) {
      const target = e.target as HTMLImageElement;
      target.style.display = 'none';
    }

    onMounted(() => {
      checkFormAvailability();
      document.addEventListener('click', handleClickOutside);
    });

    onBeforeUnmount(() => {
      document.removeEventListener('click', handleClickOutside);
    });

    return {
      slug,
      loadingInit,
      isAvailable,
      settingInfo,
      form,
      submitting,
      submittedSuccess,
      submittedData,
      downloadingPdf,
      searchMhsQuery,
      mhsOptions,
      loadingMhs,
      showMhsDropdown,
      handleSearchMhs,
      selectMahasiswa,
      searchDosenQuery,
      dosenOptions,
      loadingDosen,
      showDosenDropdown,
      handleSearchDosen,
      selectDosen,
      handleSubmit,
      downloadPdf,
      resetForm,
      handleLogoError,
    };
  },
});
</script>

<style scoped>
.public-form-container {
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
  min-height: 100vh;
}

.header-brand-banner {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
}

.status-icon-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cursor-pointer {
  cursor: pointer;
}

.autocomplete-dropdown {
  max-height: 250px;
  overflow-y: auto;
  z-index: 1050;
  background-color: #ffffff;
}

.autocomplete-item:hover {
  background-color: #f1f5f9;
}

.autocomplete-item:last-child {
  border-bottom: none !important;
}
</style>
