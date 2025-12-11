import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { Quasar, Notify, Dialog } from 'quasar';
import router from './router';
import App from './App.vue';

// Import Quasar icon sets
import '@quasar/extras/material-icons/material-icons.css';
import '@quasar/extras/fontawesome-v6/fontawesome-v6.css';

// Import Quasar css
import 'quasar/src/css/index.sass';

// Import app css
import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(Quasar, {
    plugins: {
        Notify,
        Dialog
    },
});

app.use(router);

app.mount('#app');
