let defaultLocationMap;
let defaultLocationMarker;
let defaultLocationInfoWindow;
let defaultLocationCenter = {lat: 40.749933, lng: -73.98633}; // Default center: NYC

async function initDefaultLocationMap() {
    // Load needed libraries (marker, places, drawing)
    const [{Map}, {AdvancedMarkerElement}] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker"),
        google.maps.importLibrary("places")
    ]);

    // Get existing coordinates if available
    const latInput = document.getElementById('default-latitude');
    const lngInput = document.getElementById('default-longitude');
    if (latInput.value && lngInput.value) {
        defaultLocationCenter = {
            lat: parseFloat(latInput.value),
            lng: parseFloat(lngInput.value)
        };
    }

    // Initialize map
    defaultLocationMap = new google.maps.Map(document.getElementById('default-location-map'), {
        center: defaultLocationCenter,
        zoom: 13,
        mapId: '4504f8b37365c3d0',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        gestureHandling: 'cooperative'
    });

    // Place Autocomplete
    const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
    placeAutocomplete.id = 'place-autocomplete-input';
    placeAutocomplete.placeholder = "search location";
    placeAutocomplete.locationBias = defaultLocationCenter;
    const card = document.getElementById('place-autocomplete-card');
    card.appendChild(placeAutocomplete);
    defaultLocationMap.controls[google.maps.ControlPosition.TOP_LEFT].push(card);

    // Initialize marker with proper draggable configuration
    defaultLocationMarker = new google.maps.marker.AdvancedMarkerElement({
        map: defaultLocationMap,
        position: defaultLocationCenter,
        gmpDraggable: true,
        title: 'Default Location'
    });

    defaultLocationInfoWindow = new google.maps.InfoWindow({});

    // Get selected countries for restriction
    const selectedCountries = Array.from(document.querySelectorAll('#select-countries option:checked')).map(option => option.value);
    if (selectedCountries.length > 0) {
        // Use the proper method for setting country restrictions in the new API
        placeAutocomplete.countries = selectedCountries;
    }

    // Handle place selection
    placeAutocomplete.addEventListener('gmp-select', async ({placePrediction}) => {
        const place = placePrediction.toPlace();
        await place.fetchFields({fields: ['displayName', 'formattedAddress', 'location']});

        if (place.viewport) {
            defaultLocationMap.fitBounds(place.viewport);
        } else {
            defaultLocationMap.setCenter(place.location);
            defaultLocationMap.setZoom(17);
        }

        let content = `<div id="infowindow-content">
                    <span id="place-displayname" class="title">${place.displayName}</span><br />
                    <span id="place-address">${place.formattedAddress}</span>
                </div>`;

        updateDefaultLocationInfoWindow(content, place.location);
        defaultLocationMarker.position = place.location;

        // Update coordinate inputs
        document.getElementById('default-latitude').value = place.location.lat();
        document.getElementById('default-longitude').value = place.location.lng();
    });

    // Handle marker drag with proper event listener
    defaultLocationMarker.addListener('dragend', function () {
        const position = defaultLocationMarker.position;
        const lat = position.lat();
        const lng = position.lng();

        document.getElementById('default-latitude').value = lat;
        document.getElementById('default-longitude').value = lng;

        // Reverse geocode to get address
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({location: {lat: lat, lng: lng}}, function (results, status) {
            if (status === 'OK' && results[0]) {
                let content = `<div id="infowindow-content">
                            <span id="place-displayname" class="title">Selected Location</span><br />
                            <span id="place-address">${results[0].formatted_address}</span>
                        </div>`;
                updateDefaultLocationInfoWindow(content, {lat: lat, lng: lng});
            }
        });
    });

    // Handle map click to place marker
    defaultLocationMap.addListener('click', function (event) {
        const position = event.latLng;
        const lat = position.lat();
        const lng = position.lng();

        defaultLocationMarker.position = position;
        document.getElementById('default-latitude').value = lat;
        document.getElementById('default-longitude').value = lng;

        // Reverse geocode to get address
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({location: position}, function (results, status) {
            if (status === 'OK' && results[0]) {
                let content = `<div id="infowindow-content">
                            <span id="place-displayname" class="title">Selected Location</span><br />
                            <span id="place-address">${results[0].formatted_address}</span>
                        </div>`;
                updateDefaultLocationInfoWindow(content, position);
            }
        });
    });

    // Handle coordinate input changes
    latInput.addEventListener('change', updateMarkerFromInputs);
    lngInput.addEventListener('change', updateMarkerFromInputs);

    // Update country restrictions when countries change
    document.getElementById('select-countries').addEventListener('change', function () {
        const selectedCountries = Array.from(this.querySelectorAll('option:checked')).map(option => option.value);
        if (selectedCountries.length > 0) {
            placeAutocomplete.countries = selectedCountries;
        } else {
            placeAutocomplete.countries = [];
        }
    });

    // Show initial marker info
    if (latInput.value && lngInput.value) {
        updateDefaultLocationInfoWindow(
            '<div id="infowindow-content"><span class="title">Current Default Location</span></div>',
            defaultLocationCenter
        );
    }
}

function updateMarkerFromInputs() {
    const lat = parseFloat(document.getElementById('default-latitude').value);
    const lng = parseFloat(document.getElementById('default-longitude').value);

    if (!isNaN(lat) && !isNaN(lng)) {
        const position = {lat: lat, lng: lng};
        defaultLocationMarker.position = position;
        defaultLocationMap.setCenter(position);

        // Update info window
        updateDefaultLocationInfoWindow(
            '<div id="infowindow-content"><span class="title">Manual Location Entry</span></div>',
            position
        );
    }
}

function updateDefaultLocationInfoWindow(content, position) {
    defaultLocationInfoWindow.setContent(content);
    defaultLocationInfoWindow.setPosition(position);
    defaultLocationInfoWindow.open({
        map: defaultLocationMap,
        anchor: defaultLocationMarker,
        shouldFocus: false
    });
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function () {
    // Initialize map
    if (typeof google !== 'undefined' && google.maps) {
        initDefaultLocationMap();
    } else {
        console.warn('Google Maps API not loaded. Make sure to include the Google Maps script.');
    }
});
