<script lang="ts" setup>
import { ref } from 'vue';
import type { Attribute } from '../stores/data';
const props = defineProps<{
    attribute:Attribute;
}>();
const emits = defineEmits(['onUpdate']);

import { useDataStore } from '../stores/data';
const data = useDataStore();

function showOptions()
{
    return data.types[props.attribute.type]
        && data.types[props.attribute.type].options
}

function update(field:string, value:string)
{
    emits('onUpdate', {field: field, value: value});

    if (field == 'type') {
        if (data.types[value].options && !props.attribute.options && data.types[value].optdefault) {
            emits('onUpdate', {field: 'options', value: data.types[value].optdefault});
        }
    }
}

function listAttributeTypes()
{
    var types = data.types;
    var retval:Array<Attribute> = [];
    Object.keys(types).forEach((key) => {
        retval.push({id: 0, type: key, name: types[key].name});
    });
    return retval;
}

import {ElIcon, ElInput, ElSelect, ElOption} from 'element-plus';
import { Rank } from '@element-plus/icons-vue';
</script>
<template>
    <div class="attribute">
        <div class="attribute-handle">
            <ElIcon size="large">
                <Rank />
            </ElIcon>
        </div>
        <div class="attribute-settings">
            <div class="attribute-name">
                Name: <ElInput :model-value="props.attribute.name" @update:model-value="(e) => update('name', e)"/>
            </div>
            <div class="attribute-type">
                Type: <ElSelect :model-value="props.attribute.type" @update:model-value="(e) => update('type', e)">
                    <ElOption v-for="item in listAttributeTypes()" :key="item.type" :value="item.type" :label="item.name"/>
                </ElSelect>
            </div>
            <div class="attribute-rules">
                Rules: <ElInput :model-value="props.attribute.rules" @update:model-value="(e) => update('rules', e)"/>
            </div>
            <div class="attribute-options" v-if="showOptions()">
                Options: <ElInput :model-value="props.attribute.options" @update:model-value="(e) => update('options', e)"/>
            </div>
        </div>
    </div>
</template>