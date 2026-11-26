<template>
    <div id="map-container">
        <div id="mapchartdivelement" class="colored-red"></div>
    </div>
    <div id="map-buttons">
        <div class="buttons-container">
            <div class="loc-navigation-buttons">
                <button @click="avanceLocation(-1)"> {{ '<' }} </button>
                <button @click="gotoLastLocation"> O </button>
                <button @click="avanceLocation(1)"> {{ '>' }} </button>
            </div>
            <div class="tracker-info-buttons">
                <div class="tracker-location-container">
                    <div class="tracker">
                        <select v-model="tracker_selected_id" >
                            <option :value="null">Selecione um rastreador</option>
                            <option v-for="tracker in MapPagina.trackers" :key="tracker.id" :value="tracker.id">
                                {{ MapPagina._tracker_watching_id.value === tracker.id ? '🟢' : '' }} {{ tracker.name }}
                            </option>
                        </select>
                    </div>
                    <div class="location">
                        <select v-model="loc_selected_id" @change="updateLocation">
                            <option :value="null" v-if="!loc_selected_id">Sem localização</option>
                            <option v-for="loc in filtered_localizacao"
                                :key="loc.id" :value="loc.id">{{ loc.l_data }}:{{ loc.id }}</option>
                        </select>
                    </div>
                </div>
                <div class="filter-container">
                    <div>
                        de:
                        <input type="date" id="start-date" name="start-date" v-model="date_filter.start">
                    </div>
                    <div>
                        a:
                        <input type="date" id="end-date" name="end-date" v-model="date_filter.end">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FullScreenController from '../../scripts/PagesFrame/FullScreenController';
import MapPagina from '../../scripts/MapPage/Map';
import MapController from '../../scripts/MapPage/MapController';
import { getDateWithOffset } from '../../scripts/utils';

export default {
    name: 'MapPage',
    data() {
        return { 
            MapPagina,
            MapController,
            FullScreenController,
            filtered_localizacao: [],
            getDateWithOffset,
            date_filter: MapPagina._date_filter,
            tracker_selected_id: MapPagina._tracker_selected_id,
            loc_selected_id: null,
            loc_object_reference: MapPagina._actual_locs_reference

        }
    },
    async mounted() {
        const api = await MapController.initAPI();
        if (!api) {
            console.error("Failed to initialize map API");
            return;
        }
        const el = document.getElementById('mapchartdivelement');
        if (!el) {
            console.error("Map container element not found");
            return;
        }
        const map = await MapController.initMap(el);
        if (!map) {
            console.error("Failed to initialize map");
            return;
        }
        console.log("Fullscreen: ", FullScreenController.is_fullscreen);
        if (!MapPagina.already_confirmed_fullscreen && !FullScreenController.is_fullscreen && confirm("Deseja entrar em tela cheia para melhor visualização do mapa?")) {
            FullScreenController.toggleFullScreen();
        }
        MapPagina.already_confirmed_fullscreen = true;
    },
    methods: {
        filtrarLocalizacoes() {
            const size_before = this.filtered_localizacao.length;
            const is_last = this.filtered_localizacao.findIndex(loc => loc.id === this.loc_selected_id) === this.filtered_localizacao.length - 1;

            const filtered = MapPagina.get_localizacoes(this.tracker_selected_id).filter((caminho) => {
                const locDate = caminho.l_data.substring(0, 10);

                const startDate = this.date_filter.start
                    ? this.date_filter.start.split('T')[0]
                    : null;

                const endDate = this.date_filter.end
                    ? this.date_filter.end.split('T')[0]
                    : null;

                return (!startDate || locDate >= startDate) &&
                    (!endDate || locDate <= endDate);
            });

            if (filtered.length > 0) {
                MapController.initTrack(filtered);
            }
            else {
                MapController.clearTrack();
            }
            
            this.filtered_localizacao = filtered;

            if (filtered.length > 0) {
                if ((!this.loc_selected_id || !filtered.some(loc => loc.id === this.loc_selected_id)) || (is_last && filtered.length > size_before)) {
                    
                    if (!this.loc_selected_id) MapController.centerOn(filtered[filtered.length - 1].lat, filtered[filtered.length - 1].lng);
                    
                    this.loc_selected_id = filtered[filtered.length - 1].id;
                
                    this.updateLocation();
                }
            } else {
                this.loc_selected_id = null;
            }
        },
        gotoLastLocation() {
            this.loc_selected_id = this.filtered_localizacao[this.filtered_localizacao.length - 1]?.id || null;
            this.updateLocation();
        },
        avanceLocation(direction) {
            const newIndex = this.filtered_localizacao.findIndex(loc => loc.id === this.loc_selected_id) + direction;
            if (newIndex >= 0 && newIndex < this.filtered_localizacao.length) {
                this.loc_selected_id = this.filtered_localizacao[newIndex].id;
                this.updateLocation();
            }
        },
        updateLocation() {
            const loc = this.filtered_localizacao.find(loc => loc.id === this.loc_selected_id);
            if (!loc) {
                console.error("Selected location not found");
                return;
            }
            if (!MapController.flyTo(loc.lat, loc.lng)) {
                console.error("Failed to fly to selected location");
            }
        }
    },
    watch: {
        date_filter: {
            deep: true,
            handler() {
                this.filtrarLocalizacoes();
            }
        },
        tracker_selected_id: {
            handler() {
                this.loc_selected_id = null;
                this.filtrarLocalizacoes();
                MapPagina.setActualLocsReference(this.tracker_selected_id);
                if (this.tracker_selected_id !== null) MapPagina.loadLocations(this.tracker_selected_id);
                MapPagina.setWatchForTracker(this.tracker_selected_id);
            }
        },
        loc_object_reference: {
            deep: true,
            handler() {
                this.filtrarLocalizacoes();
            }
        }
    }
}
</script>

<style scoped>
#map-container,
#map-buttons { position: absolute; width: calc(100% - var(--sidebar-collapsed)); height: 100dvh; }
#map-buttons { pointer-events: none; }
.colored-red { z-index: 0; width: 100%; height: 100%; background: var(--color-surface-muted); }

.buttons-container {
    position: absolute;
    bottom: var(--space-5);
    left: 50%;
    z-index: 2;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: var(--space-3);
    width: max-content;
    max-width: calc(100% - var(--space-4));
    padding: var(--space-3);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    transform: translateX(-50%);
    pointer-events: all;
}
.loc-navigation-buttons { display: flex; gap: var(--space-2); }
.loc-navigation-buttons button { width: var(--control-height); padding: 0; color: var(--color-text); background: var(--color-surface); border-color: var(--color-border-strong); }
.loc-navigation-buttons button:hover { color: var(--color-primary); background: var(--color-primary-soft); border-color: var(--color-primary); }

.tracker-info-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-2); min-width: 0; }
.tracker-location-container,
.filter-container { display: flex; flex-direction: column; gap: var(--space-2); }
.filter-container { align-items: flex-end; }
.filter-container > div { display: flex; align-items: center; gap: var(--space-2); color: var(--color-text-muted); font-size: var(--font-xs); }
.tracker-location-container select { width: 13rem; }
input[type="date"],
select { min-height: var(--control-height-sm); }
</style>
