<!-- Document Auto-Fill Percentage Progress Bar & Modal Indicator (Official System Theme) -->
<div id="ai-autofill-loading-container" style="display: none;">
    <!-- Top Progress Bar (Follows Percentage) -->
    <div id="ai-top-bar-fill" class="ai-top-progress-bar" style="width: 0%;"></div>

    <!-- Centered Glass Card Overlay -->
    <div class="ai-loading-backdrop">
        <div class="ai-loading-card">
            <!-- Header -->
            <div class="ai-loading-header">
                <div class="ai-icon-wrapper" id="ai-icon-box">
                    <svg class="ai-spinner" viewBox="0 0 24 24" fill="none">
                        <circle class="ai-spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="ai-spinner-head" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <!-- Clean Document Scanning Icon (Neutral / Official) -->
                    <svg class="ai-doc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div class="ai-text-wrapper">
                    <h3 class="ai-loading-title">Memproses Dokumen</h3>
                    <p id="ai-progress-status" class="ai-loading-subtitle">Mempersiapkan berkas dokumen...</p>
                </div>
            </div>

            <!-- Percentage Counter and Stage Label -->
            <div class="ai-percent-row">
                <span id="ai-progress-step" class="ai-progress-step">Tahap 1 dari 4: Membaca berkas</span>
                <span id="ai-progress-percentage" class="ai-progress-percentage">0%</span>
            </div>

            <!-- Determinate Progress Bar Track -->
            <div class="ai-progress-track">
                <div id="ai-progress-bar-fill" class="ai-progress-bar-fill" style="width: 0%;"></div>
            </div>

            <!-- Footer Info -->
            <div class="ai-loading-footer">
                <span class="ai-footer-badge">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width: 13px; height: 13px; display: inline-block; vertical-align: -1px; margin-right: 3px;">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                    Pemrosesan Otomatis Dokumen
                </span>
                <span class="ai-footer-tip">Mohon jangan menutup halaman</span>
            </div>
        </div>
    </div>
</div>

<style>
    /* Top Progress Bar */
    .ai-top-progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        height: 4px;
        z-index: 9999999;
        background: linear-gradient(90deg, #113261, #1d4ed8, #2563eb, #3b82f6);
        transition: width 0.25s ease-out;
        box-shadow: 0 0 8px rgba(37, 99, 235, 0.6);
    }

    /* Backdrop Overlay */
    .ai-loading-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        animation: ai-fade-in 0.2s ease-out;
    }

    @keyframes ai-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Modal Card */
    .ai-loading-card {
        background: #ffffff;
        color: #0f172a;
        border-radius: 1rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        max-width: 450px;
        width: 100%;
        padding: 1.5rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: ai-scale-up 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .dark .ai-loading-card {
        background: #1e293b;
        color: #f8fafc;
        border-color: rgba(51, 65, 85, 0.9);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
    }

    @keyframes ai-scale-up {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Header */
    .ai-loading-header {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .ai-icon-wrapper {
        position: relative;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, 0.1);
        border-radius: 0.75rem;
        color: #2563eb;
        transition: background 0.3s, color 0.3s;
    }

    .ai-icon-wrapper.success {
        background: rgba(34, 197, 94, 0.15) !important;
        color: #16a34a !important;
    }

    .dark .ai-icon-wrapper {
        background: rgba(37, 99, 235, 0.2);
        color: #60a5fa;
    }

    .ai-spinner {
        position: absolute;
        width: 44px;
        height: 44px;
        animation: ai-spin 1s linear infinite;
    }

    .ai-spinner-track {
        opacity: 0.2;
    }

    .ai-spinner-head {
        opacity: 0.8;
    }

    .ai-doc-icon {
        width: 20px;
        height: 20px;
        z-index: 1;
    }

    @keyframes ai-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .ai-text-wrapper {
        flex: 1;
    }

    .ai-loading-title {
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0;
        line-height: 1.35;
        color: #0f172a;
    }

    .dark .ai-loading-title {
        color: #f8fafc;
    }

    .ai-loading-subtitle {
        font-size: 0.8rem;
        color: #64748b;
        margin: 0.25rem 0 0 0;
        line-height: 1.4;
    }

    .dark .ai-loading-subtitle {
        color: #94a3b8;
    }

    /* Percentage Row */
    .ai-percent-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-top: 1.25rem;
        margin-bottom: 0.4rem;
    }

    .ai-progress-step {
        font-size: 0.8rem;
        font-weight: 500;
        color: #2563eb;
    }

    .dark .ai-progress-step {
        color: #60a5fa;
    }

    .ai-progress-percentage {
        font-size: 1.15rem;
        font-weight: 700;
        color: #113261;
        font-variant-numeric: tabular-nums;
    }

    .dark .ai-progress-percentage {
        color: #93c5fd;
    }

    /* Horizontal Progress Track & Fill */
    .ai-progress-track {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
        margin-bottom: 0.85rem;
        position: relative;
    }

    .dark .ai-progress-track {
        background: #334155;
    }

    .ai-progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #113261, #1d4ed8, #2563eb);
        transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 8px rgba(37, 99, 235, 0.4);
    }

    .ai-progress-bar-fill.success {
        background: linear-gradient(90deg, #16a34a, #22c55e) !important;
        box-shadow: 0 0 8px rgba(34, 197, 94, 0.5) !important;
    }

    /* Footer Info */
    .ai-loading-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.75rem;
        color: #64748b;
    }

    .dark .ai-loading-footer {
        color: #94a3b8;
    }

    .ai-footer-badge {
        font-weight: 500;
        color: #1d4ed8;
    }

    .dark .ai-footer-badge {
        color: #60a5fa;
    }

    .ai-footer-tip {
        color: #94a3b8;
    }

    .dark .ai-footer-tip {
        color: #64748b;
    }
</style>

<script>
    document.addEventListener('livewire:init', () => {
        let isAutofillProcessing = false;
        let progressInterval = null;
        let currentPercent = 0;
        let autofillTriggered = false;

        const container = document.getElementById('ai-autofill-loading-container');
        const topBar = document.getElementById('ai-top-bar-fill');
        const barFill = document.getElementById('ai-progress-bar-fill');
        const percentText = document.getElementById('ai-progress-percentage');
        const statusText = document.getElementById('ai-progress-status');
        const stepLabel = document.getElementById('ai-progress-step');
        const iconBox = document.getElementById('ai-icon-box');

        // Track when user clicks "Mulai Proses" or "Mulai Ekstrak" on the confirmation modal
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (btn) {
                const text = (btn.innerText || btn.textContent || '').trim();
                if (text.includes('Mulai Proses') || text.includes('Mulai Ekstrak')) {
                    autofillTriggered = true;
                }
            }
        }, true);

        // Track when modal form is submitted directly (e.g. via Enter key)
        document.addEventListener('submit', (e) => {
            const modal = e.target.closest('.fi-modal');
            if (modal) {
                const text = modal.innerText || modal.textContent || '';
                if (text.includes('Ekstrak Data Otomatis') || text.includes('Auto-Fill Press Release') || text.includes('Mulai Proses') || text.includes('Mulai Ekstrak')) {
                    autofillTriggered = true;
                }
            }
        }, true);

        function updateProgressUI(percent, statusMsg, stepMsg) {
            currentPercent = Math.min(100, Math.max(0, percent));
            const rounded = Math.round(currentPercent);

            if (barFill) barFill.style.width = rounded + '%';
            if (topBar) topBar.style.width = rounded + '%';
            if (percentText) percentText.textContent = rounded + '%';
            if (statusText && statusMsg) statusText.textContent = statusMsg;
            if (stepLabel && stepMsg) stepLabel.textContent = stepMsg;
        }

        function startAutofillProgress() {
            if (isAutofillProcessing) return;
            isAutofillProcessing = true;

            if (container) {
                container.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            if (barFill) barFill.classList.remove('success');
            if (iconBox) iconBox.classList.remove('success');

            currentPercent = 5;
            updateProgressUI(5, 'Membaca berkas dokumen...', 'Tahap 1 dari 4: Membaca berkas');

            let elapsedMs = 0;
            const intervalStep = 150; // Update every 150ms

            clearInterval(progressInterval);
            progressInterval = setInterval(() => {
                if (!isAutofillProcessing) {
                    clearInterval(progressInterval);
                    return;
                }

                elapsedMs += intervalStep;

                // Tahap 1: 0 - 1.2 detik (5% -> 25%)
                if (elapsedMs < 1200) {
                    currentPercent += 2.5;
                    updateProgressUI(currentPercent, 'Membaca berkas dokumen...', 'Tahap 1 dari 4: Membaca berkas');
                }
                // Tahap 2: 1.2s - 3.5 detik (25% -> 55%)
                else if (elapsedMs < 3500) {
                    currentPercent += 1.8;
                    updateProgressUI(currentPercent, 'Mengekstrak teks & isi dokumen...', 'Tahap 2 dari 4: Ekstraksi Teks');
                }
                // Tahap 3: 3.5s - 8 detik (55% -> 80%)
                else if (elapsedMs < 8000) {
                    currentPercent += 0.8;
                    updateProgressUI(currentPercent, 'Menganalisis isi dokumen & mengekstrak data...', 'Tahap 3 dari 4: Analisis Data Dokumen');
                }
                // Tahap 4: 8s - 14 detik+ (80% -> 94%)
                else if (currentPercent < 94) {
                    currentPercent += 0.25;
                    updateProgressUI(currentPercent, 'Memetakan data mitra, wilayah, nomor surat, & jurusan...', 'Tahap 4 dari 4: Pemetaan Form');
                }
            }, intervalStep);
        }

        function finishAutofillProgress(isSuccess = true) {
            clearInterval(progressInterval);
            autofillTriggered = false;

            if (!isAutofillProcessing) return;

            if (isSuccess) {
                // Instantly reach 100%
                updateProgressUI(100, 'Ekstraksi selesai! Mengisi formulir...', 'Selesai 100%');
                if (barFill) barFill.classList.add('success');
                if (iconBox) iconBox.classList.add('success');

                // Keep 100% visible briefly so the user sees completion, then fade out
                setTimeout(() => {
                    isAutofillProcessing = false;
                    if (container) container.style.display = 'none';
                    document.body.style.overflow = '';
                }, 450);
            } else {
                updateProgressUI(currentPercent, 'Gagal memproses dokumen.', 'Gagal');
                setTimeout(() => {
                    isAutofillProcessing = false;
                    if (container) container.style.display = 'none';
                    document.body.style.overflow = '';
                }, 600);
            }
        }

        // Intercept Livewire network requests: ONLY trigger progress if "Mulai Proses" was clicked
        Livewire.hook('request', ({ payload, respond, succeed, fail }) => {
            const rawPayload = JSON.stringify(payload || {});

            const isOpeningModal = rawPayload.includes('mountFormComponentAction') || rawPayload.includes('mountAction');
            const isExecutingAction = rawPayload.includes('callMountedFormComponentAction') || rawPayload.includes('callMountedAction');
            const containsAutofill = rawPayload.includes('autofill') || rawPayload.includes('autofill_press_release');

            // Do NOT trigger when merely opening the modal dialog (mountFormComponentAction)
            if (isOpeningModal && !isExecutingAction && !autofillTriggered) {
                return;
            }

            // Trigger ONLY when executing the action (after pressing "Mulai Proses" / submitting confirmation)
            if (autofillTriggered || (isExecutingAction && containsAutofill)) {
                autofillTriggered = false;
                startAutofillProgress();

                if (typeof respond === 'function') {
                    respond(() => {
                        // Response received from server
                    });
                }
                if (typeof succeed === 'function') {
                    succeed(() => {
                        finishAutofillProgress(true);
                    });
                }
                if (typeof fail === 'function') {
                    fail(() => {
                        finishAutofillProgress(false);
                    });
                }
            }
        });

        // Safety safeguard on commit completion
        Livewire.hook('commit', ({ succeed }) => {
            if (typeof succeed === 'function') {
                succeed(() => {
                    if (isAutofillProcessing) {
                        finishAutofillProgress(true);
                    }
                });
            }
        });
    });
</script>
