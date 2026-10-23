@php
    try {
        $jenisDokumenOptions = \Illuminate\Support\Facades\Cache::remember(
            'calendar_jenis_dokumen_options',
            3600,
            fn () => \App\Models\JenisDokumen::query()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama])
                ->toArray()
        );
    } catch (\Throwable $e) {
        $jenisDokumenOptions = [
            ['id' => 1, 'nama' => 'MoU'],
            ['id' => 3, 'nama' => 'PKS/SPK'],
            ['id' => 4, 'nama' => 'IA'],
        ];
    }
@endphp

<div
    x-data="{
        eventsUrl: @js(route('calendar.kerjasama')),
        jenisDokumenOptions: @js($jenisDokumenOptions),

        open: false,
        mode: 'kerjasama',
        jenisDokumenId: '',

        month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        selected: new Date(),
        events: [],
        requestId: 0,
        loading: false,
        error: null,
        weekdays: ['S', 'S', 'R', 'K', 'J', 'S', 'M'],

        get title() {
            return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(this.month);
        },
        get selectedDateLabel() {
            return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(this.selected);
        },
        get selectedEvents() {
            return this.events.filter((event) => event.date === this.dateKey(this.selected));
        },
        get days() {
            const firstDay = new Date(this.month.getFullYear(), this.month.getMonth(), 1);
            const startOffset = (firstDay.getDay() + 6) % 7;
            const startDate = new Date(firstDay);
            startDate.setDate(firstDay.getDate() - startOffset);

            const todayKey = this.dateKey(new Date());

            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + index);
                const key = this.dateKey(date);
                const dayEvents = this.events.filter((event) => event.date === key);

                const hasOnlyPast = dayEvents.length > 0 && dayEvents.every((e) => {
                    if (typeof e.is_past === 'boolean') return e.is_past;
                    return e.date < todayKey;
                });

                return {
                    date,
                    day: date.getDate(),
                    isCurrentMonth: date.getMonth() === this.month.getMonth(),
                    isToday: date.toDateString() === new Date().toDateString(),
                    isSelected: date.toDateString() === this.selected.toDateString(),
                    hasEvents: dayEvents.length > 0,
                    hasPastOnly: hasOnlyPast,
                };
            });
        },
        dateKey(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        async loadEvents() {
            const requestId = ++this.requestId;
            this.loading = true;
            this.error = null;

            try {
                const month = `${this.month.getFullYear()}-${String(this.month.getMonth() + 1).padStart(2, '0')}`;
                const baseUrl = this.eventsUrl;

                if (!baseUrl) throw new Error('URL endpoint kalender tidak ditemukan.');

                const params = new URLSearchParams({ month, mode: this.mode });
                if (this.jenisDokumenId) {
                    params.set('jenis_dokumen_id', this.jenisDokumenId);
                }

                const response = await fetch(`${baseUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.status === 401 || response.status === 403) {
                    throw new Error('Sesi login tidak valid. Silakan login ulang.');
                }
                if (!response.ok) throw new Error(`Data gagal dimuat (HTTP ${response.status}).`);

                const data = await response.json();
                if (!Array.isArray(data.events)) throw new Error('Format data dari server tidak valid.');

                if (requestId === this.requestId) this.events = data.events;
            } catch (error) {
                if (requestId === this.requestId) this.error = error.message || 'Data gagal dimuat.';
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },
        toggleCalendar() {
            this.open = !this.open;
            if (this.open) this.loadEvents();
        },
        changeMonth(amount) {
            this.month = new Date(this.month.getFullYear(), this.month.getMonth() + amount, 1);
            this.loadEvents();
        },
        selectDate(date) {
            this.selected = date;
            if (date.getMonth() !== this.month.getMonth() || date.getFullYear() !== this.month.getFullYear()) {
                this.month = new Date(date.getFullYear(), date.getMonth(), 1);
                this.loadEvents();
            }
        },
        setMode(newMode) {
            if (this.mode === newMode) return;
            this.mode = newMode;
            this.loadEvents();
        },
        get emptyMessage() {
            return this.mode === 'kerjasama'
                ? 'Tidak ada kerjasama berakhir pada tanggal ini.'
                : 'Tidak ada dokumen dibuat pada tanggal ini.';
        },
        get legendMessage() {
            return this.mode === 'kerjasama'
                ? 'Tanggal bertanda memiliki kerjasama berakhir (merah = akan berakhir, abu = sudah berakhir).'
                : 'Tanggal bertanda memiliki dokumen kerjasama yang dibuat.';
        },
    }"
    x-on:keydown.escape.window="open = false"
    style="position: relative; display: flex; align-items: center;"
>
    <button
        type="button"
        aria-label="Buka kalender"
        aria-haspopup="dialog"
        x-bind:aria-expanded="open"
        x-on:click="toggleCalendar()"
        style="display: inline-flex; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; border: 0; border-radius: 0.5rem; background: transparent; color: rgb(107 114 128); cursor: pointer;"
        class="hover:bg-gray-100 dark:hover:bg-gray-800"
    >
        <x-heroicon-o-calendar-days style="width: 1.375rem; height: 1.375rem;" />
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        x-on:click.outside="open = false"
        role="dialog"
        aria-label="Kalender"
        style="position: absolute; z-index: 50; top: calc(100% + 0.5rem); right: 0; width: min(36rem, calc(100vw - 2rem)); padding: 0.875rem; border: 1px solid rgb(229 231 235); border-radius: 0.75rem; background: white; box-shadow: 0 10px 25px rgb(0 0 0 / 0.12);"
        class="dark:border-gray-700 dark:bg-gray-900"
    >
        {{-- Mode tabs --}}
        <div style="display: flex; gap: 0.25rem; margin-bottom: 0.625rem; padding: 0.25rem; border-radius: 0.5rem;" class="bg-gray-100 dark:bg-gray-800">
            <button
                type="button"
                x-on:click="setMode('kerjasama')"
                x-bind:style="`flex: 1; padding: 0.375rem 0.5rem; border: 0; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: all .15s; background: ${mode === 'kerjasama' ? 'white' : 'transparent'}; color: ${mode === 'kerjasama' ? 'var(--primary-600, #113261)' : 'inherit'}; box-shadow: ${mode === 'kerjasama' ? '0 1px 2px rgb(0 0 0 / 0.1)' : 'none'};`"
            >
                Kerjasama
            </button>
            <button
                type="button"
                x-on:click="setMode('tracking')"
                x-bind:style="`flex: 1; padding: 0.375rem 0.5rem; border: 0; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: all .15s; background: ${mode === 'tracking' ? 'white' : 'transparent'}; color: ${mode === 'tracking' ? 'var(--primary-600, #113261)' : 'inherit'}; box-shadow: ${mode === 'tracking' ? '0 1px 2px rgb(0 0 0 / 0.1)' : 'none'};`"
            >
                Tracking Dokumen
            </button>
        </div>

        {{-- Filter jenis --}}
        <div style="margin-bottom: 0.625rem;">
            <select
                x-model="jenisDokumenId"
                x-on:change="loadEvents()"
                style="width: 100%; padding: 0.3125rem 0.5rem; border: 1px solid rgb(229 231 235); border-radius: 0.375rem; font-size: 0.7rem; background: white; color: inherit; cursor: pointer;"
                class="dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option value="">Semua Jenis Dokumen</option>
                <template x-for="opt in jenisDokumenOptions" :key="opt.id">
                    <option :value="String(opt.id)" x-text="opt.nama"></option>
                </template>
            </select>
        </div>

        {{-- Two-column body --}}
        <div style="display: flex; gap: 0.75rem; align-items: stretch;">
            {{-- Kiri: kalender --}}
            <div style="flex-shrink: 0; width: 13.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <button type="button" aria-label="Bulan sebelumnya" x-on:click="changeMonth(-1)"
                        style="display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border: 0; border-radius: 0.375rem; background: transparent; color: inherit; cursor: pointer;"
                        class="hover:bg-gray-100 dark:hover:bg-gray-800">
                        <x-heroicon-o-chevron-left style="width: 0.875rem; height: 0.875rem;" />
                    </button>
                    <span x-text="title" style="font-size: 0.8rem; font-weight: 600; text-transform: capitalize;" class="text-gray-950 dark:text-white"></span>
                    <button type="button" aria-label="Bulan berikutnya" x-on:click="changeMonth(1)"
                        style="display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border: 0; border-radius: 0.375rem; background: transparent; color: inherit; cursor: pointer;"
                        class="hover:bg-gray-100 dark:hover:bg-gray-800">
                        <x-heroicon-o-chevron-right style="width: 0.875rem; height: 0.875rem;" />
                    </button>
                </div>

                {{-- Grid kalender --}}
                <div style="display: grid; grid-template-columns: repeat(7, 1.6rem); gap: 0.125rem; text-align: center; justify-content: center;">
                    <template x-for="(weekday, idx) in weekdays" :key="idx">
                        <span x-text="weekday" style="padding: 0.25rem 0; font-size: 0.65rem; font-weight: 600;" class="text-gray-500 dark:text-gray-400"></span>
                    </template>
                    <template x-for="day in days" :key="day.date.toISOString()">
                        <button
                            type="button"
                            x-on:click="selectDate(day.date)"
                            x-bind:aria-label="new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(day.date)"
                            x-bind:aria-pressed="day.isSelected"
                            x-bind:style="`position: relative; display: flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem; border: ${day.isToday && !day.isSelected ? '1px solid var(--primary-600, #113261)' : '1px solid transparent'}; border-radius: 9999px; background: ${day.isSelected ? 'var(--primary-600, #113261)' : 'transparent'}; color: ${day.isSelected ? 'white' : (day.isCurrentMonth ? 'inherit' : 'rgb(156 163 175)')}; font-size: 0.7rem; cursor: pointer;`"
                            class="hover:bg-gray-100 dark:hover:bg-gray-800"
                        >
                            <span x-text="day.day"></span>

                            <span
                                x-show="day.hasEvents && !day.hasPastOnly"
                                style="position: absolute; bottom: 0.1rem; left: 50%; width: 0.2rem; height: 0.2rem; border-radius: 9999px; background: #dc2626; transform: translateX(-50%); pointer-events: none;"
                            ></span>

                            <span
                                x-show="day.hasEvents && day.hasPastOnly"
                                style="position: absolute; bottom: 0.1rem; left: 50%; width: 0.2rem; height: 0.2rem; border-radius: 9999px; background: #9ca3af; transform: translateX(-50%); pointer-events: none;"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Kanan: daftar event --}}
            <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; border-left: 1px solid rgb(229 231 235); padding-left: 0.75rem;" class="dark:border-gray-700">
                <p x-text="selectedDateLabel" style="margin: 0 0 0.375rem; font-size: 0.75rem; font-weight: 600; text-transform: capitalize;" class="text-gray-700 dark:text-gray-200"></p>
                <p style="margin: 0 0 0.5rem; font-size: 0.65rem; line-height: 1.3;" class="text-gray-500 dark:text-gray-400">
                    <span x-text="legendMessage"></span>
                </p>

                <div style="flex: 1; min-height: 0; max-height: 18rem; overflow-y: auto; padding-right: 0.25rem;">
                    <p x-show="loading" style="margin: 0; font-size: 0.75rem;" class="text-gray-500 dark:text-gray-400">Memuat data...</p>
                    <p x-show="error" x-text="error" style="margin: 0; font-size: 0.75rem; color: #dc2626;"></p>
                    <p x-show="!loading && !error && selectedEvents.length === 0" x-text="emptyMessage" style="margin: 0; font-size: 0.75rem;" class="text-gray-500 dark:text-gray-400"></p>
                    <ul x-show="!loading && !error && selectedEvents.length > 0" style="display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none;">
                        <template x-for="event in selectedEvents" :key="event.id">
                            <li style="margin: 0;">
                                {{-- Bungkus dengan <a>: jika event.url ada -> clickable, jika tidak -> tetap tampil statis --}}
                                <a
                                    x-bind:href="event.url || null"
                                    x-bind:style="`display: block; font-size: 0.75rem; padding: 0.375rem 0.5rem; border: 1px solid rgb(243 244 246); border-radius: 0.375rem; text-decoration: none; color: inherit; ${event.url ? 'cursor: pointer;' : 'cursor: default;'}`"
                                    class="dark:border-gray-800"
                                    :class="event.url ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : ''"
                                    :title="event.url ? 'Buka detail' : ''"
                                >
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.25rem;">
                                        <span x-show="event.jenis_dokumen" x-text="event.jenis_dokumen"
                                            style="display: inline-block; font-size: 0.6rem; padding: 0.0625rem 0.3125rem; border-radius: 0.25rem; background: rgb(243 244 246); color: rgb(55 65 81); font-weight: 500;"
                                            class="dark:bg-gray-800 dark:text-gray-200"></span>
                                        <span x-show="mode === 'kerjasama' && event.is_past"
                                            style="display: inline-block; font-size: 0.6rem; padding: 0.0625rem 0.3125rem; border-radius: 0.25rem; background: rgb(254 226 226); color: rgb(153 27 27); font-weight: 500;"
                                            class="dark:bg-red-900/40 dark:text-red-200">
                                            Berakhir
                                        </span>
                                    </div>
                                    <p x-text="event.title || 'Tanpa judul'"
                                        x-bind:class="event.is_past && mode === 'kerjasama' ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white'"
                                        style="margin: 0; font-weight: 600; word-break: break-word;"></p>
                                    <p x-text="event.partner || 'Mitra tidak tersedia'"
                                        style="margin: 0.125rem 0 0; word-break: break-word;"
                                        class="text-gray-500 dark:text-gray-400"></p>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>