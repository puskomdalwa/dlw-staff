<template>
  <div class="public-form-container min-vh-100 py-4 py-md-5">
    <div class="container">
      <!-- Loading State -->
      <div v-if="loadingInit" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
          <span class="visually-hidden">Memuat...</span>
        </div>
        <h5 class="text-muted fw-normal">Memuat formulir pendaftaran sidang skripsi...</h5>
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
            <h3 class="fw-bold text-dark mb-2">{{ settingInfo.title || 'Formulir Pendaftaran Sidang Skripsi' }}</h3>
            <p class="text-muted mb-4 fs-15">
              Mohon maaf, tautan formulir pendaftaran sidang skripsi ini saat ini tidak menerima pengisian baru karena masa aktif formulir telah berakhir atau sedang dinonaktifkan oleh Program Studi.
            </p>

            <div class="alert alert-light border rounded-3 text-start mb-4 fs-14">
              <div class="d-flex align-items-center mb-1 text-muted">
                <i class="ri-information-line me-2 fs-16 text-primary"></i>
                <span class="fw-semibold">Informasi:</span>
              </div>
              <ul class="mb-0 ps-3 text-secondary">
                <li>Program Sarjana (S1)</li>
                <li>Universitas Islam Internasional Darullughah Wadda'wah</li>
                <li>Silakan hubungi admin Program Studi Anda untuk informasi jadwal pembukaan pendaftaran sidang skripsi berikutnya.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- 1. HKI DEDICATED WHATSAPP PAGE (AFTER SUBMIT FOR HKI ONLY) -->
      <div v-else-if="submittedSuccess && isSubmittedHki" class="row justify-content-center animate__animated animate__fadeIn">
        <div class="col-12 col-md-9 col-lg-7">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-4 p-md-5">
            <!-- Icon WhatsApp Header -->
            <div class="mb-4">
              <div
                class="status-icon-circle mx-auto d-flex align-items-center justify-content-center shadow-sm"
                style="width: 80px; height: 80px; background-color: #e8f8ef; border-radius: 50%;"
              >
                <i class="ri-whatsapp-fill" style="font-size: 46px; color: #25D366;"></i>
              </div>
            </div>

            <span class="badge px-3 py-2 rounded-pill mx-auto mb-3 fs-13 fw-semibold text-white shadow-xs" style="background-color: #25D366;">
              <i class="ri-checkbox-circle-line me-1"></i> Pendaftaran Berhasil &bull; Mahasiswa Prodi HKI
            </span>

            <h3 class="fw-bold text-dark mb-2">Terima Kasih, {{ submittedData.nama }}!</h3>
            <p class="text-muted mb-4 fs-15">
              Pendaftaran sidang skripsi Program Studi <strong>Hukum Keluarga Islam (HKI)</strong> beserta seluruh berkas Anda telah berhasil kami terima.
            </p>

            <!-- KARTU UTAMA GABUNG GRUP WHATSAPP HKI -->
            <div
              class="card border-0 shadow rounded-4 p-4 mb-4 text-center text-white"
              style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);"
            >
              <span class="badge bg-white text-dark px-3 py-1 rounded-pill mx-auto mb-3 fw-bold fs-12 shadow-xs">
                <i class="ri-notification-3-line text-warning me-1"></i> WAJIB GABUNG GRUP SEKARANG
              </span>
              <h4 class="fw-bold mb-2 text-white">Grup WhatsApp Sidang Skripsi HKI</h4>
              <p class="fs-14 mb-4 opacity-95 text-white" style="max-width: 520px; margin: 0 auto; line-height: 1.6;">
                Seluruh mahasiswa Program Studi <strong>Hukum Keluarga Islam (HKI)</strong> yang telah mendaftar sidang skripsi diwajibkan bergabung ke grup WhatsApp berikut untuk informasi jadwal ujian, pembagian dosen penguji, dan koordinasi pelaksanaan:
              </p>
              <div class="mb-3">
                <a
                  :href="waGroupHkiUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-light btn-lg fw-bold rounded-pill px-4 py-3 shadow d-inline-flex align-items-center gap-2"
                  style="color: #128C7E !important; font-size: 16px;"
                >
                  <i class="ri-whatsapp-line fs-22"></i> Klik di Sini Untuk Gabung Grup WhatsApp
                  <i class="ri-external-link-line fs-18"></i>
                </a>
              </div>
              <div class="text-white-50 fs-12">
                Tautan grup: <a :href="waGroupHkiUrl" target="_blank" class="text-white text-decoration-underline fw-medium">{{ waGroupHkiUrl }}</a>
              </div>
            </div>

            <!-- Ringkasan Data Mahasiswa HKI -->
            <div class="card bg-light border-0 rounded-3 text-start p-4 mb-4 fs-14">
              <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                <i class="ri-file-user-line me-2 text-primary"></i> Ringkasan Pendaftaran Sidang:
              </h6>
              <div class="row g-2">
                <div class="col-sm-4 text-muted">Nama Mahasiswa:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nama }}</div>

                <div class="col-sm-4 text-muted">NIM:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nim }}</div>

                <div class="col-sm-4 text-muted" v-if="submittedData.nik">NIK:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.nik">{{ submittedData.nik }}</div>

                <div class="col-sm-4 text-muted">Program Studi:</div>
                <div class="col-sm-8 fw-semibold text-success">{{ submittedData.prodi }}</div>

                <div class="col-sm-4 text-muted">Periode Sidang:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.periode_sidang }}</div>

                <div class="col-sm-4 text-muted">Judul Skripsi:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.judul_skripsi }}</div>

                <div class="col-sm-4 text-muted" v-if="submittedData.dospem1 || submittedData.pembimbing1">Dospem 1:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.dospem1 || submittedData.pembimbing1">
                  {{ submittedData.dospem1 || submittedData.pembimbing1 }}
                </div>

                <div class="col-sm-4 text-muted" v-if="submittedData.dospem2 || submittedData.pembimbing2">Dospem 2:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.dospem2 || submittedData.pembimbing2">
                  {{ submittedData.dospem2 || submittedData.pembimbing2 }}
                </div>

                <div class="col-sm-4 text-muted">Total Berkas Diunggah:</div>
                <div class="col-sm-8 fw-semibold text-success">
                  <i class="ri-folder-check-line me-1"></i>
                  {{ submittedData.dokumen ? submittedData.dokumen.length : 0 }} Berkas Dokumen
                </div>
              </div>
            </div>

            <button
              type="button"
              class="btn btn-outline-primary btn-sm rounded-pill mx-auto px-4 py-2"
              @click="resetForm"
            >
              <i class="ri-refresh-line me-1"></i> Isi Pendaftaran Baru
            </button>
          </div>
        </div>
      </div>

      <!-- 2. STANDARD SUCCESS PAGE (FOR ALL OTHER PRODIS BESIDES HKI) -->
      <div v-else-if="submittedSuccess" class="row justify-content-center animate__animated animate__fadeIn">
        <div class="col-12 col-md-9 col-lg-7">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-4 p-md-5">
            <div class="mb-4">
              <div class="status-icon-circle bg-success-subtle text-success mx-auto">
                <i class="ri-checkbox-circle-line fs-1"></i>
              </div>
            </div>
            <span class="badge bg-success-subtle text-success fs-13 px-3 py-2 rounded-pill mx-auto mb-3">
              <i class="ri-check-line me-1"></i> Pendaftaran Sidang Berhasil Dikirim!
            </span>
            <h3 class="fw-bold text-dark mb-2">Terima Kasih, {{ submittedData.nama }}!</h3>
            <p class="text-muted mb-4 fs-15">
              Pendaftaran sidang skripsi beserta seluruh berkas persyaratan Anda telah berhasil kami terima dan disimpan di sistem.
            </p>

            <!-- Ringkasan Data -->
            <div class="card bg-light border-0 rounded-3 text-start p-4 mb-4 fs-14">
              <div class="row g-2">
                <div class="col-sm-4 text-muted">Nama Mahasiswa:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nama }}</div>

                <div class="col-sm-4 text-muted">NIM:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.nim }}</div>

                <div class="col-sm-4 text-muted" v-if="submittedData.nik">NIK:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.nik">{{ submittedData.nik }}</div>

                <div class="col-sm-4 text-muted">Program Studi:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.prodi }}</div>

                <div class="col-sm-4 text-muted">Periode Sidang:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.periode_sidang }}</div>

                <div class="col-sm-4 text-muted">Judul Skripsi:</div>
                <div class="col-sm-8 fw-semibold text-dark">{{ submittedData.judul_skripsi }}</div>

                <div class="col-sm-4 text-muted" v-if="submittedData.dospem1 || submittedData.pembimbing1">Dospem 1:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.dospem1 || submittedData.pembimbing1">
                  {{ submittedData.dospem1 || submittedData.pembimbing1 }}
                </div>

                <div class="col-sm-4 text-muted" v-if="submittedData.dospem2 || submittedData.pembimbing2">Dospem 2:</div>
                <div class="col-sm-8 fw-semibold text-dark" v-if="submittedData.dospem2 || submittedData.pembimbing2">
                  {{ submittedData.dospem2 || submittedData.pembimbing2 }}
                </div>

                <div class="col-sm-4 text-muted">Total Berkas Diunggah:</div>
                <div class="col-sm-8 fw-semibold text-success">
                  <i class="ri-folder-check-line me-1"></i>
                  {{ submittedData.dokumen ? submittedData.dokumen.length : 0 }} Berkas Dokumen
                </div>
              </div>
            </div>

            <div class="alert alert-info border-0 rounded-3 text-start mb-4 fs-13">
              <div class="d-flex align-items-center mb-1 text-info fw-semibold">
                <i class="ri-cloud-line me-2 fs-16"></i> Penyimpanan Berkas:
              </div>
              <p class="mb-0 text-secondary">
                Seluruh dokumen persyaratan skripsi Anda telah otomatis diarsipkan ke folder khusus mahasiswa:
                <code class="text-primary bg-white px-2 py-1 rounded">Berkas skripsi/{{ submittedData.prodi || 'PRODI' }}/{{ submittedData.nim }}</code>
                <code class="text-primary bg-white px-2 py-1 rounded">{{ submittedData.prodi || 'PRODI' }}/Dokumen Sidang Skripsi/{{ submittedData.nim }}</code>
                dan disinkronkan ke Google Drive Program Studi.
              </p>
            </div>

            <button
              type="button"
              class="btn btn-outline-primary btn-sm rounded-pill mx-auto px-4 py-2"
              @click="resetForm"
            >
              <i class="ri-refresh-line me-1"></i> Isi Pendaftaran Baru
            </button>
          </div>
        </div>
      </div>

      <!-- Active Form State -->
      <div v-else class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Header Brand Banner -->
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
                  <h2 class="fw-bold mb-1 fs-22 fs-md-26">FORMULIR PENDAFTARAN SIDANG SKRIPSI</h2>
                  <p class="mb-0 text-white-50 fs-14">
                    Program Strata 1 (S1) - Universitas Islam Internasional Darullughah Wadda'wah
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Body -->
            <div class="card-body p-4 p-md-5">
              <div class="alert alert-info border-0 rounded-3 d-flex align-items-start mb-4 py-3">
                <i class="ri-information-fill fs-20 text-info me-2 mt-1"></i>
                <div class="fs-13 text-secondary">
                  Cari <strong>Nama atau NIM</strong> Anda pada kolom pencarian. Sistem akan otomatis mengisi data diri dan menarik <strong>Judul Skripsi</strong> yang telah diajukan sebelumnya.
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
                        :key="mhs.id || mhs.nim"
                        class="autocomplete-item p-3 border-bottom cursor-pointer"
                        @click="selectMahasiswa(mhs)"
                      >
                        <div class="fw-semibold text-dark">{{ mhs.nama }}</div>
                        <div class="small text-muted d-flex gap-3 mt-1 flex-wrap">
                          <span><i class="ri-id-card-line me-1"></i> NIM: {{ mhs.nim }}</span>
                          <span v-if="mhs.prodi_mhs || mhs.nama_prodi || mhs.prodi">
                            <i class="ri-building-line me-1"></i> Prodi: {{ mhs.prodi_mhs || mhs.nama_prodi || mhs.prodi }}
                          </span>
                          <span v-if="mhs.judul || mhs.judul_skripsi" class="text-primary fw-medium">
                            <i class="ri-book-read-line me-1"></i> Judul: {{ mhs.judul || mhs.judul_skripsi }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 2. DATA MAHASISWA (READONLY/AUTOFILL) -->
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">Nama Mahasiswa <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      v-model="form.nama"
                      placeholder="Otomatis terisi setelah memilih mahasiswa"
                      readonly
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">NIM <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control bg-light"
                      v-model="form.nim"
                      placeholder="Otomatis terisi setelah memilih mahasiswa"
                      readonly
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark fs-13">NIK (Nomor Induk Kependudukan)</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.nik"
                      placeholder="Masukkan NIK jika belum terisi..."
                    />
                  </div>
                  <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="form-label fw-semibold text-dark fs-13 mb-0">Program Studi <span class="text-danger">*</span></label>
                      <button
                        type="button"
                        class="btn btn-link p-0 fs-11 text-decoration-none"
                        @click="toggleEditProdi"
                      >
                        <i :class="isManualProdi ? 'ri-lock-line text-secondary' : 'ri-edit-line text-primary'"></i>
                        {{ isManualProdi ? ' Kunci Otomatis' : ' Pilih Manual' }}
                      </button>
                    </div>
                    <select
                      v-if="isManualProdi"
                      class="form-select"
                      v-model="form.prodi"
                      @change="onManualProdiChange"
                    >
                      <option value="">-- Pilih Program Studi --</option>
                      <option
                        v-for="p in prodiListOptions"
                        :key="p.alias"
                        :value="p.nama"
                      >
                        {{ p.nama }} ({{ p.alias }})
                      </option>
                    </select>
                    <input
                      v-else
                      type="text"
                      class="form-control bg-light"
                      v-model="form.prodi"
                      placeholder="Otomatis terisi setelah memilih mahasiswa"
                      readonly
                    />
                  </div>
                </div>

                <hr class="my-4 text-muted opacity-25" />

                <!-- 3. PERIODE SIDANG & JUDUL SKRIPSI -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-dark fs-14">
                    <i class="ri-calendar-event-line me-1 text-primary"></i> Periode / Tanggal Pendaftaran Sidang <span class="text-danger">*</span>
                  </label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="form.periode_sidang"
                    required
                  />
                  <div class="form-text fs-12 text-muted">
                    Pilih tanggal rencana pelaksanaan atau tanggal pendaftaran sidang skripsi.
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label fw-semibold text-dark fs-14">
                    <i class="ri-book-open-line me-1 text-primary"></i> Judul Skripsi <span class="text-danger">*</span>
                  </label>
                  <div v-if="loadingJudul" class="text-muted fs-12 mb-1">
                    <span class="spinner-border spinner-border-sm me-1"></span> Mengambil data judul skripsi dari pengajuan sebelumnya...
                  </div>
                  <textarea
                    class="form-control"
                    rows="3"
                    v-model="form.judul_skripsi"
                    dir="auto"
                    placeholder="Judul skripsi akan otomatis terisi jika Anda sudah pernah mengajukan judul, atau Anda dapat menyesuaikannya..."
                    required
                  ></textarea>
                </div>

                <hr class="my-4 text-muted opacity-25" />

                <!-- 4. DOSEN PEMBIMBING SKRIPSI (DOSPEM 1 & DOSPEM 2) -->
                <div class="mb-4">
                  <div class="d-flex align-items-center mb-3">
                    <div class="status-icon-circle bg-primary-subtle text-primary me-2" style="width: 32px; height: 32px; min-width: 32px;">
                      <i class="ri-user-star-line fs-16"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-dark mb-0 fs-15">Dosen Pembimbing Skripsi</h6>
                      <span class="text-muted fs-12">Pilih Dosen Pembimbing 1 dan 2 dari daftar dosen yang tersedia</span>
                    </div>
                  </div>

                  <div class="row g-3">
                    <!-- Dospem 1 -->
                    <div class="col-md-6">
                      <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                        <label class="form-label fw-semibold text-dark fs-13 mb-1">
                          Cari Dosen Pembimbing 1 (Utama) <span class="text-danger">*</span>
                        </label>
                        <div class="mb-2">
                          <Multiselect
                            :options="optionsDosen1"
                            v-model="listDosen1"
                            :internal-search="false"
                            @search-change="getDosen1"
                            label="nama"
                            track-by="nama"
                            :searchable="true"
                            :loading="loadingDosen1"
                            :custom-label="customNameDosen"
                            placeholder="Ketik untuk mencari Dospem 1..."
                          ></Multiselect>
                        </div>
                        <label class="form-label small text-muted mb-1">Nama Pembimbing 1:</label>
                        <input
                          type="text"
                          v-model="form.dospem1"
                          class="form-control form-control-sm bg-white"
                          placeholder="Nama Pembimbing 1 terisi otomatis..."
                          readonly
                        />
                        <div class="form-text fs-11 text-muted mt-1">
                          Dosen Pembimbing 1 wajib dipilih.
                        </div>
                      </div>
                    </div>

                    <!-- Dospem 2 -->
                    <div class="col-md-6">
                      <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                        <label class="form-label fw-semibold text-dark fs-13 mb-1">
                          Cari Dosen Pembimbing 2 (Pendamping)
                        </label>
                        <div class="mb-2">
                          <Multiselect
                            :options="optionsDosen2"
                            v-model="listDosen2"
                            :internal-search="false"
                            @search-change="getDosen2"
                            label="nama"
                            track-by="nama"
                            :searchable="true"
                            :loading="loadingDosen2"
                            :custom-label="customNameDosen"
                            placeholder="Ketik untuk mencari Dospem 2 (opsional)..."
                          ></Multiselect>
                        </div>
                        <label class="form-label small text-muted mb-1">Nama Pembimbing 2:</label>
                        <input
                          type="text"
                          v-model="form.dospem2"
                          class="form-control form-control-sm bg-white"
                          placeholder="Nama Pembimbing 2 terisi otomatis (jika ada)..."
                          readonly
                        />
                        <div class="form-text fs-11 text-muted mt-1">
                          Kosongkan jika hanya memiliki 1 dosen pembimbing.
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <hr class="my-4 text-muted opacity-25" />

                <!-- 5. UPLOAD BERKAS PERSYARATAN SIDANG SKRIPSI -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold text-dark fs-14 mb-0">
                      <i class="ri-folder-upload-line me-1 text-primary"></i> Dokumen Berkas Persyaratan Sidang Skripsi
                    </label>
                    <span class="badge bg-primary-subtle text-primary fs-11">
                      Maksimal 20MB per berkas (PDF / Gambar / Doc)
                    </span>
                  </div>
                  <p class="text-muted fs-12 mb-3">
                    Unggah seluruh berkas persyaratan sidang di bawah ini. Pastikan format dokumen jelas dan terbaca.
                  </p>

                  <div class="document-list-container d-flex flex-column gap-3">
                    <div
                      v-for="(doc, idx) in documents"
                      :key="idx"
                      class="card border rounded-3 p-3 transition-hover"
                      :class="doc.file ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-white'"
                    >
                      <div class="row g-2 align-items-center">
                        <!-- Nomor & Nama Dokumen (Disabled Input) -->
                        <div class="col-md-5">
                          <label class="form-label fs-12 text-muted mb-1 d-flex align-items-center">
                            <span class="badge bg-secondary me-2">{{ idx + 1 }}</span>
                            <span>Nama Berkas Dokumen:</span>
                            <span v-if="doc.required" class="badge bg-danger ms-auto fs-10">Wajib</span>
                            <span v-else class="badge bg-secondary ms-auto fs-10">Opsional</span>
                          </label>
                          <input
                            type="text"
                            class="form-control form-control-sm bg-light fw-medium text-dark"
                            :value="doc.nama_dokumen"
                            disabled
                            readonly
                          />
                        </div>

                        <!-- File Input & Preview Status -->
                        <div class="col-md-7">
                          <label class="form-label fs-12 text-muted mb-1 d-flex justify-content-between">
                            <span>Pilih Berkas:</span>
                            <span v-if="doc.file" class="text-success fw-semibold">
                              <i class="ri-checkbox-circle-fill me-1"></i> Terpilih ({{ formatFileSize(doc.file.size) }})
                            </span>
                            <span v-else class="text-muted">Belum ada file dipilih</span>
                          </label>

                          <div class="input-group input-group-sm">
                            <input
                              type="file"
                              class="form-control form-control-sm"
                              :accept="doc.accept || '.pdf,.jpg,.jpeg,.png,.doc,.docx'"
                              @change="(e) => handleFileChange(e, idx)"
                              :ref="(el) => setFileInputRef(el, idx)"
                            />
                            <button
                              v-if="doc.file"
                              type="button"
                              class="btn btn-outline-danger btn-sm"
                              @click="removeFile(idx)"
                              title="Hapus berkas"
                            >
                              <i class="ri-delete-bin-line"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- TOMBOL SUBMIT -->
                <div class="d-grid mt-4 pt-2">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg rounded-3 fw-semibold shadow-sm py-3"
                    :disabled="submitting"
                  >
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i v-else class="ri-send-plane-fill me-2 align-middle"></i>
                    {{ submitting ? 'Mengirim Pendaftaran & Mengunggah Berkas...' : 'Kirim Pendaftaran Sidang Skripsi' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.min.css';
import { BASE_URL } from '../../../services/api/http';

export default defineComponent({
  name: 'PublicFormDaftarSidangSkripsi',
  components: {
    Multiselect,
  },
  setup() {
    const route = useRoute();
    const slug = (route.params.slug as string) || '';

    const API_BASE = BASE_URL || import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

    const waGroupHkiUrl = 'https://chat.whatsapp.com/J7r49DKbgwn2FwdAfSP9PO';

    const isManualProdi = ref(false);
    const prodiListOptions = [
      { id: 6, nama: 'Hukum Keluarga Islam', alias: 'HKI' },
      { id: 4, nama: 'Ekonomi Syariah', alias: 'ESY' },
      { id: 1, nama: 'Pendidikan Agama Islam', alias: 'PAI' },
      { id: 2, nama: 'Pendidikan Bahasa Arab', alias: 'PBA' },
      { id: 3, nama: 'Manajemen Pendidikan Islam', alias: 'MPI' },
      { id: 7, nama: 'Komunikasi dan Penyiaran Islam', alias: 'KPI' },
      { id: 8, nama: 'Bimbingan dan Konseling Islam', alias: 'BKI' },
      { id: 9, nama: 'Ilmu Al-Qur\'an dan Tafsir', alias: 'IAT' },
      { id: 10, nama: 'Sejarah Peradaban Islam', alias: 'SPI' },
    ];

    function toggleEditProdi() {
      isManualProdi.value = !isManualProdi.value;
    }

    function onManualProdiChange() {
      const selected = prodiListOptions.find((p) => p.nama === form.value.prodi);
      if (selected) {
        form.value.prodi_id = selected.id;
      }
    }

    function checkIsHki(prodiVal?: string | null, prodiIdVal?: number | string | null): boolean {
      if (prodiIdVal !== null && prodiIdVal !== undefined && Number(prodiIdVal) === 6) {
        return true;
      }
      if (!prodiVal || typeof prodiVal !== 'string') {
        return false;
      }
      const lower = prodiVal.toLowerCase().trim();
      if (!lower) return false;

      // Exact alias match
      if (lower === 'hki' || lower === 'as-hk' || lower === 'as-hki' || lower === 'as') {
        return true;
      }

      // Explicit full name match
      if (
        lower.includes('hukum keluarga islam') ||
        lower.includes('hukum keluarga') ||
        lower.includes('ahwal al-syakhsiyyah') ||
        lower.includes('ahwal syakhsiyyah') ||
        lower.includes('ahwal as-syakhsiyyah')
      ) {
        return true;
      }

      // Word boundary match for HKI (misal "Prodi HKI" atau "S1 HKI")
      if (/\bhki\b/.test(lower)) {
        return true;
      }

      return false;
    }

    const isHkiSelected = computed(() => {
      return checkIsHki(form.value.prodi, form.value.prodi_id);
    });

    const isSubmittedHki = computed(() => {
      return checkIsHki(submittedData.value?.prodi, submittedData.value?.prodi_id);
    });

    const loadingInit = ref(true);
    const isAvailable = ref(false);
    const settingInfo = ref<any>({});

    const form = ref<any>({
      nama: '',
      nim: '',
      nik: '',
      prodi: '',
      prodi_id: null,
      judul_skripsi: '',
      dospem1: '',
      dospem2: '',
      pembimbing1: '',
      pembimbing2: '',
      periode_sidang: new Date().toISOString().split('T')[0],
    });

    // 5 Dokumen Persyaratan Berkas Sidang Skripsi (nama dokumen disable)
    const documents = ref([
      {
        nama_dokumen: 'File Skripsi',
        required: true,
        accept: '.pdf,.doc,.docx',
        file: null as File | null,
      },
      {
        nama_dokumen: 'Lembar Konsultasi Dospem 1',
        required: true,
        accept: '.pdf,.jpg,.jpeg,.png',
        file: null as File | null,
      },
      {
        nama_dokumen: 'Lembar Konsultasi Dospem 2',
        required: false,
        accept: '.pdf,.jpg,.jpeg,.png',
        file: null as File | null,
      },
      {
        nama_dokumen: 'FILE FOTO 3X4 (BERDASI, BACKGROUND MERAH, TANPA AKSESORIS)',
        required: true,
        accept: '.jpg,.jpeg,.png',
        file: null as File | null,
      },
      {
        nama_dokumen: 'Scan Nota Pembimbing (Bukti ACC Skripsi)',
        required: true,
        accept: '.pdf,.jpg,.jpeg,.png',
        file: null as File | null,
      },
      {
        nama_dokumen: 'Scan Ijazah Asli',
        required: false,
        accept: '.pdf,.jpg,.jpeg,.png',
        file: null as File | null,
      },
    ]);

    const fileInputRefs = ref<any[]>([]);
    function setFileInputRef(el: any, index: number) {
      if (el) {
        fileInputRefs.value[index] = el;
      }
    }

    const submitting = ref(false);
    const submittedSuccess = ref(false);
    const submittedData = ref<any>({});

    // Pencarian Dosen Pembimbing (Dospem 1 & Dospem 2)
    const listDosen1 = ref<any>(null);
    const optionsDosen1 = ref<any[]>([]);
    const loadingDosen1 = ref(false);
    let timeoutDosen1: any = null;

    const listDosen2 = ref<any>(null);
    const optionsDosen2 = ref<any[]>([]);
    const loadingDosen2 = ref(false);
    let timeoutDosen2: any = null;

    function toTitleCase(text: string): string {
      return text
        .toLowerCase()
        .split(/\s+/)
        .map((w) => (w ? w.charAt(0).toUpperCase() + w.slice(1) : ''))
        .join(' ');
    }

    function formatNamaCapitalWithGelarString(name: string): string {
      const str = (name || '').trim().replace(/^[,\s]+|[,\s]+$/g, '');
      if (!str) return '';

      let gelarDepan = '';
      let namaTanpaGelarDepan = str;

      const prefixPattern = /^((?:(?:Prof|Drs|Dra|Dr|Ir|KH|K\.H|Ust|Ustadz|Gus)\.?\s*|(?:H|Hj)\.\s*)+)(.*)$/i;
      const matches = str.match(prefixPattern);
      if (matches && matches[2] && matches[2].trim()) {
        gelarDepan = matches[1].trim();
        namaTanpaGelarDepan = matches[2].trim();
      }

      let namaUtama = '';
      let gelarBelakang = '';

      if (namaTanpaGelarDepan.includes(',')) {
        const idx = namaTanpaGelarDepan.indexOf(',');
        namaUtama = namaTanpaGelarDepan.slice(0, idx).trim();
        gelarBelakang = namaTanpaGelarDepan.slice(idx + 1).replace(/^[,\s]+|[,\s]+$/g, '').trim();
      } else {
        const degreePattern = /\b((?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?)(?:[\s,]+(?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?))*)$/i;
        const dMatches = namaTanpaGelarDepan.match(degreePattern);
        if (dMatches && dMatches.index !== undefined) {
          namaUtama = namaTanpaGelarDepan.slice(0, dMatches.index).trim();
          gelarBelakang = namaTanpaGelarDepan.slice(dMatches.index).replace(/^[,\s]+|[,\s]+$/g, '').trim();
        } else {
          namaUtama = namaTanpaGelarDepan;
          gelarBelakang = '';
        }
      }

      let result = toTitleCase(namaUtama);

      if (gelarDepan) {
        let cleanDepan = gelarDepan.replace(/\s+/g, ' ');
        cleanDepan = cleanDepan.replace(/\b(prof|dr|drs|dra|ir|kh|ust|gus)\b/gi, (m) => {
          const lower = m.toLowerCase();
          const map: Record<string, string> = {
            prof: 'Prof',
            dr: 'Dr',
            drs: 'Drs',
            dra: 'Dra',
            ir: 'Ir',
            kh: 'K.H',
            ust: 'Ust',
            gus: 'Gus',
          };
          return map[lower] || (lower.charAt(0).toUpperCase() + lower.slice(1));
        });
        result = `${cleanDepan} ${result}`;
      }

      if (gelarBelakang) {
        result = `${result.replace(/^[,\s]+|[,\s]+$/g, '')}, ${gelarBelakang}`;
      }

      return result.trim();
    }

    function formatNamaDosen(dosen: any) {
      if (!dosen) return '';

      if (typeof dosen === 'string') {
        return formatNamaCapitalWithGelarString(dosen);
      }

      const gelarDepan = (dosen.gelar_depan || '').trim();
      const gelarBelakang = (dosen.gelar_belakang || '').trim();
      const rawNama = (dosen.nama_asli || dosen.nama || '').trim();

      if (dosen.nama_asli && (gelarDepan || gelarBelakang)) {
        let res = toTitleCase(dosen.nama_asli.trim());
        if (gelarDepan) {
          res = `${gelarDepan} ${res}`;
        }
        if (gelarBelakang) {
          const cleanBelakang = gelarBelakang.replace(/^[,\s]+|[,\s]+$/g, '');
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
      return params?.nidn ? `${nameWithTitle} - ${params.nidn}` : nameWithTitle;
    }

    function getDosen1(params: string) {
      clearTimeout(timeoutDosen1);
      const keyword = (params || '').trim();
      if (!keyword) {
        optionsDosen1.value = [];
        return;
      }

      timeoutDosen1 = setTimeout(async () => {
        try {
          loadingDosen1.value = true;
          const res = await axios.get(`${API_BASE}/public/daftar-sidang-skripsi/get-dosen`, {
            params: { search: keyword, slug: slug },
          });
          optionsDosen1.value = res.data?.data || [];
        } catch (e) {
          optionsDosen1.value = [];
        } finally {
          loadingDosen1.value = false;
        }
      }, 300);
    }

    function getDosen2(params: string) {
      clearTimeout(timeoutDosen2);
      const keyword = (params || '').trim();
      if (!keyword) {
        optionsDosen2.value = [];
        return;
      }

      timeoutDosen2 = setTimeout(async () => {
        try {
          loadingDosen2.value = true;
          const res = await axios.get(`${API_BASE}/public/daftar-sidang-skripsi/get-dosen`, {
            params: { search: keyword, slug: slug },
          });
          optionsDosen2.value = res.data?.data || [];
        } catch (e) {
          optionsDosen2.value = [];
        } finally {
          loadingDosen2.value = false;
        }
      }, 300);
    }

    watch(listDosen1, (val) => {
      if (val) {
        const formatted = formatNamaDosen(val);
        form.value.dospem1 = formatted;
        form.value.pembimbing1 = formatted;
      } else {
        form.value.dospem1 = '';
        form.value.pembimbing1 = '';
      }
    });

    watch(listDosen2, (val) => {
      if (val) {
        const formatted = formatNamaDosen(val);
        form.value.dospem2 = formatted;
        form.value.pembimbing2 = formatted;
      } else {
        form.value.dospem2 = '';
        form.value.pembimbing2 = '';
      }
    });

    // Pencarian Mahasiswa
    const searchMhsQuery = ref('');
    const mhsOptions = ref<any[]>([]);
    const loadingMhs = ref(false);
    const showMhsDropdown = ref(false);
    const loadingJudul = ref(false);
    let mhsTimeout: any = null;

    // Cek ketersediaan form publik
    async function checkFormAvailability() {
      try {
        loadingInit.value = true;
        const res = await axios.get(`${API_BASE}/public/daftar-sidang-skripsi/check/${slug}`);
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

    // Autocomplete Mahasiswa
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
          const res = await axios.get(`${API_BASE}/public/daftar-sidang-skripsi/get-mhs`, {
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

    async function selectMahasiswa(mhs: any) {
      form.value.nama = mhs.nama || mhs.nama_mhs || '';
      form.value.nim = mhs.nim || '';
      form.value.nik = mhs.nik || '';
      form.value.prodi =
        mhs.prodi_mhs ||
        mhs.nama_prodi ||
        (typeof mhs.prodi === 'object' ? mhs.prodi?.nama || mhs.prodi?.alias : mhs.prodi) ||
        mhs.alias_prodi ||
        '';
      form.value.prodi_id = mhs.prodi_id || (mhs.prodi && typeof mhs.prodi === 'object' ? mhs.prodi.id : null);

      searchMhsQuery.value = `${form.value.nama} (${form.value.nim})`;
      showMhsDropdown.value = false;

      // Ambil judul dari mhs jika ada
      const detectedJudul = mhs.judul || mhs.judul_skripsi || mhs.judul_proposal_skripsi;
      if (detectedJudul) {
        form.value.judul_skripsi = detectedJudul;
      }

      // Auto-fill Dospem 1 & Dospem 2 dari mhs jika ada
      if (mhs.dospem1) {
        form.value.dospem1 = mhs.dospem1;
        form.value.pembimbing1 = mhs.dospem1;
        listDosen1.value = { nama: mhs.dospem1, nama_gelar: mhs.dospem1 };
        optionsDosen1.value = [listDosen1.value];
      }
      if (mhs.dospem2) {
        form.value.dospem2 = mhs.dospem2;
        form.value.pembimbing2 = mhs.dospem2;
        listDosen2.value = { nama: mhs.dospem2, nama_gelar: mhs.dospem2 };
        optionsDosen2.value = [listDosen2.value];
      }

      if (form.value.nim) {
        // Coba lookup ke API getJudulByNimPublic
        try {
          loadingJudul.value = true;
          const resJudul = await axios.get(`${API_BASE}/public/daftar-sidang-skripsi/get-judul/${encodeURIComponent(form.value.nim)}`);
          if (resJudul.data?.status && resJudul.data?.data) {
            const dataPj = resJudul.data.data;
            if (!form.value.judul_skripsi && dataPj.judul_proposal_skripsi) {
              form.value.judul_skripsi = dataPj.judul_proposal_skripsi;
            }
            if (!form.value.dospem1 && dataPj.dospem1) {
              form.value.dospem1 = dataPj.dospem1;
              form.value.pembimbing1 = dataPj.dospem1;
              listDosen1.value = { nama: dataPj.dospem1, nama_gelar: dataPj.dospem1 };
              optionsDosen1.value = [listDosen1.value];
            }
            if (!form.value.dospem2 && dataPj.dospem2) {
              form.value.dospem2 = dataPj.dospem2;
              form.value.pembimbing2 = dataPj.dospem2;
              listDosen2.value = { nama: dataPj.dospem2, nama_gelar: dataPj.dospem2 };
              optionsDosen2.value = [listDosen2.value];
            }
          }
        } catch (e) {
          // ignore
        } finally {
          loadingJudul.value = false;
        }
      }
    }

    function handleFileChange(e: Event, index: number) {
      const target = e.target as HTMLInputElement;
      if (target.files && target.files.length > 0) {
        const file = target.files[0];
        if (file.size > 20 * 1024 * 1024) {
          Swal.fire({
            icon: 'warning',
            title: 'Ukuran Berkas Terlalu Besar',
            text: `Berkas ${file.name} melebihi batas maksimal 20MB.`,
          });
          target.value = '';
          return;
        }
        documents.value[index].file = file;
      } else {
        documents.value[index].file = null;
      }
    }

    function removeFile(index: number) {
      documents.value[index].file = null;
      if (fileInputRefs.value[index]) {
        fileInputRefs.value[index].value = '';
      }
    }

    function formatFileSize(bytes: number): string {
      if (!bytes || bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function handleLogoError(e: Event) {
      const img = e.target as HTMLImageElement;
      img.style.display = 'none';
    }

    async function handleSubmit() {
      if (!form.value.nama || !form.value.nim) {
        Swal.fire({
          icon: 'warning',
          title: 'Data Belum Lengkap',
          text: 'Silakan cari dan pilih data mahasiswa terlebih dahulu!',
        });
        return;
      }

      if (!form.value.judul_skripsi || !form.value.judul_skripsi.trim()) {
        Swal.fire({
          icon: 'warning',
          title: 'Judul Skripsi Kosong',
          text: 'Judul skripsi wajib diisi!',
        });
        return;
      }

      if (!form.value.dospem1 || !form.value.dospem1.trim()) {
        Swal.fire({
          icon: 'warning',
          title: 'Dosen Pembimbing 1 Kosong',
          text: 'Silakan pilih Dosen Pembimbing 1 terlebih dahulu!',
        });
        return;
      }

      if (!form.value.periode_sidang) {
        Swal.fire({
          icon: 'warning',
          title: 'Periode Sidang Kosong',
          text: 'Silakan tentukan periode / tanggal pendaftaran sidang!',
        });
        return;
      }

      // Validasi berkas wajib
      const missingDocs: string[] = [];
      documents.value.forEach((d) => {
        if (d.required && !d.file) {
          missingDocs.push(d.nama_dokumen);
        }
      });

      if (missingDocs.length > 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Berkas Persyaratan Belum Lengkap',
          html: `<div class="text-start fs-14">Mohon unggah berkas wajib berikut:<ul class="mt-2 mb-0 ps-3">${missingDocs.map((m) => `<li><strong>${m}</strong></li>`).join('')}</ul></div>`,
        });
        return;
      }

      const confirm = await Swal.fire({
        title: 'Kirim Pendaftaran Sidang?',
        text: 'Pastikan data dan seluruh berkas yang Anda unggah sudah benar dan sesuai persyaratan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Kirim Sekarang',
        cancelButtonText: 'Periksa Kembali',
      });

      if (!confirm.isConfirmed) return;

      try {
        submitting.value = true;

        const formData = new FormData();
        formData.append('nama', form.value.nama);
        formData.append('nim', form.value.nim);
        if (form.value.nik) formData.append('nik', form.value.nik);
        formData.append('prodi', form.value.prodi || '');
        if (form.value.prodi_id) formData.append('prodi_id', form.value.prodi_id);
        formData.append('judul_skripsi', form.value.judul_skripsi);
        formData.append('periode_sidang', form.value.periode_sidang);
        if (form.value.dospem1) {
          formData.append('dospem1', form.value.dospem1);
          formData.append('pembimbing1', form.value.dospem1);
        }
        if (form.value.dospem2) {
          formData.append('dospem2', form.value.dospem2);
          formData.append('pembimbing2', form.value.dospem2);
        }

        // Berkas dokumen
        documents.value.forEach((doc) => {
          if (doc.file) {
            formData.append('dokumen_files[]', doc.file);
            formData.append('dokumen_names[]', doc.nama_dokumen);
          }
        });

        const res = await axios.post(
          `${API_BASE}/public/daftar-sidang-skripsi/submit/${slug}`,
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          }
        );

        if (res.data?.status) {
          submittedSuccess.value = true;
          submittedData.value = res.data.data || {
            nama: form.value.nama,
            nim: form.value.nim,
            nik: form.value.nik,
            prodi: form.value.prodi,
            prodi_id: form.value.prodi_id,
            judul_skripsi: form.value.judul_skripsi,
            dospem1: form.value.dospem1,
            dospem2: form.value.dospem2,
            pembimbing1: form.value.dospem1,
            pembimbing2: form.value.dospem2,
            periode_sidang: form.value.periode_sidang,
            dokumen: res.data.data?.dokumen || [],
          };
          window.scrollTo({ top: 0, behavior: 'smooth' });

          const isHki = checkIsHki(submittedData.value?.prodi, submittedData.value?.prodi_id);
          if (isHki) {
            // Membuka halaman baru untuk bergabung ke Grup WhatsApp HKI
            try {
              window.open(waGroupHkiUrl, '_blank');
            } catch (e) {}

            Swal.fire({
              icon: 'success',
              title: 'Pendaftaran Berhasil Dikirim!',
              html: `
                <p class="fs-15 mb-3">Terima kasih <strong>${form.value.nama || ''}</strong>, pendaftaran sidang Anda telah berhasil kami terima.</p>
                <div class="alert alert-success text-start p-3 rounded-3 border-0" style="background-color: #e8f8ef;">
                  <div class="d-flex align-items-center mb-1 text-success fw-bold">
                    <i class="ri-whatsapp-line me-2 fs-18"></i> Khusus Mahasiswa Prodi HKI:
                  </div>
                  <p class="mb-2 fs-13 text-dark">
                    Halaman baru untuk bergabung ke <strong>Grup WhatsApp Sidang Skripsi HKI</strong> telah dibuka. Jika halaman belum terbuka otomatis, silakan klik tombol di bawah ini:
                  </p>
                  <div class="text-center pt-1">
                    <a href="${waGroupHkiUrl}" target="_blank" class="btn btn-sm text-white rounded-pill px-3 fw-bold shadow-xs" style="background-color: #25D366;">
                      <i class="ri-whatsapp-line me-1"></i> Buka Grup WhatsApp HKI
                    </a>
                  </div>
                </div>
              `,
              confirmButtonText: 'Tutup & Lihat Ringkasan',
              confirmButtonColor: '#0d6efd',
            });
          } else {
            Swal.fire({
              icon: 'success',
              title: 'Pendaftaran Berhasil Dikirim!',
              text: `Terima kasih ${form.value.nama || ''}, pendaftaran sidang skripsi beserta seluruh berkas persyaratan Anda telah berhasil kami terima.`,
              confirmButtonText: 'Lihat Ringkasan',
              confirmButtonColor: '#0d6efd',
            });
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Mengirim',
            text: res.data?.message || 'Terjadi kesalahan saat memproses pendaftaran Anda.',
          });
        }
      } catch (error: any) {
        const errorMsg =
          error.response?.data?.message ||
          (error.response?.data?.errors
            ? Object.values(error.response.data.errors).flat().join('<br>')
            : 'Terjadi kesalahan pada server saat mengirim formulir.');

        Swal.fire({
          icon: 'error',
          title: 'Gagal Mengirim Formulir',
          html: errorMsg,
        });
      } finally {
        submitting.value = false;
      }
    }

    function resetForm() {
      form.value = {
        nama: '',
        nim: '',
        nik: '',
        prodi: '',
        prodi_id: null,
        judul_skripsi: '',
        dospem1: '',
        dospem2: '',
        pembimbing1: '',
        pembimbing2: '',
        periode_sidang: new Date().toISOString().split('T')[0],
      };
      listDosen1.value = null;
      listDosen2.value = null;
      optionsDosen1.value = [];
      optionsDosen2.value = [];
      searchMhsQuery.value = '';
      documents.value.forEach((d, idx) => {
        d.file = null;
        if (fileInputRefs.value[idx]) {
          fileInputRefs.value[idx].value = '';
        }
      });
      submittedSuccess.value = false;
      submittedData.value = {};
      checkFormAvailability();
    }

    onMounted(() => {
      checkFormAvailability();
    });

    return {
      slug,
      loadingInit,
      isAvailable,
      settingInfo,
      form,
      documents,
      setFileInputRef,
      submitting,
      submittedSuccess,
      submittedData,
      searchMhsQuery,
      mhsOptions,
      loadingMhs,
      showMhsDropdown,
      loadingJudul,
      handleSearchMhs,
      selectMahasiswa,
      handleFileChange,
      removeFile,
      formatFileSize,
      handleLogoError,
      handleSubmit,
      resetForm,
      listDosen1,
      optionsDosen1,
      loadingDosen1,
      getDosen1,
      listDosen2,
      optionsDosen2,
      loadingDosen2,
      getDosen2,
      formatNamaDosen,
      customNameDosen,
      waGroupHkiUrl,
      isManualProdi,
      prodiListOptions,
      toggleEditProdi,
      onManualProdiChange,
      checkIsHki,
      isHkiSelected,
      isSubmittedHki,
    };
  },
});
</script>

<style scoped>
.public-form-container {
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
  font-family: inherit;
}

.header-brand-banner {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}

.status-icon-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.autocomplete-dropdown {
  max-height: 280px;
  overflow-y: auto;
  z-index: 1050;
}

.autocomplete-item {
  transition: background-color 0.15s ease-in-out;
}

.autocomplete-item:hover {
  background-color: #f1f5f9;
}

.cursor-pointer {
  cursor: pointer;
}

.fs-10 {
  font-size: 10px;
}

.fs-11 {
  font-size: 11px;
}

.fs-12 {
  font-size: 12px;
}

.fs-13 {
  font-size: 13px;
}

.fs-14 {
  font-size: 14px;
}

.fs-15 {
  font-size: 15px;
}

.fs-22 {
  font-size: 22px;
}

@media (min-width: 768px) {
  .fs-md-26 {
    font-size: 26px;
  }
}

.transition-hover {
  transition: all 0.2s ease-in-out;
}
</style>

