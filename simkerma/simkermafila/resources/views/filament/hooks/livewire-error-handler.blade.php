<script>
    document.addEventListener('livewire:init', () => {
        // Intercept network failures (500, 502, 503, 504) and suppress Livewire's black/grey full-screen iframe
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if ([500, 502, 503, 504].includes(status)) {
                    preventDefault();

                    if (typeof FilamentNotification !== 'undefined') {
                        new FilamentNotification()
                            .title(status === 504 ? 'Koneksi Server Timeout (504)' : `Terjadi Gangguan Server (${status})`)
                            .body(status === 504 
                                ? 'Proses ekstraksi AI membutuhkan waktu lebih lama dari batas koneksi server (100 detik). Silakan coba kembali beberapa saat lagi.' 
                                : 'Server sedang sibuk atau mengalami kendala sementara. Silakan coba kembali beberapa saat.')
                            .danger()
                            .duration(8000)
                            .send();
                    } else if (window.$wire && typeof window.$wire.dispatch === 'function') {
                        window.$wire.dispatch('notify', {
                            status: 'danger',
                            title: status === 504 ? 'Koneksi Server Timeout (504)' : `Terjadi Gangguan Server (${status})`,
                            body: status === 504 ? 'Proses melebihi batas waktu (100 detik).' : 'Server sedang sibuk.'
                        });
                    }
                }
            });
        });

        // Safeguard: Ensure modal backdrop overlay & scroll locks are cleanly restored after actions
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                setTimeout(() => {
                    const openModals = document.querySelectorAll('.fi-modal.fi-modal-open');
                    if (openModals.length === 0) {
                        if (document.body.style.overflow === 'hidden') {
                            document.body.style.overflow = '';
                        }
                        document.querySelectorAll('.fi-modal-close-overlay').forEach(el => {
                            if (!el.closest('.fi-modal-open')) {
                                el.style.pointerEvents = 'none';
                            }
                        });
                    }
                }, 350);
            });
        });
    });
</script>
