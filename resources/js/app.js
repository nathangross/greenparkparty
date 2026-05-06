import './bootstrap';
import Alpine from 'alpinejs'
import intersect from '@alpinejs/intersect'
import.meta.glob([
    '../images/**'
]);

window.Alpine = Alpine
Alpine.plugin(intersect)
Alpine.start()
