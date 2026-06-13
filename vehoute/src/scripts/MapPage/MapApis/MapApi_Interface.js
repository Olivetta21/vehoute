export default class MapApi_Interface {
    static isAnyNotImplemented() {
        const requiredMethods = [
            'initAPI',
            'isMapInitialized',
            'initializeMap',
            'getMapDiv',
            'haveMarker',
            'createMarker',
            'setMarkerPosition',
            'cleanMarker',
            'haveLine',
            'createLine',
            'setLinePath',
            'cleanLine',
            'panTo',
            'flyTo'
        ]
        for (const method of requiredMethods) {
            if (this[method] === MapApi_Interface.prototype[method]) {
                throw new Error(`Method '${method}' is not implemented in ${this.constructor.name}`);
                //return true;
            }
        }        return false;
    }

    static initAPI() {
        throw new Error("Method 'initAPI()' must be implemented.");
    }
    static isMapInitialized() {
        throw new Error("Method 'isMapInitialized()' must be implemented.");
    }
    static initializeMap() {
        throw new Error("Method 'initializeMap()' must be implemented.");
    }
    static getMapDiv(){
        throw new Error("Method 'getMapDiv()' must be implemented.");
    }
    static haveMarker(){
        throw new Error("Method 'haveMarker()' must be implemented.");
    }
    static createMarker(){
        throw new Error("Method 'createMarker()' must be implemented.");
    }
    static setMarkerPosition(){
        throw new Error("Method 'setMarkerPosition()' must be implemented.");
    }
    static cleanMarker(){
        throw new Error("Method 'cleanMarker()' must be implemented.");
    }
    static haveLine(){
        throw new Error("Method 'haveLine()' must be implemented.");
    }
    static createLine(){
        throw new Error("Method 'createLine()' must be implemented.");
    }
    static setLinePath(){
        throw new Error("Method 'setLinePath()' must be implemented.");
    }
    static cleanLine(){
        throw new Error("Method 'cleanLine()' must be implemented.");
    }
    static panTo(){
        throw new Error("Method 'panTo()' must be implemented.");
    }
    static flyTo() {
        throw new Error("Method 'flyTo()' must be implemented.");
    }
}