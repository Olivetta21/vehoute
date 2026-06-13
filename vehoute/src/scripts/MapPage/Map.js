import MapApi from "./MapApis/MapApi_Google";

export default class Map {

    static async initAPI() {
        return await !MapApi.isAnyNotImplemented() && await MapApi.initAPI();
    }
    
    static initMap(map_el_div) {
        if (MapApi.isMapInitialized()) {
            const map_div = MapApi.getMapDiv();
            //definir a div passada para o div do mapa (o mapa não é um filho, ou seja, ele deve substituir o conteúdo da div passada);
            if (map_div) map_el_div.replaceWith(map_div);
            else {
                console.error("Failed to get map div");
                return false;
            }
            console.log("Map already initialized");
            return true;
        }
        MapApi.initializeMap(map_el_div);
        if (!MapApi.isMapInitialized()) {
            console.error("Failed to initialize the map");
            return false;
        }
        console.log("Map initialized");
        return true
    }

    static initTrack(coords) {
        if (!coords || coords.length < 1) {
            console.error("No coordinates provided for tracking");
            return false;
        }
        if (!MapApi.isMapInitialized()) {
            console.error("Map not initialized. Call initMap() first.");
            return false;
        }

        if (!MapApi.haveMarker()) {
            if (!MapApi.createMarker()) {
                console.error("Failed to create marker");
                return false;
            }
        }

        if (!MapApi.haveLine()) {
            if (!MapApi.createLine(coords)) {
                console.error("Failed to create line");
                return false;
            }
        } else {
            if (!MapApi.setLinePath(coords)) {
                console.error("Failed to set line path");
                return false;
            }
        }
        return true;
    }
    
    static setMarkerPosition(lat, lng) {
        return MapApi.setMarkerPosition(lat, lng);
    }

    static clearTrack() {
        return MapApi.cleanMarker() && MapApi.cleanLine();
    }
    
    static centerOn(lat, lng) {
        if (MapApi.isMapInitialized()) {
            if (!MapApi.panTo(lat, lng)) {
                console.error("Failed to pan to the specified location");
                return false;
            }
            if (MapApi.haveMarker()) {
                if (!MapApi.setMarkerPosition(lat, lng)) {
                    console.error("Failed to set marker position");
                    return false;
                }
            }
            return true;
        } else {
            console.error("Map not initialized");
            return false;
        }
    }

    static flyTo(lat, lng) {
        if (MapApi.isMapInitialized()) {
            if (!MapApi.flyTo(lat, lng)) {
                console.error("Failed to fly to the specified location");
                return false;
            }
            return true;
        } else {
            console.error("Map not initialized");
            return false;
        }
    }

}