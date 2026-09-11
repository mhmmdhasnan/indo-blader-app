import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const DEFAULT_LAT = -6.2088;
const DEFAULT_LNG = 106.8456;

document.addEventListener('alpine:init', () => {
    // Editable map used in the admin event form: click or drag the marker to set coordinates.
    Alpine.data('leafletPicker', (lat, lng) => ({
        map: null,
        marker: null,
        init($wire) {
            const startLat = lat || DEFAULT_LAT;
            const startLng = lng || DEFAULT_LNG;

            this.map = L.map(this.$el).setView([startLat, startLng], lat ? 15 : 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(this.map);

            this.marker = L.marker([startLat, startLng], { draggable: true }).addTo(this.map);

            const commit = (latlng) => {
                $wire.set('evLat', +latlng.lat.toFixed(6));
                $wire.set('evLng', +latlng.lng.toFixed(6));
            };

            this.marker.on('dragend', () => commit(this.marker.getLatLng()));
            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                commit(e.latlng);
            });

            $wire.$watch('evLat', () => this.syncFromWire($wire));
            $wire.$watch('evLng', () => this.syncFromWire($wire));

            setTimeout(() => this.map.invalidateSize(), 150);
        },
        syncFromWire($wire) {
            const newLat = parseFloat($wire.get('evLat'));
            const newLng = parseFloat($wire.get('evLng'));
            if (Number.isNaN(newLat) || Number.isNaN(newLng)) return;
            const current = this.marker.getLatLng();
            if (Math.abs(current.lat - newLat) < 1e-7 && Math.abs(current.lng - newLng) < 1e-7) return;
            this.marker.setLatLng([newLat, newLng]);
            this.map.setView([newLat, newLng], this.map.getZoom());
        },
    }));

    // Read-only map used on the public event page to show the venue location.
    Alpine.data('leafletView', (lat, lng, label) => ({
        init() {
            const map = L.map(this.$el, {
                zoomControl: true,
                scrollWheelZoom: false,
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            L.marker([lat, lng]).addTo(map).bindPopup(label || 'Venue');

            setTimeout(() => map.invalidateSize(), 150);
        },
    }));
});
