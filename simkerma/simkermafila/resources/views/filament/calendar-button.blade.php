<div
    x-data="{
        eventsUrl: @js(route('calendar.kerjasama')),
        open: false,
        month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        selected: new Date(),
        events: [],
        requestId: 0,
        loading: false,
        error: null,
        weekdays: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
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

            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + index);

                return {
                    date,
                    day: date.getDate(),
                    isCurrentMonth: date.getMonth() === this.month.getMonth(),
                    isToday: date.toDateString() === new Date().toDateString(),
                    isSelected: date.toDateString() === this.selected.toDateString(),
                    hasEvents: this.events.some((event) => event.date === this.dateKey(date)),
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

                if (!baseUrl) {
                    throw new Error('URL endpoint kalender tidak ditemukan.');
                }

                const response = await fetch(`${baseUrl}?month=${month}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.status === 401 || response.status === 403) {
                    throw new Error('Sesi login tidak valid. Silakan login ulang.');
                }

                if (!response.ok) {
                    throw new Error(`Data kerjasama gagal dimuat (HTTP ${response.status}).`);
                }

                const data = await response.json();

                if (!Array.isArray(data.events)) {
                    throw new Error('Format data kerjasama dari server tidak valid.');
                }

                if (requestId === this.requestId) {
                    this.events = data.events;
                }
            } catch (error) {
                if (requestId === this.requestId) {
                    this.error = error.message || 'Data kerjasama gagal dimuat.';
                }
            } finally {
                if (requestId === this.requestId) {
                    this.loading = false;
                }
            }
        },
        toggleCalendar() {
            this.open = !this.open;

            if (this.open) {
                this.loadEvents();
            }
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
        style="position: absolute; z-index: 50; top: calc(100% + 0.5rem); right: 0; width: 19rem; padding: 1rem; border: 1px solid rgb(229 231 235); border-radius: 0.75rem; background: white; box-shadow: 0 10px 25px rgb(0 0 0 / 0.12);"
        class="dark:border-gray-700 dark:bg-gray-900"
    >
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <button
                type="button"
                aria-label="Bulan sebelumnya"
                x-on:click="changeMonth(-1)"
                style="display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 0; border-radius: 0.375rem; background: transparent; color: inherit; cursor: pointer;"
                class="hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                <x-heroicon-o-chevron-left style="width: 1rem; height: 1rem;" />
            </button>
            <span x-text="title" style="font-size: 0.95rem; font-weight: 600; text-transform: capitalize;" class="text-gray-950 dark:text-white"></span>
            <button
                type="button"
                aria-label="Bulan berikutnya"
                x-on:click="changeMonth(1)"
                style="display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 0; border-radius: 0.375rem; background: transparent; color: inherit; cursor: pointer;"
                class="hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                <x-heroicon-o-chevron-right style="width: 1rem; height: 1rem;" />
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.25rem; text-align: center;">
            <template x-for="weekday in weekdays" :key="weekday">
                <span x-text="weekday" style="padding: 0.375rem 0; font-size: 0.7rem; font-weight: 600;" class="text-gray-500 dark:text-gray-400"></span>
            </template>
            <template x-for="day in days" :key="day.date.toISOString()">
                <button
                    type="button"
                    x-on:click="selectDate(day.date)"
                    x-bind:aria-label="new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(day.date)"
                    x-bind:aria-pressed="day.isSelected"
                    x-bind:style="`position: relative; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; justify-self: center; border: ${day.isToday && !day.isSelected ? '1px solid var(--primary-600, #113261)' : '1px solid transparent'}; border-radius: 9999px; background: ${day.isSelected ? 'var(--primary-600, #113261)' : 'transparent'}; color: ${day.isSelected ? 'white' : (day.isCurrentMonth ? 'inherit' : 'rgb(156 163 175)')}; font-size: 0.8rem; cursor: pointer;`"
                    class="hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    <span x-text="day.day"></span>
                    <span
                        x-show="day.hasEvents"
                        style="position: absolute; bottom: 0.15rem; left: 50%; width: 0.25rem; height: 0.25rem; border-radius: 9999px; background: #dc2626; transform: translateX(-50%);"
                    ></span>
                </button>
            </template>
        </div>

        <div style="margin-top: 0.75rem; border-top: 1px solid rgb(229 231 235); padding-top: 0.75rem;" class="dark:border-gray-700">
            <p x-text="selectedDateLabel" style="margin: 0 0 0.5rem; font-size: 0.8rem; font-weight: 600; text-transform: capitalize;" class="text-gray-700 dark:text-gray-200"></p>
            <p style="margin: 0 0 0.5rem; font-size: 0.7rem;" class="text-gray-500 dark:text-gray-400">
                <span style="display: inline-block; width: 0.4rem; height: 0.4rem; margin-right: 0.25rem; border-radius: 9999px; background: #dc2626;"></span>
                Tanggal bertanda memiliki kerjasama yang akan berakhir.
            </p>
            <p x-show="loading" style="margin: 0; font-size: 0.8rem;" class="text-gray-500 dark:text-gray-400">Memuat kerjasama...</p>
            <p x-show="error" x-text="error" style="margin: 0; font-size: 0.8rem; color: #dc2626;"></p>
            <p x-show="!loading && !error && selectedEvents.length === 0" style="margin: 0; font-size: 0.8rem;" class="text-gray-500 dark:text-gray-400">
                Tidak ada kerjasama berakhir pada tanggal ini.
            </p>
            <ul x-show="!loading && !error && selectedEvents.length > 0" style="display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none;">
                <template x-for="event in selectedEvents" :key="event.id">
                    <li style="font-size: 0.8rem;">
                        <p x-text="event.title || 'Kerjasama tanpa judul'" style="margin: 0; font-weight: 600;" class="text-gray-900 dark:text-white"></p>
                        <p x-text="event.partner || 'Mitra tidak tersedia'" style="margin: 0.125rem 0 0;" class="text-gray-500 dark:text-gray-400"></p>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>