let map;
let marker;
let infoWindow;
let geocoder;
let zonePolygons = [];

async function initMap() {
    // Import libraries
    const [{Map}, {AdvancedMarkerElement}, {PlaceAutocompleteElement}] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker"),
        google.maps.importLibrary("places")
    ]);

    // Initialize geocoder
    geocoder = new google.maps.Geocoder();

    // Set default center (NYC)
    let center = {lat: 40.749933, lng: -73.98633};

    // Check if there are existing coordinates from form
    const existingLat = document.getElementById('latitude')?.value;
    const existingLng = document.getElementById('longitude')?.value;
    if (existingLat && existingLng) {
        center = {
            lat: parseFloat(existingLat),
            lng: parseFloat(existingLng)
        };
    }

    // Init map
    map = new Map(document.getElementById('map'), {
        center,
        zoom: existingLat && existingLng ? 16 : 13,
        mapTypeControl: false,
        mapId: '4504f8b37365c3d0',
    });

    // Create city selector card for inside the map
    const citySelectorCard = document.createElement('div');
    citySelectorCard.id = 'city-selector-card';
    citySelectorCard.style.cssText = `
        background-color: #fff;
        border-radius: 8px;
        box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        margin: 10px;
        padding: 10px;
        font-family: Roboto, sans-serif;
        font-size: 14px;
        font-weight: 500;
        min-width: 300px;
    `;

    const citySelectorLabel = document.createElement('p');
    citySelectorLabel.textContent = 'Search for a place or click on map:';
    citySelectorLabel.style.cssText = `
        margin: 0 0 8px 0;
        font-weight: bold;
        color: #333;
    `;
    citySelectorCard.appendChild(citySelectorLabel);

    // Place Autocomplete for city selector inside map
    const placeAutocomplete = new PlaceAutocompleteElement();
    placeAutocomplete.id = 'place-autocomplete-input';
    placeAutocomplete.style.width = "100%";
    placeAutocomplete.locationBias = center;
    citySelectorCard.appendChild(placeAutocomplete);

    // Add the city selector card as a map control
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(citySelectorCard);

    // Keep the existing autocomplete container for backward compatibility
    // but make it hidden since we're using the map control instead
    const existingContainer = document.getElementById('autocomplete-container');
    if (existingContainer) {
        existingContainer.style.display = 'none';
    }

    // Initialize marker
    marker = new AdvancedMarkerElement({
        map,
        position: center,
        draggable: true
    });

    // If there are existing coordinates, show the marker
    if (existingLat && existingLng) {
        marker.position = center;
    }

    // Draw all delivery zones on the map so seller can see service availability
    try {
        await renderDeliveryZonesOnMap();
    } catch (e) {
        console.warn('Unable to render delivery zones on map:', e);
    }

    // Add click listener to map for location selection
    map.addListener('click', async (event) => {
        const clickedLocation = event.latLng;
        await handleLocationSelection(clickedLocation);
    });

    // Add drag listener to marker
    marker.addListener('dragend', async (event) => {
        const draggedLocation = event.latLng;
        await handleLocationSelection(draggedLocation);
    });

    // Handle place selection from autocomplete
    placeAutocomplete.addEventListener('gmp-select', async ({placePrediction}) => {
        const place = placePrediction.toPlace();
        await place.fetchFields({fields: ['addressComponents', 'formattedAddress', 'location', 'displayName']});

        // Center map and move marker
        if (place.viewport) {
            map.fitBounds(place.viewport);
        } else {
            map.setCenter(place.location);
            map.setZoom(16);
        }

        await handleLocationSelection(place.location, place);
    });
}

// Handle location selection (from click, drag, or autocomplete)
async function handleLocationSelection(location, place = null) {
    // Update marker position
    marker.position = location;

    // Update latitude and longitude fields
    document.getElementById('latitude').value = location.lat();
    document.getElementById('longitude').value = location.lng();

    let addressData = {};
    let displayName = '';
    let formattedAddress = '';

    if (place) {
        // Data from place autocomplete
        const ac = place.addressComponents || [];
        ac.forEach(component => {
            if (component.types.includes('locality')) addressData.city = component.longText;
            if (component.types.includes('administrative_area_level_1')) addressData.state = component.longText;
            if (component.types.includes('country')) addressData.country = component.longText;
            if (component.types.includes('postal_code')) addressData.postal_code = component.longText;
            if (component.types.includes('route')) addressData.street = component.longText;
            if (component.types.includes('street_number')) addressData.street_number = component.longText;
        });

        displayName = place.displayName || '';
        formattedAddress = place.formattedAddress || '';
    } else {
        // Reverse geocode for clicked/dragged location
        try {
            const response = await geocoder.geocode({location: location});
            if (response.results[0]) {
                const result = response.results[0];
                formattedAddress = result.formatted_address;

                // Parse address components from geocoding result
                result.address_components.forEach(component => {
                    if (component.types.includes('locality')) addressData.city = component.long_name;
                    if (component.types.includes('administrative_area_level_1')) addressData.state = component.long_name;
                    if (component.types.includes('country')) addressData.country = component.long_name;
                    if (component.types.includes('postal_code')) addressData.postal_code = component.long_name;
                    if (component.types.includes('route')) addressData.street = component.long_name;
                    if (component.types.includes('street_number')) addressData.street_number = component.long_name;
                });

                displayName = addressData.city || 'Selected Location';
            }
        } catch (error) {
            console.error('Geocoding failed:', error);
            displayName = 'Selected Location';
            formattedAddress = `${location.lat()}, ${location.lng()}`;
        }
    }

    // Update form fields
    if (addressData.country) {
        let selectCountries = document.getElementById("select-countries");
        if (selectCountries && selectCountries.tomselect) {
            loadCountryAndSetValue(selectCountries.tomselect, addressData.country);
        }
    }

    // Combine street number and street
    const street_full = addressData.street_number && addressData.street ?
        `${addressData.street_number} ${addressData.street}` : addressData.street || '';

    // Fill the form fields
    if (document.getElementById('city')) document.getElementById('city').value = addressData.city || '';
    if (document.getElementById('state')) document.getElementById('state').value = addressData.state || '';
    if (document.getElementById('zipcode')) document.getElementById('zipcode').value = addressData.postal_code || '';
    if (document.getElementById('landmark')) document.getElementById('landmark').value = street_full;
    if (document.getElementById('address')) document.getElementById('address').value = formattedAddress;

    // Show info window
    if (!infoWindow) infoWindow = new google.maps.InfoWindow();
    const content = `<div id="infowindow-content">
        <span class="title" style="font-weight: bold;">${displayName}</span><br>
        <span>${formattedAddress}</span><br>
        <small style="color: #666;">Click and drag marker to adjust position</small>
    </div>`;
    infoWindow.setContent(content);
    infoWindow.setPosition(location);
    infoWindow.open({map, anchor: marker, shouldFocus: false});
}

// Fetch active delivery zones from API and draw their polygons on the seller map
async function renderDeliveryZonesOnMap() {
    // Clear existing polygons if any
    if (zonePolygons.length) {
        zonePolygons.forEach(p => p.setMap(null));
        zonePolygons = [];
    }

    const response = await fetch('/api/delivery-zone?per_page=500', {headers: {'Accept': 'application/json'}});
    if (!response.ok) throw new Error('Failed to load delivery zones');
    const json = await response.json();

    // Some APIs wrap data inside data.data per existing controller
    const items = (json && json.data && Array.isArray(json.data.data)) ? json.data.data : (Array.isArray(json.data) ? json.data : []);

    if (!items.length) return;

    const iw = infoWindow || new google.maps.InfoWindow();

    items.forEach(zone => {
        if (!zone.boundary_json || !Array.isArray(zone.boundary_json) || zone.boundary_json.length < 3) return;
        const path = zone.boundary_json.map(pt => ({lat: parseFloat(pt.lat), lng: parseFloat(pt.lng)})).filter(p => !Number.isNaN(p.lat) && !Number.isNaN(p.lng));
        if (path.length < 3) return;

        const polygon = new google.maps.Polygon({
            paths: path,
            strokeColor: '#0066ff',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#1a73e8',
            fillOpacity: 0.08,
            // Important: keep polygons non-interactive so map clicks work everywhere
            // Clicking inside a polygon previously intercepted the event and
            // prevented selecting a location. Setting clickable to false lets
            // the underlying map receive the click to place/move the marker.
            clickable: false,
            map: map,
        });

        polygon.addListener('click', (e) => {
            const content = `<div><strong>${zone.name || 'Service Zone'}</strong></div>`;
            iw.setContent(content);
            iw.setPosition(e.latLng);
            iw.open({map});
        });

        zonePolygons.push(polygon);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    try {
        window.initMap = initMap;
    } catch (error) {
        console.error('Error initializing map:', error);
    }
});
$(document).ready(function () {

    const storesTable = $('#stores-table').DataTable();
// Reload table when filters change
    $('#verificationStatus, #visibilityStatus').on('change', function () {
        storesTable.ajax.reload(null, false);
    });

// Add filter params to AJAX request
    $('#stores-table').on('preXhr.dt', function (e, settings, data) {
        data.verification_status = $('#verificationStatus').val();
        data.visibility_status = $('#visibilityStatus').val();
    });
});
