<script lang="ts" setup>
import type { Sheet, APIResult } from '@/lib/types';
const props = defineProps<{
    visible:boolean;
    sheet: Sheet;
}>();
const emits = defineEmits(['onClose', 'onSave', 'onUpdate']);

function update(field:string, value:string)
{
    emits('onUpdate', {field: field, value:value});
}

function closeForm()
{
    emits('onClose');
}

import { saveSheet } from '@/lib/api';
function saveForm()
{
    saveSheet(props.sheet)
        .then((data:APIResult) => {
            if (data && data.data && data.data.errors && data.data.errors.length) {
                alert("Error saving sheet: " + data.data.errors.join('\r\n'));
            }
            emits('onSave');

        })
        .catch((e) => {
            console.log(e);
            alert("There was a problem saving the sheet. Please reload the page and try again");
        });
}

import { ElForm, ElFormItem, ElInput, ElButton, ElDialog } from 'element-plus'
</script>
<template>
    <ElDialog :model-value="props.visible" title="Sheet Management" :before-close="(done) => { closeForm(); done(false); }">
        <ElForm>
            <ElFormItem label="Name">
                <ElInput :model-value="props.sheet.name" @update:model-value="(e) => update('name', e)"/>
            </ElFormItem>
        </ElForm>
        <template #footer>
            <span class="dialog-footer">
                <ElButton type="warning" @click="closeForm">Cancel</ElButton>
                <ElButton type="primary" @click="saveForm">Save</ElButton>
            </span>
        </template>
    </ElDialog>    
</template>