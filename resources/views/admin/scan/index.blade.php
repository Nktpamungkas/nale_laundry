<x-layouts.app :title="'Scan Station'">
    <div class="topbar">
        <h1>Scan Station Laundry</h1>
    </div>

    <div class="panel">
        <p class="muted">Scan QR dari struk/label laundry. Scanner USB dan kamera HP didukung.</p>

        <form method="POST" action="{{ route('admin.scan.update') }}" id="scan-form" class="row">
            @csrf
            <div class="field" style="flex:2; min-width: 260px;">
                <label>Nomor Order</label>
                <input id="order_number" type="text" name="order_number" value="{{ old('order_number') }}" placeholder="Scan / ketik nomor order" autocomplete="off" required>
            </div>
            <div class="field" style="flex:1; min-width: 220px;">
                <label>Status Baru</label>
                <select name="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $defaultStatus) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="flex:2; min-width: 240px;">
                <label>Catatan (opsional)</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="Contoh: Station cuci shift pagi">
            </div>
            <div class="field">
                <button class="btn" type="submit">Update Status</button>
            </div>
        </form>

        <div class="mt-12 row">
            <button id="start-camera" type="button" class="btn ghost">Mulai Scan Kamera</button>
            <button id="stop-camera" type="button" class="btn ghost">Stop Kamera</button>
            <label style="display:flex; align-items:center; gap:6px;"><input type="checkbox" id="auto-submit" value="1"> Auto submit setelah scan</label>
        </div>

        <div id="scan-feedback" class="mt-12" style="display:none; padding:10px 12px; border-radius:10px; font-size:14px;"></div>

        <div id="camera-wrapper" class="mt-12" style="display:none; max-width:520px; border:1px solid #d1d5db; border-radius:10px; overflow:hidden; background:#111;">
            <div id="camera-target" style="width:100%; min-height:300px;"></div>
            <div id="camera-fallback" style="display:none; padding:10px; color:#fff; font-size:13px; background:#1f2937;"></div>
        </div>
    </div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const orderInput = document.getElementById('order_number');
    const form = document.getElementById('scan-form');
    const startBtn = document.getElementById('start-camera');
    const stopBtn = document.getElementById('stop-camera');
    const autoSubmit = document.getElementById('auto-submit');
    const wrapper = document.getElementById('camera-wrapper');
    const fallback = document.getElementById('camera-fallback');
    const feedback = document.getElementById('scan-feedback');

    let html5Qr = null;
    let cameraRunning = false;
    let lastCode = '';
    let lastCodeAt = 0;

    orderInput.focus();

    function normalizeCode(raw) {
        return (raw || '').trim().toUpperCase();
    }

    function showFeedback(message, type) {
        feedback.style.display = 'block';
        feedback.textContent = message;

        if (type === 'success') {
            feedback.style.background = '#dcfce7';
            feedback.style.color = '#166534';
            feedback.style.border = '1px solid #86efac';
        } else if (type === 'error') {
            feedback.style.background = '#fee2e2';
            feedback.style.color = '#991b1b';
            feedback.style.border = '1px solid #fca5a5';
        } else {
            feedback.style.background = '#e2e8f0';
            feedback.style.color = '#0f172a';
            feedback.style.border = '1px solid #cbd5e1';
        }
    }

    function playSuccessBeep() {
        if (navigator.vibrate) {
            navigator.vibrate(120);
        }

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(950, audioContext.currentTime);

            gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.2, audioContext.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.22);

            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.22);
        } catch (error) {
            // Ignore audio errors on unsupported devices.
        }
    }

    function applyScannedCode(code) {
        const normalized = normalizeCode(code);
        if (!normalized) {
            return false;
        }

        const now = Date.now();
        if (normalized === lastCode && (now - lastCodeAt) < 1200) {
            return false;
        }

        lastCode = normalized;
        lastCodeAt = now;

        orderInput.value = normalized;
        playSuccessBeep();
        showFeedback('Scan berhasil: ' + normalized, 'success');

        if (autoSubmit.checked) {
            form.requestSubmit();
        } else {
            orderInput.focus();
        }

        return true;
    }

    async function startCamera() {
        if (cameraRunning) {
            return;
        }

        wrapper.style.display = 'block';
        fallback.style.display = 'none';

        if (typeof Html5Qrcode === 'undefined') {
            fallback.style.display = 'block';
            fallback.textContent = 'Library scanner QR tidak tersedia. Gunakan scanner USB.';
            return;
        }

        html5Qr = new Html5Qrcode('camera-target');

        const scannerConfig = {
            fps: 10,
            qrbox: { width: 220, height: 220 },
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        };

        const cameraCandidates = [
            { facingMode: 'environment' },
            { facingMode: { exact: 'environment' } },
            { facingMode: 'user' },
        ];

        let lastError = null;

        for (const cameraConfig of cameraCandidates) {
            try {
                await html5Qr.start(
                    cameraConfig,
                    scannerConfig,
                    (decodedText) => {
                        if (applyScannedCode(decodedText)) {
                            stopCamera(false);
                        }
                    },
                    () => {
                        // Ignore frame-level decode errors.
                    }
                );

                cameraRunning = true;
                showFeedback('Kamera aktif. Arahkan ke QR code.', 'info');
                return;
            } catch (error) {
                lastError = error;
            }
        }

        fallback.style.display = 'block';
        fallback.textContent = 'Gagal mengakses kamera: ' + (lastError?.message || lastError || 'Unknown error');
        showFeedback('Kamera gagal dijalankan.', 'error');
    }

    async function stopCamera(showInfo = true) {
        if (!cameraRunning || !html5Qr) {
            wrapper.style.display = 'none';
            fallback.style.display = 'none';
            return;
        }

        try {
            await html5Qr.stop();
            await html5Qr.clear();
        } catch (error) {
            // Ignore stop errors.
        }

        cameraRunning = false;
        html5Qr = null;
        wrapper.style.display = 'none';
        fallback.style.display = 'none';

        if (showInfo) {
            showFeedback('Kamera dihentikan.', 'info');
        }
    }

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    window.addEventListener('beforeunload', function () {
        if (cameraRunning) {
            stopCamera();
        }
    });

    orderInput.addEventListener('change', function () {
        this.value = normalizeCode(this.value);
    });
})();
</script>
</x-layouts.app>
