<template>
    <div id="map-container">
        <div id="mapchartdivelement" class="colored-red"></div>
    </div>
    <div id="map-buttons">
        <div class="buttons-container">
            <button @click="Maaapa.flyTo(-23.55052, -46.63331)">SP</button>
            <button @click="Maaapa.flyTo(-22.9068, -43.1729)">RJ</button>
            <button @click="Maaapa.flyTo(-19.9167, -43.9345)">BH</button>
            <button @click="Maaapa.flyTo(-15.7942, -47.8822)">BRA</button>
        </div>
    </div>
</template>

<script>
import Map from '@/scripts/MapPage/Map';

export default {
    name: 'MapPage',
    data() {
        return { 
            Maaapa: Map,
            caminho: [
                { lat: -23.55052, lng: -46.63331 },
                { lat: -23.55090, lng: -46.63280 },
                { lat: -23.55140, lng: -46.63230 },
                { lat: -23.55180, lng: -46.63170 },
                { lat: -23.55220, lng: -46.63110 },
                { lat: -23.55270, lng: -46.63050 },
                { lat: -23.55310, lng: -46.62990 }
            ]
        }
    },
    async mounted() {
        const api = await Map.initAPI();
        if (!api) {
            console.error("Failed to initialize map API");
            return;
        }
        const el = document.getElementById('mapchartdivelement');
        if (!el) {
            console.error("Map container element not found");
            return;
        }
        const map = await Map.initMap(el);
        if (!map) {
            console.error("Failed to initialize map");
            return;
        }

        if (!Map.initTrack(this.caminho)) {
            console.error("Failed to initialize track");
            return;
        }

        const panto_loc = this.caminho[0];
        if (!Map.centerOn(panto_loc.lat, panto_loc.lng)) {
            console.error("Failed to center map on initial location");
            return;
        }
    },
    methods: {
        // Métodos relacionados ao mapa podem ser adicionados aqui
    }
}
</script>

<style scoped>
    #map-container {
        height: calc(100vh - 100px);
        width: calc(100vw - 40px);
        position: absolute;
        border: 5px solid;
    }
    .colored-red { 
        width: 100%;
        height: 100%;
        background-color: wheat;
        z-index: 0;
        border: 1px solid white;
    }

    #map-buttons {
        height: 100vh;
        width: calc(100vw - 40px);
        position: absolute;
        border: 2px solid red;
        pointer-events: none;
    }

    .buttons-container {
        border: 2px solid lime;
        background-color: #ffffffa3;
        height: 100px;
        width: 100%;
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        pointer-events: all;
    }
</style>