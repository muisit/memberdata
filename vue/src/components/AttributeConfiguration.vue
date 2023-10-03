<script lang="ts" setup>
import type { Attribute } from '../lib/types';
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
        retval.push({type: key, name: types[key].name, filter: 'N'});
    });
    return retval;
}

import {ElIcon, ElInput, ElSelect, ElOption, ElRow, ElCol, ElCheckbox} from 'element-plus';
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
            <ElRow>
                <ElCol :span="12">
                    <div class="attribute-name">
                        <div>Name:</div>
                        <ElInput :model-value="props.attribute.name" @update:model-value="(e) => update('name', e)"/>
                    </div>
                </ElCol>
                <ElCol :span="12">
                    <ElCheckbox :model-value="props.attribute.filter == 'Y'"  @update:model-value="(e) => update('filter', e ? 'Y' : 'N')"/>
                    Allow filtering on this attribute
                </ElCol>
            </ElRow>
            <ElRow>
                <ElCol :span="12">
                    <div class="attribute-type">
                        <div>Type:</div>
                        <ElSelect :model-value="props.attribute.type" @update:model-value="(e) => update('type', e)">
                            <ElOption v-for="item in listAttributeTypes()" :key="item.type" :value="item.type" :label="item.name"/>
                        </ElSelect>
                    </div>
                </ElCol>
            </ElRow>
            <ElRow>
                <ElCol :span="12">
                    <div class="attribute-rules">
                        <div>Rules:</div>
                        <ElInput :model-value="props.attribute.rules" @update:model-value="(e) => update('rules', e)"/>
                    </div>                       
                </ElCol>
            </ElRow>
            <ElRow>
                <ElCol :span="12">
                    <div class="attribute-options" v-if="showOptions()">
                        <div>Options:</div>
                        <ElInput :model-value="props.attribute.options" @update:model-value="(e) => update('options', e)"/>
                    </div>
                </ElCol>
            </ElRow>
        </div>
    </div>
</template>