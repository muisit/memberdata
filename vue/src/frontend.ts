import './assets/frontend.scss'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Frontend from './Frontend.vue'

var el = document.getElementById('memberdata-fe');
var props = {};
if (el) {
    var data = el.getAttribute('data-memberdata');
    if (data) {
        props = JSON.parse(data);
    }
}

const app = createApp(Frontend, props)

app.use(createPinia())
app.mount('#memberdata-fe')
