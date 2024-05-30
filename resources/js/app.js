import './bootstrap';
// import Alpine from 'alpinejs'
import intersect from '@alpinejs/intersect'
import.meta.glob([
    '../images/**'
]);

Alpine.plugin(intersect)
