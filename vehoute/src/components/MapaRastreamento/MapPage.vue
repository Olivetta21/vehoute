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
                <button @click="MapPagina.insertLocation(
                    tracker_selected_id,
                    { lat: -23.54980 + Math.random() * 0.01, lng: -46.62950 + Math.random() * 0.01, id: new Date().getTime(), l_data: getDateWithOffset(-5) + ' 10:00:00' }
                    )"> + </button>
            </div>
            <div class="tracker-info-buttons">
                <div class="tracker-location-container">
                    <div class="tracker">
                        <select v-model="tracker_selected_id" >
                            <option value=null>Selecione um rastreador</option>
                            <option v-for="tracker in MapPagina.trackers" :key="tracker.id" :value="tracker.id">
                                {{ MapPagina._tracker_watching_id.value === tracker.id ? '🟢' : '' }} {{ tracker.name }}
                            </option>
                        </select>
                    </div>
                    <div class="location">
                        <select v-model="loc_selected_id" @change="updateLocation">
                            <option value=null v-if="!loc_selected_id">Sem localização</option>
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
import MapPagina from '../../scripts/MapPage/Map';
import MapController from '../../scripts/MapPage/MapController';
import { getDateWithOffset } from '../../scripts/utils';

export default {
    name: 'MapPage',
    data() {
        return { 
            MapPagina,
            MapController,
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
                MapPagina.loadLocations(this.tracker_selected_id);
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
    #map-container {
        height: calc(100vh - 100px);
        width: calc(100vw - 40px);
        position: absolute;
    }
    .colored-red { 
        width: 100%;
        height: 100%;
        background-color: wheat;
        z-index: 0;
    }

    #map-buttons {
        height: 100vh;
        width: calc(100vw - 40px);
        position: absolute;
        pointer-events: none;
    }

    .buttons-container {
        background-color: var(--colorD1);
        color: var(--colorA4);
        height: 100px;
        width: 100%;
        position: absolute;
        left: 50%;
        bottom: 0;
        padding: 5px;
        transform: translateX(-50%);
        pointer-events: all;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .tracker-info-buttons {
        display: flex;
        justify-content: space-between;
    }
    .tracker-location-container, .filter-container {
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
    }
    .filter-container {
        align-items: flex-end;
    }

    option {
        background-color: var(--colorD1);
        color: var(--colorA4);
    }


    .loc-navigation-buttons, .filter-container {
        display: flex;
        justify-content: space-evenly;
        flex: 1;
    }
    .loc-navigation-buttons button {
        padding: 0;
        width: 80px;
        font-size: 1.2rem;
        background-color: unset;
        color: var(--colorA4);
        border: 1px solid var(--colorA4);
        
    }
    
    input[type="date"],
    select {
        border: 1px solid var(--colorA4);
        background: unset;
        color: var(--colorA4);
        padding: 1px;
        border-radius: 5px;
    }

</style>