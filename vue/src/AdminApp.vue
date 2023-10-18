<script lang="ts" setup>
import { ref } from 'vue';
import type { FieldDefinition } from './lib/types';
import { is_valid } from './lib/functions';
const props = defineProps<{
    nonce:string;
    url:string;
}>();

import { useDataStore } from './stores/data';
const data = useDataStore();
data.nonce = props.nonce;
data.baseUrl = props.url;
data.getSheets().then(() => {
    data.currentSheet = data.sheets[0];
    data.getConfiguration();
});

const sheetDialog = ref(false);
function openSheetDialog()
{
    data.currentSheet.id = 0;
    data.currentSheet.name='Sheet';
    sheetDialog.value = true;
}

function closeSheetDialog()
{
    sheetDialog.value = false;
}

function saveSheetDialog()
{
    var found = false;
    data.sheets.map((sh) => {
        if (sh.id == data.currentSheet.id) {
            found = true;
        }
    });
    if (!found) {
        data.sheets.push(data.currentSheet);
    }
    sheetDialog.value = false;
}

function updateSheetDialog(fieldDef:FieldDefinition)
{
    if (fieldDef.field == 'name') {
        data.currentSheet.name = fieldDef.value;
    }
}

const tabindex = ref('data');
import { ElTabs, ElTabPane, ElSelect, ElOption, ElButton, ElIcon } from 'element-plus';
import { Edit } from '@element-plus/icons-vue';
import ConfigurationView from './components/ConfigurationView.vue';
import DataView from './components/DataView.vue';
import SheetDialog from './components/SheetDialog.vue';
</script>
<template>
    <div class="container">
        <div class='main-header'>
            <h1>Memberdata Manager</h1>
            <div class="subheader">
                <span v-if="!is_valid(data.currentSheet.id)">Pick a sheet</span>
                <span v-else>
                    {{ data.currentSheet.name }}
                    <ElIcon size='large' @click="() => sheetDialog = true">
                        <Edit />
                    </ElIcon>
                </span>
            </div>
            <div class="action-buttons">
                <ElSelect :model-value="data.currentSheet.id" @update:model-value="(e) => data.pickSheet(e)">
                    <ElOption v-for="sheet in data.sheets" :key="sheet.id" :value="sheet.id" :label="sheet.name"/>
                </ElSelect>
                <ElButton @click="openSheetDialog" type="primary">Add</ElButton>
            </div>
            <SheetDialog :sheet="data.currentSheet" :visible="sheetDialog" @on-close="closeSheetDialog" @on-save="saveSheetDialog" @on-update="updateSheetDialog" />
        </div>
        <ElTabs v-model="tabindex">
            <ElTabPane label="Data" name="data" class="container">
                <DataView :index="tabindex"/>
            </ElTabPane>
            <ElTabPane label="Settings" name="settings">
                <ConfigurationView :index="tabindex"/>
            </ElTabPane>
         </ElTabs>  
    </div>
</template>
