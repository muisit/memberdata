import './assets/frontend.scss'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Frontend from './FrontendApp.vue'

const el = document.getElementById('memberdata-fe');
let props = {};
if (el) {
    const data = el.getAttribute('data-memberdata');
    if (data) {
        props = JSON.parse(data);
    }
}

const app = createApp(Frontend, props)

app.use(createPinia())
app.mount('#memberdata-fe')
