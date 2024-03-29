<script lang="ts" setup>
import type { Attribute, Member } from '../lib/types';
const props = defineProps<{
    attribute:Attribute;
    member: Member;
    errors: Array<string>;
}>();
const emits = defineEmits(['onUpdate']);

function update(value:string)
{
    emits('onUpdate', value);
}

function modelValue()
{
    return props.member[props.attribute.name] || '';
}

function showReadOnly()
{
    return !showTextInput() && !showEmailInput() && !showSelectInput();
}

function showTextInput()
{
    return ['text', 'int', 'number', 'money', 'date', 'datetime'].includes(props.attribute.type);
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
    var options:any = [];
    if (props.attribute.type == 'enum') {
        props.attribute.options.split('|').forEach((val:string) => {
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
        <div class="label" v-if="showReadOnly()">
            <label>{{ modelValue() }}</label>
        </div>
        <div class="errors" v-if="props.errors.length > 0">
            <div class="error" v-for="(error, i) in props.errors" :key="i">
                {{ error }}
            </div>
        </div>
    </div>
</template>