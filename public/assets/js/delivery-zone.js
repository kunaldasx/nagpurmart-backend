let map;
let marker;
let infoWindow;
let polygon = null; // Current drawn polygon
let originalPolygon = null; // Original polygon if exists
let drawingManager;
let center = {lat: 40.749933, lng: -73.98633}; // Default center: NYC
let otherZonePolygons = []; // Other delivery zones overlays

async function initMap() {
    // Load needed libraries (marker, places, drawing)
    const [{Map}, {AdvancedMarkerElement}, {DrawingManager}] = await Promise.all([
        google.maps.importLibrary("marker"),
        google.maps.importLibrary("places"),
        google.maps.importLibrary("drawing")
    ]);

    // Center from hidden input if available
    const centerLatInput = document.getElementById('center-latitude');
    const centerLngInput = document.getElementById('center-longitude');
    if (centerLatInput.value && centerLngInput.value) {
        center = {
            lat: parseFloat(centerLatInput.value),
            lng: parseFloat(centerLngInput.value)
        };
    }

    // Initialize map
    map = new google.maps.Map(document.getElementById('map'), {
        center,
        zoom: 13,
        mapId: '4504f8b37365c3d0',
        mapTypeControl: false,
    });

    // Place Autocomplete
    const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
    placeAutocomplete.id = 'place-autocomplete-input';
    placeAutocomplete.locationBias = center;
    const card = document.getElementById('place-autocomplete-card');
    card.appendChild(placeAutocomplete);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(card);

    marker = new google.maps.marker.AdvancedMarkerElement({map});
    infoWindow = new google.maps.InfoWindow({});

    placeAutocomplete.addEventListener('gmp-select', async ({placePrediction}) => {
        const place = placePrediction.toPlace();
        await place.fetchFields({fields: ['displayName', 'formattedAddress', 'location']});
        if (place.viewport) {
            map.fitBounds(place.viewport);
        } else {
            map.setCenter(place.location);
            map.setZoom(17);
        }
        let content = `<div id="infowindow-content">
            <span id="place-displayname" class="title">${place.displayName}</span><br />
            <span id="place-address">${place.formattedAddress}</span>
        </div>`;
        updateInfoWindow(content, place.location);
        marker.position = place.location;
    });

    // Drawing Manager for Polygon
    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.POLYGON,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: ['polygon']
        },
        polygonOptions: {
            fillColor: '#FF0000',
            fillOpacity: 0.2,
            strokeWeight: 2,
            clickable: true,
            editable: true,
            zIndex: 1
        }
    });
    drawingManager.setMap(map);

    // Only allow one polygon at a time
    google.maps.event.addListener(drawingManager, 'polygoncomplete', function (newPolygon) {
        if (polygon) {
            polygon.setMap(null);
        }
        polygon = newPolygon;
        updateBoundaryInput(polygon);
        setPolygonListeners(polygon);
        drawingManager.setDrawingMode(null); // Stop drawing after one polygon
    });

    // Restore existing polygon if available
    const boundaryJsonInput = document.getElementById('boundary-json');
    if (boundaryJsonInput.value) {
        try {
            const pathArr = JSON.parse(boundaryJsonInput.value);
            if (Array.isArray(pathArr) && pathArr.length > 0) {
                const path = pathArr.map(coord => new google.maps.LatLng(coord.lat, coord.lng));
                originalPolygon = new google.maps.Polygon({
                    paths: path,
                    fillColor: '#FF0000',
                    fillOpacity: 0.2,
                    strokeWeight: 2,
                    editable: true,
                    map: map,
                });
                map.fitBounds(getBoundsForPath(path));
                polygon = originalPolygon;
                updateBoundaryInput(polygon);
                setPolygonListeners(polygon);
            }
        } catch (e) {
            // Ignore parse error
        }
    }

    // Render other delivery zones in blue (non-interactive) so admin can see nearby zones
    try {
        await renderOtherDeliveryZonesOnForm();
    } catch (e) {
        console.warn('Unable to render other delivery zones on form:', e);
    }

    // Clear last polygon button
    document.getElementById('clear-last')?.addEventListener('click', function () {
        if (polygon) {
            polygon.setMap(null);
            polygon = null;
            document.getElementById('boundary-json').value = "";
        }
    });

    // Reset to original polygon button
    document.getElementById('reset-zone')?.addEventListener('click', function () {
        if (originalPolygon) {
            if (polygon) polygon.setMap(null);
            // Deep-clone path to allow editing
            const origPath = originalPolygon.getPath().getArray().map(latlng => ({
                lat: latlng.lat(),
                lng: latlng.lng()
            }));
            polygon = new google.maps.Polygon({
                paths: origPath,
                fillColor: '#FF0000',
                fillOpacity: 0.2,
                strokeWeight: 2,
                editable: true,
                map: map,
            });
            map.fitBounds(getBoundsForPath(origPath.map(coord => new google.maps.LatLng(coord.lat, coord.lng))));
            updateBoundaryInput(polygon);
            setPolygonListeners(polygon);
        }
    });
}

// Fetch active delivery zones and draw them as blue polygons, excluding the current zone (if any)
async function renderOtherDeliveryZonesOnForm() {
    // Clear existing overlays
    if (otherZonePolygons.length) {
        otherZonePolygons.forEach(p => p.setMap(null));
        otherZonePolygons = [];
    }

    const currentZoneIdEl = document.getElementById('current-zone-id');
    const currentZoneId = currentZoneIdEl ? parseInt(currentZoneIdEl.value) : null;

    const response = await fetch('/api/delivery-zone?per_page=500', {headers: {Accept: 'application/json'}});
    if (!response.ok) return; // fail silently on admin form
    const json = await response.json();

    // API wraps collection inside data.data
    const items = (json && json.data && Array.isArray(json.data.data)) ? json.data.data : (Array.isArray(json.data) ? json.data : []);
    if (!items.length) return;

    items.forEach(zone => {
        if (currentZoneId && zone.id === currentZoneId) return; // skip current
        if (!zone.boundary_json || !Array.isArray(zone.boundary_json) || zone.boundary_json.length < 3) return;
        const path = zone.boundary_json
            .map(pt => ({lat: parseFloat(pt.lat), lng: parseFloat(pt.lng)}))
            .filter(p => !Number.isNaN(p.lat) && !Number.isNaN(p.lng));
        if (path.length < 3) return;

        const overlay = new google.maps.Polygon({
            paths: path,
            strokeColor: '#0066ff',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#1a73e8',
            fillOpacity: 0.08,
            clickable: false, // do not intercept clicks; keep drawing/editing smooth
            zIndex: 0, // stay beneath the editable polygon
            map: map,
        });

        otherZonePolygons.push(overlay);
    });
}

// Helper: update hidden field with polygon coordinates
function updateBoundaryInput(polygon) {
    const path = polygon.getPath().getArray().map(latlng => ({
        lat: latlng.lat(),
        lng: latlng.lng()
    }));
    document.getElementById('boundary-json').value = JSON.stringify(path);

    // Calculate centroid (center)
    const center = getPolygonCentroid(path);
    if (center) {
        document.getElementById('center-latitude').value = center.lat;
        document.getElementById('center-longitude').value = center.lng;
    }

    // Calculate max radius from center to any vertex (in km)
    const radiusKm = getMaxRadiusKm(center, path);
    console.log(radiusKm)

    document.getElementById('radius-km').value = radiusKm.toFixed(3);
}

// Calculate centroid of polygon (simple average, works for most lat/lng polygons)
function getPolygonCentroid(path) {
    if (!path.length) return null;
    let lat = 0, lng = 0;
    path.forEach(point => {
        lat += point.lat;
        lng += point.lng;
    });
    return {lat: lat / path.length, lng: lng / path.length};
}

// Calculate max distance from center to any vertex (in kilometers)
function getMaxRadiusKm(center, path) {
    let maxDist = 0;
    path.forEach(point => {
        const dist = haversineDistance(center, point);
        if (dist > maxDist) maxDist = dist;
    });
    return maxDist;
}

// Haversine formula for distance between two lat/lng points (in kilometers)
function haversineDistance(coord1, coord2) {
    const R = 6371; // Earth's radius in km
    const dLat = toRad(coord2.lat - coord1.lat);
    const dLng = toRad(coord2.lng - coord1.lng);
    const lat1 = toRad(coord1.lat);
    const lat2 = toRad(coord2.lat);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.sin(dLng / 2) * Math.sin(dLng / 2) * Math.cos(lat1) * Math.cos(lat2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toRad(deg) {
    return deg * Math.PI / 180;
}

// Helper: set listeners for polygon edit events to update hidden input
function setPolygonListeners(polygon) {
    google.maps.event.clearListeners(polygon.getPath(), 'set_at');
    google.maps.event.clearListeners(polygon.getPath(), 'insert_at');
    google.maps.event.clearListeners(polygon.getPath(), 'remove_at');
    polygon.getPath().addListener('set_at', () => updateBoundaryInput(polygon));
    polygon.getPath().addListener('insert_at', () => updateBoundaryInput(polygon));
    polygon.getPath().addListener('remove_at', () => updateBoundaryInput(polygon));
}

// Helper: compute bounds from a path
function getBoundsForPath(path) {
    const bounds = new google.maps.LatLngBounds();
    path.forEach(latlng => bounds.extend(latlng));
    return bounds;
}

// InfoWindow helper
function updateInfoWindow(content, position) {
    infoWindow.setContent(content);
    infoWindow.setPosition(position);
    infoWindow.open({map, anchor: marker, shouldFocus: false});
}

try {
    initMap();
} catch (e) {
    console.error("Error initializing map:", e);
}
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        handleDelete(event, '.delete-delivery-zone', `/${panel}/delivery-zones/`, 'You are about to delete this Zone.');
    });
});
