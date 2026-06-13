import MapApi_Interface from "./MapApi_Interface";


export default class MapApi_Google extends MapApi_Interface {
    static map = null;
	static marker = null;
    static line = null;
    
    static async initAPI() {
        if (window.google?.maps) {
            return true;
        }

        return new Promise((resolve) => {
            let settled = false;
            const originalConsoleError = console.error;
            const originalConsoleWarn = console.warn;
            const restoreConsole = () => {
                console.error = originalConsoleError;
                console.warn = originalConsoleWarn;
            };

            const settle = (val) => {
                if (!settled) {
                    settled = true;
                    restoreConsole(); // Devolve o console ao estado normal
                    delete window.gm_authFailure; 
                    resolve(val);
                }
            };

            // Monitora o que o Google tenta falar no console
            const interceptor = (...args) => {
                const msg = args.map(String).join(' ');
                // Se a mensagem contiver o padrão de erro crítico do Google Maps
                if (msg.includes('Google Maps JavaScript API error') || msg.includes('Google Maps JavaScript API warning')) {
                    console.log("[Google Maps Interceptor]", msg);
                    settle(false);
                }
            };

            console.error = function(...args) {
                interceptor(...args);
                originalConsoleError.apply(console, args);
            };

            console.warn = function(...args) {
                interceptor(...args);
                originalConsoleWarn.apply(console, args);
            };

            window.gm_authFailure = () => settle(false);

            const script = document.createElement('script');
            const apiKey = process.env.VUE_APP_GOOGLE_MAPS_API_KEY;
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&loading=async`;
            
            script.onload = () => {
                // Se der algum erro, o interceptor ali em cima vai pegar o erro antes desse tempo acabar.
                setTimeout(() => {
                    settle(true);
                }, 10000); 
            };

            script.onerror = () => settle(false);
            
            document.head.appendChild(script);
        });
    }

	
	static isMapInitialized() {
		return MapApi_Google.map !== null;
	}

	static initializeMap(map_el_div) {
        try {
            if (MapApi_Google.isMapInitialized()) {
                return true;
            }
            MapApi_Google.map = new window.google.maps.Map(map_el_div, {
                zoom: 2,
                center: { lat: -34, lng: 150 },
            });
            return true;
        } catch (error) {
            console.error("Error instantiating map:", error);
        }
        return false;
	}
	
	static getMapDiv() {
        if (!MapApi_Google.isMapInitialized()) {
            console.error("Map not initialized");
            return false;
        }
        try {
            return MapApi_Google.map.getDiv();
        } catch (error) {
            console.error("Error getting map div:", error);
        }
        return false;
	}
	
	static haveMarker(){
		return MapApi_Google.marker !== null;
	}
	
	static createMarker(){
        if (!MapApi_Google.isMapInitialized() || MapApi_Google.haveMarker()) {
            console.error("Map not initialized or marker already exists");
            return false;
        }

        try {
            MapApi_Google.marker = new window.google.maps.Marker({
                map: MapApi_Google.map,
                icon: {
                path: window.google.maps.SymbolPath.CIRCLE,
                scale: 6,
                fillColor: "#00F",
                fillOpacity: 1,
                strokeWeight: 0
                }
            });
            return true;
        } catch (error) {
            console.error("Error creating marker:", error);
        }
        return false;
    }

    static setMarkerPosition(lat, lng) {
        if (!MapApi_Google.haveMarker()) {
            console.error("Marker not initialized");
            return false;
        }
        try {
            MapApi_Google.marker.setPosition(new window.google.maps.LatLng(lat, lng));
            return true;
        }
        catch (error) {
            console.error("Error setting marker position:", error);
        }
        return false;
    }

    static cleanMarker() {
        if (!MapApi_Google.haveMarker()) {
            return true;
        }
        try {
            MapApi_Google.marker.setMap(null);
            MapApi_Google.marker = null;
            return true;
        } catch (error) {
            console.error("Error cleaning marker:", error);
        }
        return false;
    }

    static haveLine() {
        return MapApi_Google.line !== null;
    }

    static createLine(coords) {
        if (!MapApi_Google.isMapInitialized() || MapApi_Google.haveLine()) {
            console.error("Map not initialized or line already exists");
            return false;
        }

        try {            
            MapApi_Google.line = new window.google.maps.Polyline({
                path: coords,
                strokeOpacity: 0,
                icons: [{
                    icon: {
                    path: 'M 0,-1 0,1',
                    strokeOpacity: 1,
                    scale: 4
                    },
                    offset: '0',
                    repeat: '10px'
                }],
                map: MapApi_Google.map
            });
            return true;
        } catch (error) {
            console.error("Error creating line:", error);
        }
        return false;
    }

    static setLinePath(coords) {
        if (!MapApi_Google.haveLine()) {
            console.error("Line not initialized");
            return false;
        }

        try {
            MapApi_Google.line.setPath(coords);
            return true;
        } catch (error) {
            console.error("Error setting line path:", error);
        }
        return false;
    }

    static cleanLine() {
        if (!MapApi_Google.haveLine()) {
            return true;
        }
        try {
            MapApi_Google.line.setMap(null);
            MapApi_Google.line = null;
            return true;
        } catch (error) {
            console.error("Error cleaning line:", error);
        }
        return false;
    }

    static panTo(lat, lng) {
        if (!MapApi_Google.isMapInitialized()) {
            console.error("Map not initialized");
            return false;
        }

        try {
            let pos = new window.google.maps.LatLng(lat, lng);
            MapApi_Google.map.panTo(pos);
            return true;
        } catch (error) {
            console.error("Error panning map:", error);
        }
        return false;
    }

    static flyTo(lat, lng) {
        if (!MapApi_Google.isMapInitialized()) {
            console.error("Map not initialized");
            return false;
        }
        try {
            const target = { lat, lng };
            const originalZoom = MapApi_Google.map.getZoom();
            let zoom = originalZoom;

            const isTargetVisible = () => {
                const bounds = MapApi_Google.map.getBounds();
                return bounds && bounds.contains(target);
            };

            const startZoomIn = () => {
                const zoomIn = setInterval(() => {
                    zoom++;
                    MapApi_Google.map.setZoom(zoom);
                    if (zoom >= originalZoom) clearInterval(zoomIn);
                }, 200);
            };

            // Destino já visível: pan direto, sem zoom algum
            if (isTargetVisible()) {
                MapApi_Google.panTo(target);
                return true;
            }

            // Zoom out só até o destino entrar nos bounds
            const zoomOut = setInterval(() => {
                // A checagem reflete o estado do zoom aplicado na iteração anterior (200ms atrás)
                if (isTargetVisible() || zoom <= 2) {
                    clearInterval(zoomOut);
                    setTimeout(() => {}, 1000);
                    MapApi_Google.panTo(target);
                    setTimeout(startZoomIn, 1000);
                    return;
                }
                zoom--;
                MapApi_Google.map.setZoom(zoom);
            }, 200);

            return true;
        } catch (error) {
            console.error("Error flying to location:", error);
        }
        return false;
    }

}