import { ref, watch, onUnmounted } from 'vue';
import jsQR from 'jsqr';

const SCAN_INTERVAL_MS = 200;
const REPEAT_COOLDOWN_MS = 2500;

/**
 * Requests the camera, decodes QR codes from live video frames, and calls
 * onDecode(text) with a cooldown so the same code isn't re-fired every frame.
 * `active` must be a ref<boolean>; the camera stream is (re)started only
 * when it changes.
 */
export function useQrScanner(onDecode, active) {
    const videoRef = ref(null);
    const error = ref(null);
    const ready = ref(false);

    let canvasEl = null;
    let stream = null;
    let intervalId = null;
    let last = { text: null, at: 0 };
    // Bumped on every start/stop so a getUserMedia() resolution that arrives
    // after the scanner was already stopped (or restarted) is discarded.
    let generation = 0;

    function teardown() {
        generation++;
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
        stream?.getTracks().forEach((t) => t.stop());
        stream = null;
    }

    async function start() {
        teardown();
        const myGeneration = generation;
        if (!canvasEl) canvasEl = document.createElement('canvas');
        error.value = null;
        ready.value = false;

        if (!navigator.mediaDevices?.getUserMedia) {
            error.value = "Camera access isn't available on this browser or device.";
            return;
        }

        try {
            const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            if (myGeneration !== generation) {
                s.getTracks().forEach((t) => t.stop());
                return;
            }
            stream = s;
            if (videoRef.value) {
                videoRef.value.srcObject = s;
                videoRef.value.play().catch(() => {});
            }
            ready.value = true;
            intervalId = setInterval(() => {
                const video = videoRef.value;
                const canvas = canvasEl;
                if (!video || !canvas || video.readyState !== video.HAVE_ENOUGH_DATA) return;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                if (!ctx) return;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                if (!code) return;
                const now = Date.now();
                if (code.data === last.text && now - last.at < REPEAT_COOLDOWN_MS) return;
                last = { text: code.data, at: now };
                onDecode(code.data);
            }, SCAN_INTERVAL_MS);
        } catch (err) {
            if (myGeneration === generation) {
                error.value = err instanceof Error ? err.message : 'Camera access was denied or is unavailable.';
            }
        }
    }

    watch(
        active,
        (isActive) => {
            if (isActive) start();
            else teardown();
        },
        { immediate: true, flush: 'post' }
    );

    onUnmounted(teardown);

    return { videoRef, error, ready };
}
