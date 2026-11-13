<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if ([500, 502, 503, 504].includes(status)) {
                    // Mencegah Livewire 3 membuka dialog iframe abu-abu/hitam satu layar penuh (showHtmlModal)
                    preventDefault();

                    // Tampilkan notifikasi toast Filament yang rapi & elegan di pojok kanan atas
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
    });
</script>
