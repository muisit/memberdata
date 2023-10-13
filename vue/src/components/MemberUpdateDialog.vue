<script lang="ts" setup>
import { ref } from 'vue';
import type { Ref } from 'vue';
const props = defineProps<{
    visible:boolean;
    member: Member;
}>();
const emits = defineEmits(['onClose', 'onUpdate', 'onSave']);

import { useDataStore } from '@/stores/data';
import type { Member, Attribute } from '@/lib/types';
import { validateAttribute } from '../lib/validation_rules';
const data = useDataStore();
function closeForm()
{
    emits('onClose');
}

interface ErrorsForAttribute {
    [key:string]: Array<string>;
}
const errorMessages:Ref<ErrorsForAttribute> = ref({});

function saveForm()
{
    var messages:Array<string> = [];
    errorMessages.value = {};
    data.configuration.forEach((attribute) => {
        var value = '' + (props.member[attribute.name] || '');
        var result = validateAttribute(attribute, value);
        if (result.length) {
            errorMessages.value[attribute.name] = result;
            messages = messages.concat(result);
        }
    });
    if (messages.length) {
        alert("There were validation errors:\r\n" + messages.join('\r\n'));
    }
    else {
        emits('onSave');
    }
}

function onUpdate(attribute:Attribute, value:string)
{
    emits('onUpdate', {attribute: attribute, value: value});
}

import AttributeInput from './AttributeInput.vue'
import { ElButton, ElDialog, ElForm, ElFormItem } from 'element-plus'
</script>
<template>
    <ElDialog :model-value="props.visible" title="Member Update" :before-close="(done) => { closeForm(); done(false); }">
        <ElForm>
            <ElFormItem v-for="attribute in data.configuration" :key="attribute.name">
                <label class="el-form-item__label">{{ attribute.name }}</label>
                <AttributeInput :attribute="attribute" :member="props.member" @on-update="(v) => onUpdate(attribute, v)" :errors="errorMessages[attribute.name] || []"/>
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