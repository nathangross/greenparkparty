import './bootstrap';
import intersect from '@alpinejs/intersect'
import.meta.glob([
    '../images/**'
]);

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(intersect)
})
