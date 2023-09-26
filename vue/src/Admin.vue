<script lang="ts" setup>
import { ref } from 'vue';
const props = defineProps<{
    nonce:string;
    url:string;
}>();

import { useDataStore } from './stores/data';
const data = useDataStore();
data.nonce = props.nonce;
data.baseUrl = props.url;
data.getConfiguration();

const tabindex = ref('data');
import { ElTabs, ElTabPane } from 'element-plus';
import ConfigurationView from './components/ConfigurationView.vue';
import DataView from './components/DataView.vue';
</script>
<template>
    <div>
        <h1>Memberdata Manager</h1>
        <ElTabs v-model="tabindex">
            <ElTabPane label="Data" name="data">
                <DataView :index="tabindex"/>
            </ElTabPane>
            <ElTabPane label="Settings" name="settings">
                <ConfigurationView :index="tabindex"/>
            </ElTabPane>
         </ElTabs>  
    </div>
</template>
