import { createApp } from 'vue';
import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Composant Vue de test
import TestComponent from './components/TestComponent.vue';

const app = createApp({});
app.component('test-component', TestComponent);
app.mount('#vue-app');