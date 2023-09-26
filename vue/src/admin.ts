import 'element-plus/dist/index.css'
import './assets/admin.scss'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Admin from './Admin.vue'

var el = document.getElementById('memberdata-admin');
var props = {};
if (el) {
    var data = el.getAttribute('data-memberdata');
    if (data) {
        props = JSON.parse(data);
    }
}

const app = createApp(Admin, props)

app.use(createPinia())
app.mount('#memberdata-admin')
