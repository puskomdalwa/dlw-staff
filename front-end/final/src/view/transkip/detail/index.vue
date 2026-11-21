<script lang="ts">
import { defineComponent, onMounted, ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { apiGet, apiPost } from "../../../services/api/request";
import SimpleCardComponent from "../../../shared/components/@spk/simple-card.vue";
import { toast } from "vue3-toastify";

export default defineComponent({
  components: { SimpleCardComponent },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const id = route.params.id;

    const loading = ref(false);
    const saving = ref(false);
    const loadingNilai = ref(false);

    const mahasiswa = ref<any>(null);
    const transkip = ref<any>({
      ipk: null,
      judul_skripsi: "",
      predikat_kelulusan: ""
    });

    const nilaiSiakad = ref<any[]>([]);
    
    // Checkbox mapping array of booleans matching nilaiSiakad
    const selectedNilai = ref<boolean[]>([]);
    const selectAll = ref(false);

    async function getDetail() {
      try {
        loading.value = true;
        const res = await apiGet(`/transkip/${id}`);
        if (res.success && res.data?.status) {
          mahasiswa.value = res.data.data;
          
          if (res.data.data.transkip) {
            transkip.value.ipk = res.data.data.transkip.ipk;
            predikatKelulusan(res.data.data.transkip.ipk);
            transkip.value.judul_skripsi = res.data.data.transkip.judul_skripsi;
            // transkip.value.predikat_kelulusan = res.data.data.transkip.predikat_kelulusan;
          }
        }
      } catch (err) {
        toast.error("Gagal load data detail", { autoClose: 2000 });
      } finally {
        loading.value = false;
      }
    }

    const activeSource = ref<'siakad' | 'pddikti' | null>(null);

    const cardTitle = computed(() => {
      if (activeSource.value === 'siakad') {
        return 'Daftar Nilai <span class="badge bg-primary-transparent ms-2">Sumber: SIAKAD</span>';
      }
      if (activeSource.value === 'pddikti') {
        return 'Daftar Nilai <span class="badge bg-info-transparent ms-2">Sumber: PDDIKTI</span>';
      }
      return 'Daftar Nilai';
    });

    function predikatKelulusan(ipk: any) {
      if (ipk === null || ipk === undefined || ipk === '') {
        transkip.value.predikat_kelulusan = '';
        return;
      }
      const numIpk = Number(ipk);
      if (isNaN(numIpk) || numIpk <= 0) {
        transkip.value.predikat_kelulusan = '';
        return;
      }

      if (numIpk < 2.75) {
        transkip.value.predikat_kelulusan = 'Cukup';
      } else if (numIpk < 3.25) {
        transkip.value.predikat_kelulusan = 'Sangat Baik';
      } else if (numIpk < 3.75) {
        transkip.value.predikat_kelulusan = 'Sangat Memuaskan';
      } else {
        transkip.value.predikat_kelulusan = 'Pujian';
      }
    }

    watch(
      () => transkip.value.ipk,
      (newIpk) => {
        predikatKelulusan(newIpk);
      },
      { immediate: true, deep: true }
    );

    function onIpkChange(e: any) {
      const val = e?.target ? e.target.value : e;
      transkip.value.ipk = val;
      predikatKelulusan(val);
    }

    function normalizeNilaiItem(item: any) {
      return {
        ...item,
        kode_mk: item.kode_mk || item.kode_mata_kuliah || "-",
        nama_mk: item.nama_mk || item.nama_mata_kuliah || "-",
        sks_mk: item.sks_mk !== undefined ? item.sks_mk : (item.sks_mata_kuliah !== undefined ? item.sks_mata_kuliah : 0),
        smt_mk: item.smt_mk || item.id_periode || item.nama_semester || "-",
        nilai_akhir: item.nilai_akhir !== undefined ? item.nilai_akhir : (item.nilai_angka !== undefined ? item.nilai_angka : "-"),
        nilai_bobot: item.nilai_bobot !== undefined ? item.nilai_bobot : (item.nilai_indeks !== undefined ? item.nilai_indeks : 0),
        nilai_huruf: item.nilai_huruf || "-"
      };
    }

    async function loadNilaiSiakad() {
      try {
        loadingNilai.value = true;
        activeSource.value = 'siakad';
        const res = await apiGet(`/transkip/${id}/nilai`);
        if (res.success && res.data?.status) {
          const rawData = Array.isArray(res.data.data) ? res.data.data : [];
          nilaiSiakad.value = rawData.map(normalizeNilaiItem);
          
          // Match with saved nilai if any
          const savedNilai = mahasiswa.value?.nilai_mahasiswa || [];
          selectedNilai.value = nilaiSiakad.value.map((n: any) => {
            const found = savedNilai.find((sn: any) => sn.kode_mk === n.kode_mk);
            return found ? found.transkrip === 'Y' : true; // Default selected
          });
          
          updateSelectAllState();
          toast.success("Nilai dari SIAKAD berhasil dimuat", { autoClose: 2000 });
        } else {
          nilaiSiakad.value = [];
          selectedNilai.value = [];
          toast.warning(res.data?.message || "Tidak ada data nilai dari SIAKAD", { autoClose: 2000 });
        }
      } catch (err) {
        nilaiSiakad.value = [];
        selectedNilai.value = [];
        toast.error("Gagal memuat nilai dari SIAKAD", { autoClose: 2000 });
      } finally {
        loadingNilai.value = false;
      }
    }

    async function loadNilaiPddikti() {
      try {
        loadingNilai.value = true;
        activeSource.value = 'pddikti';
        const res = await apiGet(`/transkip/${id}/nilai-pddikti`);
        if (res.success && res.data?.status) {
          const rawData = Array.isArray(res.data.data) ? res.data.data : [];
          nilaiSiakad.value = rawData.map(normalizeNilaiItem);
          
          // Match with saved nilai if any
          const savedNilai = mahasiswa.value?.nilai_mahasiswa || [];
          selectedNilai.value = nilaiSiakad.value.map((n: any) => {
            const found = savedNilai.find((sn: any) => sn.kode_mk === n.kode_mk);
            return found ? found.transkrip === 'Y' : true; // Default selected
          });
          
          updateSelectAllState();

          // Isi data IPK dari PDDIKTI ke form transkrip
          if (res.data.ipk !== undefined && res.data.ipk !== null && res.data.ipk !== '') {
            transkip.value.ipk = res.data.ipk;
            predikatKelulusan(res.data.ipk);
          }

          // Isi data Judul Skripsi dari PDDIKTI ke form transkrip
          if (res.data.judul_skripsi) {
            transkip.value.judul_skripsi = res.data.judul_skripsi;
          }
        } else {
          nilaiSiakad.value = [];
          selectedNilai.value = [];
          const msg = res.data?.message || res.message || "Tidak ada data nilai dari PDDIKTI";
          toast.error(msg, { autoClose: 3500 });
        }
      } catch (err: any) {
        nilaiSiakad.value = [];
        selectedNilai.value = [];
        const msg = err?.response?.data?.message || err?.message || "Gagal memuat nilai dari PDDIKTI";
        toast.error(msg, { autoClose: 3500 });
      } finally {
        loadingNilai.value = false;
      }
    }

    function toggleSelectAll() {
      selectedNilai.value = selectedNilai.value.map(() => selectAll.value);
    }

    function updateSelectAllState() {
      selectAll.value = selectedNilai.value.every(v => v);
    }

    async function saveTranskipData() {
      try {
        saving.value = true;
        // 1. Save Transkip (IPK, Judul, Predikat)
        const payloadTranskip = {
          mahasiswa_id: id,
          ipk: transkip.value.ipk,
          judul_skripsi: transkip.value.judul_skripsi,
          predikat_kelulusan: transkip.value.predikat_kelulusan
        };
        const resTranskip = await apiPost('/transkip', payloadTranskip);
        
        if (resTranskip.success && resTranskip.data?.status) {
          transkip.value.predikat_kelulusan = resTranskip.data.data.predikat_kelulusan;
        }

        // 2. Save Nilai
        const tgl = new Date().toISOString().split('T')[0];
        const payloadNilai = {
          mahasiswa_id: id,
          tanggal_nilai: tgl,
          nilai: nilaiSiakad.value.map((n, i) => ({
            kode_mk: n.kode_mk,
            nama_mk: n.nama_mk,
            sks_mk: n.sks_mk,
            smt_mk: n.smt_mk,
            nilai_akhir: n.nilai_akhir,
            nilai_bobot: n.nilai_bobot,
            nilai_huruf: n.nilai_huruf,
            transkrip: selectedNilai.value[i] ? 'Y' : 'T'
          }))
        };

        const resNilai = await apiPost('/transkip/nilai', payloadNilai);
        
        if (resTranskip.success && resNilai.success) {
          toast.success("Data berhasil disimpan", { autoClose: 2000 });
          // Redirect to transkrip menu after a short delay to allow toast to show
          setTimeout(() => {
            router.push('/transkip');
          }, 1000);
        } else {
          toast.error("Terjadi kesalahan saat menyimpan", { autoClose: 2000 });
        }

      } catch (err) {
        toast.error("Gagal menyimpan", { autoClose: 2000 });
      } finally {
        saving.value = false;
      }
    }
    const printedNilai = computed(() => {
      return nilaiSiakad.value.filter((_, i) => selectedNilai.value[i]);
    });

    const leftColumnNilai = computed(() => {
      const half = Math.ceil(printedNilai.value.length / 2);
      return printedNilai.value.slice(0, half);
    });

    const rightColumnNilai = computed(() => {
      const half = Math.ceil(printedNilai.value.length / 2);
      return printedNilai.value.slice(half);
    });

    const calculateTotals = (items: any[]) => {
      return items.reduce(
        (acc, item) => {
          acc.sks += Number(item.sks_mk) || 0;
          // Asumsi mutu (M) = SKS * Bobot sesuai contoh tabel (misal 2 * 3.25 = 6.50)
          acc.mutu += (Number(item.sks_mk) || 0) * (Number(item.nilai_bobot) || 0);
          return acc;
        },
        { sks: 0, mutu: 0 }
      );
    };

    const leftTotals = computed(() => calculateTotals(leftColumnNilai.value));
    const rightTotals = computed(() => calculateTotals(rightColumnNilai.value));

    function printTranskrip() {
      if (transkip.value.ipk) {
        predikatKelulusan(transkip.value.ipk);
      }
      window.print();
    }

    onMounted(async () => {
      await getDetail();
      // Tabel sengaja dibiarkan kosong di awal sampai user menekan salah satu tombol nilai
    });

    return {
      mahasiswa,
      transkip,
      nilaiSiakad,
      selectedNilai,
      selectAll,
      loading,
      loadingNilai,
      saving,
      activeSource,
      cardTitle,
      loadNilaiSiakad,
      loadNilaiPddikti,
      toggleSelectAll,
      updateSelectAllState,
      saveTranskipData,
      printTranskrip,
      predikatKelulusan,
      onIpkChange,
      printedNilai,
      leftColumnNilai,
      rightColumnNilai,
      leftTotals,
      rightTotals
    };
  }
});
</script>

<template>
  <div class="container-fluid">
    <div class="d-print-none">
      <div class="d-md-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
      <h1 class="page-title fw-semibold fs-18 mb-0">Detail Transkrip</h1>
      <div class="ms-md-1 ms-0">
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Akademik</a></li>
            <li class="breadcrumb-item"><router-link to="/transkip">Transkrip Nilai</router-link></li>
            <li class="breadcrumb-item active" aria-current="page">Detail</li>
          </ol>
        </nav>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-else-if="mahasiswa">
      <div class="row">
        <!-- Info Mahasiswa & Form IPK -->
        <div class="col-xl-12">
          <SimpleCardComponent title="Informasi Transkrip">
            <div class="row gy-3">
              <div class="col-md-6">
                <table class="table table-borderless table-sm">
                  <tbody>
                    <tr>
                      <th width="150">NIM</th>
                      <td>: {{ mahasiswa.nim }}</td>
                    </tr>
                    <tr>
                      <th>Nama</th>
                      <td>: {{ mahasiswa.nama }}</td>
                    </tr>
                    <tr>
                      <th>Program Studi</th>
                      <td>: {{ mahasiswa.prodi?.nama }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Judul Skripsi</label>
                  <textarea class="form-control" v-model="transkip.judul_skripsi" rows="2"></textarea>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">IPK</label>
                    <input 
                      type="number" 
                      step="0.01" 
                      class="form-control" 
                      v-model="transkip.ipk" 
                      @input="onIpkChange"
                      @change="onIpkChange"
                      placeholder="Contoh: 3.75" 
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Predikat Kelulusan</label>
                    <input type="text" class="form-control bg-light fw-semibold" v-model="transkip.predikat_kelulusan" readonly placeholder="Otomatis terisi" />
                  </div>
                </div>
              </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button class="btn btn-primary" @click="saveTranskipData" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ri-save-line me-1"></i> Simpan
              </button>
              <button class="btn btn-success" @click="printTranskrip" :disabled="saving">
                <i class="ri-printer-line me-1"></i> Print Transkrip
              </button>
            </div>
          </SimpleCardComponent>
        </div>

        <!-- Tabel Nilai -->
        <div class="col-xl-12 mt-4">
          <SimpleCardComponent :title="cardTitle">
            <template #showheader>
              <div class="d-flex align-items-center gap-2">
                <button 
                  type="button" 
                  class="btn btn-sm" 
                  :class="activeSource === 'siakad' ? 'btn-primary' : 'btn-outline-primary'" 
                  @click="loadNilaiSiakad" 
                  :disabled="loadingNilai">
                  <span v-if="loadingNilai && activeSource === 'siakad'" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ri-database-2-line me-1"></i>
                  Nilai Siakad
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm" 
                  :class="activeSource === 'pddikti' ? 'btn-info text-white' : 'btn-outline-info'" 
                  @click="loadNilaiPddikti" 
                  :disabled="loadingNilai">
                  <span v-if="loadingNilai && activeSource === 'pddikti'" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ri-cloud-line me-1"></i>
                  Nilai PDDIKTI
                </button>
              </div>
            </template>

            <div v-if="loadingNilai" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
              <div class="mt-2 text-muted">Memuat data nilai dari {{ activeSource === 'siakad' ? 'SIAKAD' : 'PDDIKTI' }}...</div>
            </div>

            <div v-else-if="activeSource === null" class="text-center py-5">
              <div class="mb-3">
                <span class="avatar avatar-xl avatar-rounded bg-light text-primary">
                  <i class="ri-table-line fs-24"></i>
                </span>
              </div>
              <h6 class="fw-semibold mb-1">Tabel Nilai Masih Kosong</h6>
              <p class="text-muted mb-3">Silakan klik salah satu tombol di atas untuk menampilkan data nilai mahasiswa:</p>
              <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-primary btn-wave" @click="loadNilaiSiakad" :disabled="loadingNilai">
                  <i class="ri-database-2-line me-1"></i> Nilai Siakad
                </button>
                <button type="button" class="btn btn-info text-white btn-wave" @click="loadNilaiPddikti" :disabled="loadingNilai">
                  <i class="ri-cloud-line me-1"></i> Nilai PDDIKTI
                </button>
              </div>
            </div>

            <div v-else-if="nilaiSiakad.length === 0" class="text-center py-5 text-muted">
              <i class="ri-inbox-line fs-24 d-block mb-2"></i>
              Tidak ada data nilai ditemukan dari {{ activeSource === 'siakad' ? 'SIAKAD' : 'PDDIKTI' }} untuk mahasiswa ini.
            </div>

            <div class="table-responsive" v-else>
              <table class="table table-bordered table-striped table-hover mt-3">
                <thead class="table-primary">
                  <tr>
                    <th class="text-center" width="50">
                      <input class="form-check-input" type="checkbox" v-model="selectAll" @change="toggleSelectAll">
                    </th>
                    <th class="text-center">No</th>
                    <th>Kode MK</th>
                    <th>Nama MK</th>
                    <th class="text-center">SKS</th>
                    <th class="text-center">Smt</th>
                    <th class="text-center">N. Akhir</th>
                    <th class="text-center">N. Bobot</th>
                    <th class="text-center">Huruf</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in nilaiSiakad" :key="index">
                    <td class="text-center">
                      <input class="form-check-input" type="checkbox" v-model="selectedNilai[index]" @change="updateSelectAllState">
                    </td>
                    <td class="text-center">{{ index + 1 }}</td>
                    <td>{{ item.kode_mk }}</td>
                    <td>{{ item.nama_mk }}</td>
                    <td class="text-center">{{ item.sks_mk }}</td>
                    <td class="text-center">{{ item.smt_mk }}</td>
                    <td class="text-center">{{ item.nilai_akhir }}</td>
                    <td class="text-center">{{ item.nilai_bobot }}</td>
                    <td class="text-center">{{ item.nilai_huruf }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </SimpleCardComponent>
        </div>
      </div>
    </template>
    </div> <!-- End of d-print-none -->

    <!-- Print Area -->
    <div id="print-area" class="d-none d-print-block border border-dark bg-white" v-if="mahasiswa">
      <div class="print-header text-center fw-bold border-bottom border-dark p-2">
        <div class="mb-1">
          <img src="/logo_uii.png" alt="Logo UII" class="print-logo" />
        </div>
        <div>UNIVERSITAS ISLAM INTERNASIONAL DARULLUGHAH WADDA'WAH</div>
        <div>TRANSKRIP AKADEMIK</div>
        <div class="fw-normal">Nomor : _________________</div>
      </div>

      <div class="p-2 border-bottom border-dark">
        <table class="table-info-mahasiswa table-borderless">
          <tr>
            <td width="20%">Nama Mahasiswa</td>
            <td width="2%">:</td>
            <td width="28%">{{ mahasiswa?.nama }}</td>
            <td width="20%">Program Pendidikan</td>
            <td width="2%">:</td>
            <td width="28%">S1</td>
          </tr>
          <tr>
            <td>Nomor Pokok Mahasiswa</td>
            <td>:</td>
            <td>{{ mahasiswa?.nim }}</td>
            <td>Fakultas</td>
            <td>:</td>
            <td>{{ mahasiswa?.prodi?.fakultas_prodi?.fakultas?.nama || 'Tarbiyah' }}</td>
          </tr>
          <tr>
            <td>Nomor Ijazah Nasional</td>
            <td>:</td>
            <td>{{ mahasiswa?.nomor_ijazah_nasional }}</td>
            <td>Program Studi</td>
            <td>:</td>
            <td>{{ mahasiswa?.prodi?.nama }}</td>
          </tr>
          <tr>
            <td>Tempat, Tanggal Lahir</td>
            <td>:</td>
            <td>{{ mahasiswa?.tempat_lahir }}, {{ mahasiswa?.tgl_lahir }}</td>
            <td>Nomor SK BAN-PT</td>
            <td>:</td>
            <td>{{ mahasiswa?.nomor_sk_ban_pt || '10034/SK/BAN-PT/Ak/S/III/2022' }}</td>
          </tr>
          <tr>
            <td>Tanggal Kelulusan</td>
            <td>:</td>
            <td>{{ mahasiswa?.tanggal_sk_yudisium }}</td>
            <td></td><td></td><td></td>
          </tr>
        </table>
      </div>

      <div class="d-flex p-2 border-bottom border-dark print-tables-container">
        <!-- Left Table -->
        <table class="print-table w-50 me-1">
          <thead>
            <tr>
              <th rowspan="2" width="5%">NO</th>
              <th rowspan="2" width="60%">MATA KULIAH</th>
              <th rowspan="2" width="10%">SKS<br>S</th>
              <th colspan="2" width="25%">NILAI</th>
            </tr>
            <tr>
              <th>HM</th>
              <th>M</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in leftColumnNilai" :key="'l'+index">
              <td class="text-center">{{ index + 1 }}</td>
              <td>{{ item.nama_mk }}</td>
              <td class="text-center">{{ item.sks_mk }}</td>
              <td class="text-center">{{ item.nilai_huruf }}</td>
              <td class="text-center">{{ (Number(item.sks_mk) * Number(item.nilai_bobot)).toFixed(2) }}</td>
            </tr>
            <tr>
              <td colspan="2" class="text-end fw-bold"></td>
              <td class="text-center fw-bold">{{ leftTotals.sks }}</td>
              <td></td>
              <td class="text-center fw-bold">{{ leftTotals.mutu.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Right Table -->
        <table class="print-table w-50 ms-1">
          <thead>
            <tr>
              <th rowspan="2" width="5%">NO</th>
              <th rowspan="2" width="60%">MATA KULIAH</th>
              <th rowspan="2" width="10%">SKS<br>S</th>
              <th colspan="2" width="25%">NILAI</th>
            </tr>
            <tr>
              <th>HM</th>
              <th>M</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in rightColumnNilai" :key="'r'+index">
              <td class="text-center">{{ leftColumnNilai.length + index + 1 }}</td>
              <td>{{ item.nama_mk }}</td>
              <td class="text-center">{{ item.sks_mk }}</td>
              <td class="text-center">{{ item.nilai_huruf }}</td>
              <td class="text-center">{{ (Number(item.sks_mk) * Number(item.nilai_bobot)).toFixed(2) }}</td>
            </tr>
            <tr v-for="i in (leftColumnNilai.length - rightColumnNilai.length)" :key="'filler'+i">
              <td>&nbsp;</td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
              <td colspan="2" class="text-end fw-bold"></td>
              <td class="text-center fw-bold">{{ rightTotals.sks }}</td>
              <td></td>
              <td class="text-center fw-bold">{{ rightTotals.mutu.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Info -->
      <div class="print-footer p-2">
        <table class="w-100 table-borderless">
          <tr>
            <td width="20%">Indeks Prestasi Kumulatif</td>
            <td width="2%">:</td>
            <td width="48%">{{ transkip.ipk }}</td>
            <td width="30%" class="text-center">Bangil, {{ new Date().toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</td>
          </tr>
          <tr>
            <td>Predikat Kelulusan</td>
            <td>:</td>
            <td>{{ transkip.predikat_kelulusan }}</td>
            <td class="text-center">Dekan,</td>
          </tr>
          <tr>
            <td class="align-top">Judul Skripsi</td>
            <td class="align-top">:</td>
            <td class="align-top">{{ transkip.judul_skripsi }}</td>
            <td class="text-center" style="height: 65px; vertical-align: bottom;">
              <div class="fw-bold text-decoration-underline">{{ mahasiswa?.prodi?.fakultas_prodi?.fakultas?.dekan || 'Dr. Junaidi, M.Pd.I.' }}</div>
              <div>NIDN. {{ mahasiswa?.prodi?.fakultas_prodi?.fakultas?.nidn || '2118086802' }}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    size: A4 portrait;
    margin: 0; /* Atur ke 0 agar browser tidak memaksakan margin bawaan yang besar */
  }
  #print-area {
    width: 100%;
    padding: 0.5cm 1cm; /* Gunakan padding untuk mengatur jarak secara manual (0.5cm atas-bawah, 1cm kiri-kanan) */
    box-sizing: border-box;
    font-family: "Times New Roman", Times, serif;
    font-size: 10pt;
    color: #000;
  }
  .print-header {
    font-size: 14pt;
  }
  .print-logo {
    height: 65px;
    width: auto;
    object-fit: contain;
    margin-bottom: 4px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .table-info-mahasiswa {
    width: 100%;
    font-size: 10pt;
    font-weight: bold;
    margin: 0;
  }
  .table-info-mahasiswa td {
    padding: 2px 0;
  }
  .print-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 9pt;
  }
  .print-table th, .print-table td {
    border: 1px solid #000;
    padding: 2px 4px; /* Kurangi padding tabel sedikit agar lebih hemat tempat */
  }
  .print-table th {
    text-align: center;
    font-weight: bold;
    background-color: #f8f9fa !important;
    -webkit-print-color-adjust: exact;
  }
  .print-footer {
    font-size: 10pt;
    font-weight: bold;
  }
  .print-footer table td {
    padding: 2px;
  }
  .print-footer table {
    margin: 0;
  }
}
</style>

<style>
@media print {
  body, html {
    background-color: white !important;
  }
  .main-content, .app-content, .page, .page-container, .container-fluid {
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
