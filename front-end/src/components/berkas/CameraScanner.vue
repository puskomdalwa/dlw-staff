<script lang="ts" setup>
import { ref, onMounted, onUnmounted, watch } from "vue";

const props = defineProps<{
  show: boolean;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "captured", base64Image: string): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);

const stream = ref<MediaStream | null>(null);
const isCameraReady = ref(false);
const cameraError = ref<string | null>(null);
const hasCaptured = ref(false);
const capturedImage = ref<string | null>(null);
const processedImage = ref<string | null>(null);

// Scanner filters & adjustments
const brightness = ref(100); // 50 to 200%
const contrast = ref(100);   // 50 to 200%
const activePreset = ref<"normal" | "magic" | "bw" | "grayscale">("magic");

// Device camera switcher
const videoDevices = ref<MediaDeviceInfo[]>([]);
const currentDeviceId = ref<string>("");

async function initCamera() {
  cameraError.value = null;
  isCameraReady.value = false;
  hasCaptured.value = false;
  capturedImage.value = null;

  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    videoDevices.value = devices.filter((d) => d.kind === "videoinput");

    const constraints: MediaStreamConstraints = {
      video: currentDeviceId.value
        ? { deviceId: { exact: currentDeviceId.value } }
        : {
            facingMode: { ideal: "environment" },
            width: { ideal: 1920 },
            height: { ideal: 1080 },
          },
      audio: false,
    };

    if (stream.value) {
      stream.value.getTracks().forEach((track) => track.stop());
    }

    const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
    stream.value = mediaStream;

    if (videoRef.value) {
      videoRef.value.srcObject = mediaStream;
      videoRef.value.play();
      isCameraReady.value = true;
    }
  } catch (err: any) {
    console.error("Camera access error:", err);
    cameraError.value =
      err.name === "NotAllowedError"
        ? "Izin akses kamera ditolak. Silakan izinkan akses kamera di browser Anda."
        : "Kamera tidak ditemukan atau sedang digunakan oleh aplikasi lain.";
  }
}

function stopCamera() {
  if (stream.value) {
    stream.value.getTracks().forEach((track) => track.stop());
    stream.value = null;
  }
  isCameraReady.value = false;
}

async function switchCamera() {
  if (videoDevices.value.length < 2) return;
  const currentIndex = videoDevices.value.findIndex(
    (d) => d.deviceId === currentDeviceId.value
  );
  const nextIndex = (currentIndex + 1) % videoDevices.value.length;
  currentDeviceId.value = videoDevices.value[nextIndex].deviceId;
  await initCamera();
}

function capturePhoto() {
  if (!videoRef.value || !isCameraReady.value) return;

  const video = videoRef.value;
  const canvas = document.createElement("canvas");
  canvas.width = video.videoWidth || 1280;
  canvas.height = video.videoHeight || 720;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  // Draw frame
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  capturedImage.value = canvas.toDataURL("image/jpeg", 0.95);
  hasCaptured.value = true;

  // Apply default scanner preset (Magic Color)
  applyFilters();
}

function applyFilters() {
  if (!capturedImage.value) return;

  const img = new Image();
  img.onload = () => {
    const canvas = canvasRef.value || document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    // Build filter string based on preset & sliders
    let filterString = `brightness(${brightness.value}%) contrast(${contrast.value}%)`;

    if (activePreset.value === "magic") {
      // CamScanner Magic Color: high contrast, boosted brightness & saturation
      const magicBrightness = Math.round(brightness.value * 1.15);
      const magicContrast = Math.round(contrast.value * 1.35);
      filterString = `brightness(${magicBrightness}%) contrast(${magicContrast}%) saturate(120%)`;
    } else if (activePreset.value === "bw") {
      // High contrast Black & White for text readability
      const bwContrast = Math.round(contrast.value * 1.6);
      filterString = `grayscale(100%) contrast(${bwContrast}%) brightness(${brightness.value}%)`;
    } else if (activePreset.value === "grayscale") {
      filterString = `grayscale(100%) brightness(${brightness.value}%) contrast(${contrast.value}%)`;
    }

    ctx.filter = filterString;
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    processedImage.value = canvas.toDataURL("image/jpeg", 0.92);
  };
  img.src = capturedImage.value;
}

function setPreset(preset: "normal" | "magic" | "bw" | "grayscale") {
  activePreset.value = preset;
  if (preset === "normal") {
    brightness.value = 100;
    contrast.value = 100;
  } else if (preset === "magic") {
    brightness.value = 105;
    contrast.value = 120;
  } else if (preset === "bw") {
    brightness.value = 110;
    contrast.value = 135;
  } else if (preset === "grayscale") {
    brightness.value = 100;
    contrast.value = 110;
  }
  applyFilters();
}

function retake() {
  hasCaptured.value = false;
  capturedImage.value = null;
  processedImage.value = null;
  activePreset.value = "magic";
  brightness.value = 100;
  contrast.value = 100;
}

function confirmScan() {
  if (processedImage.value) {
    emit("captured", processedImage.value);
    closeModal();
  }
}

function closeModal() {
  stopCamera();
  emit("close");
}

function handleFileInput(e: Event) {
  const target = e.target as HTMLInputElement;
  if (!target.files || target.files.length === 0) return;

  const file = target.files[0];
  const reader = new FileReader();
  reader.onload = () => {
    capturedImage.value = reader.result as string;
    hasCaptured.value = true;
    applyFilters();
  };
  reader.readAsDataURL(file);
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      initCamera();
    } else {
      stopCamera();
    }
  }
);

onMounted(() => {
  if (props.show) {
    initCamera();
  }
});

onUnmounted(() => {
  stopCamera();
});
</script>

<template>
  <div v-if="show" class="scanner-backdrop">
    <div class="scanner-modal card custom-card shadow-lg">
      <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i class="ri-scan-2-line fs-20 text-primary"></i>
          <h5 class="modal-title fs-16 mb-0 text-white font-weight-bold">
            Scanner Dokumen Realtime (CamScanner)
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
      </div>

      <div class="card-body p-0 bg-black position-relative scanner-viewport-wrapper">
        <!-- Error State -->
        <div v-if="cameraError && !hasCaptured" class="p-4 text-center text-white my-auto">
          <i class="ri-error-warning-line text-warning display-4 mb-3"></i>
          <p class="text-danger fs-15">{{ cameraError }}</p>
          <p class="text-muted fs-13 mb-4">
            Anda tetap dapat memilih dokumen langsung dari galeri atau penyimpanan lokal komputer/HP.
          </p>
          <label class="btn btn-primary btn-wave">
            <i class="ri-folder-open-line me-1"></i> Pilih Foto dari Perangkat
            <input type="file" accept="image/*" class="d-none" @change="handleFileInput" />
          </label>
        </div>

        <!-- Realtime Video Camera Stream -->
        <div v-show="!hasCaptured && !cameraError" class="video-container">
          <video
            ref="videoRef"
            autoplay
            playsinline
            muted
            class="video-preview"
          ></video>

          <!-- Document Framing Guide Overlay -->
          <div class="document-guide-overlay">
            <div class="corner-border top-left"></div>
            <div class="corner-border top-right"></div>
            <div class="corner-border bottom-left"></div>
            <div class="corner-border bottom-right"></div>
            <div class="guide-text">
              Posisikan dokumen berkas tepat di dalam bingkai
            </div>
          </div>

          <!-- Top Toolbar for Camera -->
          <div class="camera-top-bar">
            <button
              v-if="videoDevices.length > 1"
              type="button"
              class="btn btn-sm btn-dark bg-opacity-75 text-white rounded-pill px-3"
              @click="switchCamera"
            >
              <i class="ri-camera-switch-line me-1"></i> Ganti Kamera
            </button>
            <label class="btn btn-sm btn-dark bg-opacity-75 text-white rounded-pill px-3 ms-auto">
              <i class="ri-image-add-line me-1"></i> Dari Galeri
              <input type="file" accept="image/*" class="d-none" @change="handleFileInput" />
            </label>
          </div>
        </div>

        <!-- Captured Image Preview & Editor -->
        <div v-show="hasCaptured" class="preview-container text-center py-2">
          <img
            :src="processedImage || capturedImage || ''"
            alt="Hasil Scan"
            class="captured-preview shadow"
          />
          <canvas ref="canvasRef" class="d-none"></canvas>
        </div>
      </div>

      <!-- Footer Controls -->
      <div class="card-footer bg-light p-3">
        <!-- Controls When In Camera Mode -->
        <div v-if="!hasCaptured && !cameraError" class="d-flex justify-content-center align-items-center gap-3">
          <button type="button" class="btn btn-secondary px-4" @click="closeModal">
            Batal
          </button>
          <button
            type="button"
            class="shutter-button shadow"
            title="Ambil Foto Dokumen"
            @click="capturePhoto"
          >
            <div class="shutter-inner"></div>
          </button>
        </div>

        <!-- Controls When In Preview / Editing Mode -->
        <div v-else-if="hasCaptured" class="edit-toolbar">
          <!-- Preset Filter Buttons -->
          <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
            <button
              type="button"
              class="btn btn-sm rounded-pill px-3"
              :class="activePreset === 'magic' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="setPreset('magic')"
            >
              <i class="ri-magic-line me-1"></i> Magic Color (CamScanner)
            </button>
            <button
              type="button"
              class="btn btn-sm rounded-pill px-3"
              :class="activePreset === 'bw' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="setPreset('bw')"
            >
              <i class="ri-contrast-drop-2-line me-1"></i> B&W Dokumen
            </button>
            <button
              type="button"
              class="btn btn-sm rounded-pill px-3"
              :class="activePreset === 'grayscale' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="setPreset('grayscale')"
            >
              <i class="ri-contrast-line me-1"></i> Grayscale
            </button>
            <button
              type="button"
              class="btn btn-sm rounded-pill px-3"
              :class="activePreset === 'normal' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="setPreset('normal')"
            >
              <i class="ri-image-line me-1"></i> Asli (Normal)
            </button>
          </div>

          <!-- Brightness & Contrast Sliders -->
          <div class="row g-3 align-items-center mb-3 px-2">
            <div class="col-md-6">
              <div class="d-flex justify-content-between fs-12 mb-1">
                <span><i class="ri-sun-line text-warning me-1"></i> Kecerahan (Brightness):</span>
                <span class="fw-semibold">{{ brightness }}%</span>
              </div>
              <input
                type="range"
                class="form-range"
                min="50"
                max="200"
                v-model.number="brightness"
                @input="applyFilters"
              />
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between fs-12 mb-1">
                <span><i class="ri-contrast-2-line text-primary me-1"></i> Kontras (Contrast):</span>
                <span class="fw-semibold">{{ contrast }}%</span>
              </div>
              <input
                type="range"
                class="form-range"
                min="50"
                max="200"
                v-model.number="contrast"
                @input="applyFilters"
              />
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <button type="button" class="btn btn-danger-light btn-wave" @click="retake">
              <i class="ri-camera-lens-line me-1"></i> Foto Ulang (Retake)
            </button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-secondary" @click="closeModal">
                Batal
              </button>
              <button type="button" class="btn btn-success btn-wave shadow-sm" @click="confirmScan">
                <i class="ri-check-line me-1"></i> Gunakan Hasil Scan Ini
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scanner-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 1rem;
}

.scanner-modal {
  width: 100%;
  max-width: 820px;
  max-height: 94vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 12px;
}

.scanner-viewport-wrapper {
  min-height: 440px;
  max-height: 60vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-container {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 440px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #000;
}

.video-preview {
  width: 100%;
  height: 100%;
  max-height: 56vh;
  object-fit: contain;
}

.document-guide-overlay {
  position: absolute;
  top: 10%;
  left: 10%;
  right: 10%;
  bottom: 10%;
  border: 1px dashed rgba(255, 255, 255, 0.45);
  pointer-events: none;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 12px;
}

.corner-border {
  position: absolute;
  width: 28px;
  height: 28px;
  border-color: #00d285;
  border-style: solid;
}

.top-left {
  top: -2px;
  left: -2px;
  border-width: 4px 0 0 4px;
}

.top-right {
  top: -2px;
  right: -2px;
  border-width: 4px 4px 0 0;
}

.bottom-left {
  bottom: -2px;
  left: -2px;
  border-width: 0 0 4px 4px;
}

.bottom-right {
  bottom: -2px;
  right: -2px;
  border-width: 0 4px 4px 0;
}

.guide-text {
  background: rgba(0, 0, 0, 0.65);
  color: #fff;
  font-size: 13px;
  padding: 4px 12px;
  border-radius: 20px;
}

.camera-top-bar {
  position: absolute;
  top: 12px;
  left: 12px;
  right: 12px;
  display: flex;
  justify-content: space-between;
  z-index: 10;
}

.preview-container {
  width: 100%;
  height: 100%;
  max-height: 56vh;
  overflow-y: auto;
  display: flex;
  align-items: center;
  justify-content: center;
}

.captured-preview {
  max-width: 90%;
  max-height: 52vh;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid #444;
}

.shutter-button {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #fff;
  border: 4px solid #00d285;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s ease;
}

.shutter-button:hover {
  transform: scale(1.05);
}

.shutter-inner {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #00d285;
}
</style>
