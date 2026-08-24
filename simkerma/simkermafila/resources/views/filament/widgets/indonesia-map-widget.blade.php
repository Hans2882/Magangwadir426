<x-filament-widgets::widget>
    <x-filament::section>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: bold;">Peta Sebaran Kerjasama</h2>
            <button id="back-button" style="display: none; padding: 0.5rem 1rem; background-color: #113261; color: white; border-radius: 0.5rem; border: none; cursor: pointer;">Kembali ke Nasional</button>
        </div>

        <div 
            wire:ignore
            x-data="{
                map: null,
                provinceLayer: null,
                cityLayer: null,
                provinceData: @js($provinceData ?? []),

                normalizeName(name) {
                    name = name.toUpperCase();
                    const map = {
                        'PROBANTEN': 'BANTEN',
                        'DI. ACEH': 'ACEH',
                        'DAERAH ISTIMEWA YOGYAKARTA': 'DI YOGYAKARTA',
                        'NUSATENGGARA BARAT': 'NUSA TENGGARA BARAT',
                        'BANGKA BELITUNG': 'KEPULAUAN BANGKA BELITUNG',
                        'IRIAN JAYA TIMUR': 'PAPUA',
                        'IRIAN JAYA TENGAH': 'PAPUA TENGAH',
                        'IRIAN JAYA BARAT': 'PAPUA BARAT'
                    };
                    return map[name] || name;
                },
                
                async initMap() {
                    console.log('Map initialized with provinceData:', this.provinceData);
                    if (typeof L === 'undefined') {
                        let link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);
                        
                        let script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        document.head.appendChild(script);
                        
                        await new Promise(resolve => script.onload = resolve);
                    }

                    this.map = L.map('map', {
                        center: [-2.5, 118.0],
                        zoom: 5,
                        zoomControl: false,
                        attributionControl: false
                    });

                    L.control.zoom({ position: 'topright' }).addTo(this.map);

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(this.map);

                    await this.loadProvinces();

                    document.getElementById('back-button').addEventListener('click', async () => {
                        if (this.cityLayer) {
                            this.map.removeLayer(this.cityLayer);
                        }
                        this.provinceLayer.addTo(this.map);
                        this.map.setView([-2.5, 118.0], 5);
                        document.getElementById('back-button').style.display = 'none';
                    });
                },

                getColor(d) {
                    return d > 10 ? '#08519c' :
                           d >= 5  ? '#3182bd' :
                           d >= 1   ? '#6baed6' :
                                     '#eff3ff';
                },

                styleFeature(feature, dataMap, keyField) {
                    let name = this.normalizeName(feature.properties.Propinsi || feature.properties.PROVINSI || feature.properties.name || '');
                    let data = dataMap[name] || 0;
                    let count = (typeof data === 'object') ? (data.total || 0) : data;
                    return {
                        fillColor: this.getColor(count),
                        weight: 1,
                        opacity: 1,
                        color: '#000000',
                        fillOpacity: 0.8
                    };
                },

                async loadProvinces() {
                    const response = await fetch('/38 Provinsi Indonesia - Provinsi.json');
                    const geojson = await response.json();

                    this.provinceLayer = L.geoJSON(geojson, {
                        style: (feature) => this.styleFeature(feature, this.provinceData, 'Propinsi'),
                        onEachFeature: (feature, layer) => {
                            let name = this.normalizeName(feature.properties.Propinsi || feature.properties.PROVINSI || feature.properties.name || '');
                            let data = this.provinceData[name] || null;
                            let total = data ? data.total : 0;
                            let tooltipContent = `<b>${name}</b><br/>Total: <b>${total}</b>`;
                            if (data && total > 0) {
                                tooltipContent += `<br/><span style='color:#1d4ed8;'>MoU: ${data.mou_count}</span>`;
                                tooltipContent += `<br/><span style='color:#15803d;'>PKS: ${data.pks_count}</span>`;
                            }
                            
                            layer.bindTooltip(tooltipContent, { sticky: true });

                            layer.on({'click': (e) => this.drillDownToProvince(e, name) });
                        }
                    }).addTo(this.map);
                },

                async drillDownToProvince(e, provinceName) {
                    const cityData = await @this.getCityData(provinceName);
                    
                    this.map.fitBounds(e.target.getBounds(), { padding: [20, 20] });
                    
                    this.map.removeLayer(this.provinceLayer);
                    document.getElementById('back-button').style.display = 'block';

                    if (this.cityLayer) {
                        this.map.removeLayer(this.cityLayer);
                    }

                    try {
                        const response = await fetch('/38 Provinsi Indonesia - Kabupaten.json');
                        let geojson = await response.json();
                        
                        geojson.features = geojson.features.filter(f => {
                            let p = this.normalizeName(f.properties.WADMPR || '');
                            return p === provinceName || p.replace('-', ' ') === provinceName;
                        });

                        this.cityLayer = L.geoJSON(geojson, {
                            style: (feature) => {
                                let name = (feature.properties.WADMKK || '').toUpperCase();
                                let data = cityData[name] || cityData[name.replace('KABUPATEN ', '')] || cityData['KABUPATEN ' + name] || null;
                                let count = data ? (data.total || 0) : 0;
                                return {
                                    fillColor: this.getColor(count),
                                    weight: 1,
                                    opacity: 1,
                                    color: '#000000',
                                    fillOpacity: 0.8
                                };
                            },
                            onEachFeature: (feature, layer) => {
                                let name = (feature.properties.WADMKK || '').toUpperCase();
                                let dbName = name.startsWith('KOTA ') ? name : (name.startsWith('KABUPATEN ') ? name : 'KABUPATEN ' + name);
                                let data = cityData[name] || cityData[name.replace('KABUPATEN ', '')] || cityData['KABUPATEN ' + name] || null;
                                let total = data ? (data.total || 0) : 0;
                                let tooltipContent = `<b>${dbName}</b><br/>Total: <b>${total}</b>`;
                                if (data && total > 0) {
                                    tooltipContent += `<br/><span style='color:#1d4ed8;'>MoU: ${data.mou_count}</span>`;
                                    tooltipContent += `<br/><span style='color:#15803d;'>PKS: ${data.pks_count}</span>`;
                                }
                                layer.bindTooltip(tooltipContent, { sticky: true });
                            }
                        }).addTo(this.map);
                    } catch (err) {
                        console.error('Failed to load regency data', err);
                    }
                }
            }" 
            x-init="initMap()" 
            style="position: relative; width: 100%; height: 500px; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb; z-index: 10;"
        >
            <div id="map" style="width: 100%; height: 100%; z-index: 1;"></div>
            
            <button 
                id="back-button"
                style="display: none; position: absolute; top: 10px; left: 10px; z-index: 1000; padding: 5px 10px; background: white; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"
                onclick="this.parentElement.querySelector('#map')._leaflet_map.setView([-2.5, 118.0], 5); this.style.display='none';"
            >
                &larr; Kembali ke Nasional
            </button>

            <div style="position: absolute; bottom: 1rem; right: 1rem; background-color: rgba(255,255,255,0.9); padding: 0.75rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 1000; border: 1px solid #e5e7eb; pointer-events: none;">
                <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; color: #1f2937;">Jumlah Kerjasama</h4>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #374151; margin-bottom: 0.25rem;"><div style="width: 1rem; height: 1rem; background-color: #08519c;"></div> > 10</div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #374151; margin-bottom: 0.25rem;"><div style="width: 1rem; height: 1rem; background-color: #3182bd;"></div> 5 - 10</div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #374151; margin-bottom: 0.25rem;"><div style="width: 1rem; height: 1rem; background-color: #6baed6;"></div> 1 - 4</div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #374151;"><div style="width: 1rem; height: 1rem; background-color: #eff3ff;"></div> 0</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>