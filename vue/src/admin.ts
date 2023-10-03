import 'element-plus/dist/index.css'
import './assets/admin.scss'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Admin from './AdminApp.vue'

const el = document.getElementById('memberdata-admin');
let props = {};
if (el) {
    const data = el.getAttribute('data-memberdata');
    if (data) {
        props = JSON.parse(data);
    }
}

const app = createApp(Admin, props)

app.use(createPinia())
app.mount('#memberdata-admin')
