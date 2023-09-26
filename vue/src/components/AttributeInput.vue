<script lang="ts" setup>
import { ref } from 'vue';
import type { Attribute, Member } from '../stores/data';
const props = defineProps<{
    attribute:Attribute;
    member: Member;
    errors: Array<string>;
}>();
const emits = defineEmits(['onUpdate']);

import { useDataStore } from '../stores/data';
const data = useDataStore();

function update(value:string)
{
    emits('onUpdate', value);
}

function modelValue()
{
    return props.member[props.attribute.name] || '';
}

function showTextInput()
{
    return !showSelectInput() && !showEmailInput();
}

function showEmailInput()
{
    return props.attribute.type == 'email';
}

function showSelectInput()
{
    return ['enum'].includes(props.attribute.type);
}

function listSelectOptions()
{
    var options = [];
    if (props.attribute.type == 'enum') {
        props.attribute.options.split('|').forEach((val) => {
            options.push({label: val, value: val});
        });
    }
    return options;
}

import {ElInput, ElSelect, ElOption} from 'element-plus';
</script>
<template>
    <div class="attribute-input">
        <ElInput :model-value="modelValue()" @update:model-value="(e) => update(e)" v-if="showTextInput()"/>
        <ElInput type='email' :model-value="modelValue()" @update:model-value="(e) => update(e)" v-if="showEmailInput()"/>
        <ElSelect :model-value="modelValue()" @update:model-value="(e) => update(e)" v-if="showSelectInput()">
            <ElOption v-for="item in listSelectOptions()" :key="item.value" :value="item.value" :label="item.label"/>
        </ElSelect>
        <div class="errors" v-if="props.errors.length > 0">
            <div class="error" v-for="(error, i) in props.errors" :key="i">
                {{ error }}
            </div>
        </div>
    </div>
</template>