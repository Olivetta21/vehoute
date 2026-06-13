import MapApi_Interface from "./MapApi_Interface.js";

export default class MapApi_OpenStreet extends MapApi_Interface {
    static map = null;
    static marker = null;
    static line = null;

    static async initAPI() {
        if (window.L) {
            return true;
        }

        return new Promise((resolve) => {
            // CSS do Leaflet
            if (!document.querySelector('link[href="https://unpkg.com/leaflet/dist/leaflet.css"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet/dist/leaflet.css';
                document.head.appendChild(link);
            }

            // Script do Leaflet
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet/dist/leaflet.js';
            
            script.onload = () => {
                resolve(true);
            };

            script.onerror = () => {
                console.error("[Leaflet Inicialização] Falha ao carregar o script do Leaflet.");
                resolve(false);
            };

            document.head.appendChild(script);
        });
    }

    static isMapInitialized() {
        return MapApi_OpenStreet.map !== null;
    }

    static initializeMap(map_el_div) {
        try {
            if (MapApi_OpenStreet.isMapInitialized()) {
                return true;
            }

            MapApi_OpenStreet.map = window.L.map(map_el_div).setView([-34, 150], 15);

            window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(MapApi_OpenStreet.map);

            return true;

        } catch (error) {
            console.error("Error instantiating map:", error);
        }

        return false;
    }

    static getMapDiv() {
        if (!MapApi_OpenStreet.isMapInitialized()) {
            console.error("Map not initialized");
            return null;
        }

        try {
            return MapApi_OpenStreet.map.getContainer();
        } catch (error) {
            console.error("Error getting map div:", error);
            return null;
        }
    }

    static haveMarker() {
        return MapApi_OpenStreet.marker !== null;
    }

    static createMarker() {
        if (!MapApi_OpenStreet.isMapInitialized() || MapApi_OpenStreet.haveMarker()) {
            console.error("Map not initialized or marker already exists");
            return false;
        }

        try {
            const blueIcon = window.L.divIcon({
                html: `<div style="
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    background: #00F;
                "></div>`,
                className: '',
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            });

            MapApi_OpenStreet.marker = window.L.marker([0, 0], {
                icon: blueIcon
            }).addTo(MapApi_OpenStreet.map);

            return true;

        } catch (error) {
            console.error("Error creating marker:", error);
        }

        return false;
    }

    static setMarkerPosition(lat, lng) {
        if (!MapApi_OpenStreet.haveMarker()) {
            console.error("Marker not initialized");
            return false;
        }

        try {
            MapApi_OpenStreet.marker.setLatLng([lat, lng]);
            return true;
        } catch (error) {
            console.error("Error setting marker position:", error);
        }

        return false;
    }

    static cleanMarker() {
        if (!MapApi_OpenStreet.haveMarker()) {
            return true;
        }

        try {
            MapApi_OpenStreet.map.removeLayer(MapApi_OpenStreet.marker);
            MapApi_OpenStreet.marker = null;
            return true;
        } catch (error) {
            console.error("Error cleaning marker:", error);
        }

        return false;
    }

    static haveLine() {
        return MapApi_OpenStreet.line !== null;
    }

    static createLine(coords) {
        if (!MapApi_OpenStreet.isMapInitialized() || MapApi_OpenStreet.haveLine()) {
            console.error("Map not initialized or line already exists");
            return false;
        }

        try {
            MapApi_OpenStreet.line = window.L.polyline(coords, {
                dashArray: '2, 8',
                weight: 4
            }).addTo(MapApi_OpenStreet.map);

            return true;

        } catch (error) {
            console.error("Error creating line:", error);
        }

        return false;
    }

    static setLinePath(coords) {
        if (!MapApi_OpenStreet.haveLine()) {
            console.error("Line not initialized");
            return false;
        }

        try {
            MapApi_OpenStreet.line.setLatLngs(coords);
            return true;
        } catch (error) {
            console.error("Error setting line path:", error);
        }

        return false;
    }

    static cleanLine() {
        if (!MapApi_OpenStreet.haveLine()) {
            return true;
        }

        try {
            MapApi_OpenStreet.map.removeLayer(MapApi_OpenStreet.line);
            MapApi_OpenStreet.line = null;
            return true;
        } catch (error) {
            console.error("Error cleaning line:", error);
        }

        return false;
    }

    static panTo(lat, lng) {
        if (!MapApi_OpenStreet.isMapInitialized()) {
            console.error("Map not initialized");
            return false;
        }

        try {
            MapApi_OpenStreet.map.panTo([lat, lng]);
            return true;
        } catch (error) {
            console.error("Error panning map:", error);
        }

        return false;
    }

    static flyTo(lat, lng) {
        if (!MapApi_OpenStreet.isMapInitialized()) {
            console.error("Map not initialized");
            return false;
        }

        try {
            MapApi_OpenStreet.map.flyTo([lat, lng]);
            return true;
        } catch (error) {
            console.error("Error flying to location:", error);
        }
        return false;
    }
}
