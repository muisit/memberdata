<script lang="ts" setup>
import { ref } from 'vue';
import type { Attribute, FilterSpec } from '@/stores/data';
import { random_token } from '../lib/functions';
const props = defineProps<{
    attribute:Attribute;
    filter: FilterSpec|null;
}>();
const emits = defineEmits(['onFilter']);

import { useDataStore } from '@/stores/data';
const data = useDataStore();

const showDrop = ref(false);
const dropRef = ref();

import { useDetectOutsideClick } from '../lib/outsideClick';
useDetectOutsideClick(dropRef, () => {
    showDrop.value = false;
})

function isChecked(filterValue: string|null):boolean
{
    return (props.filter && props.filter.values.includes(filterValue)) ? true : false;
}

function toggleFilter(filterValue: string|null)
{
    var shouldCheck = !isChecked(filterValue);
    var newfilter:FilterSpec = Object.assign({search: null, values: []}, props.filter || {});
    newfilter.values = newfilter.values.filter((value) => filterValue !== value);

    if (shouldCheck) {
        newfilter.values.push(filterValue);
    }
    emits('onFilter', {attribute: props.attribute.name, filter: newfilter});
}

function updateSearchFilter(v:string)
{
    var newfilter:FilterSpec = Object.assign({search: null, values: []}, props.filter || {});
    newfilter.search = v;
    emits('onFilter', {attribute: props.attribute.name, filter: newfilter});
}

function getSearchFilter()
{
    return props.filter ? props.filter.search : null;
}

import { ElIcon, ElCheckbox, ElInput } from 'element-plus';
import { CaretRight, CaretBottom } from '@element-plus/icons-vue';
</script>
<template>
    <div v-if="props.attribute.filter == 'Y'" class="drop-filter" ref="dropRef" :id="'_' + random_token()">
        <div v-if="!showDrop" @click="() => showDrop = true" class="filter member-filter-drop">
            <ElIcon size="large">
                <CaretRight />
            </ElIcon>
        </div>
        <div v-if="showDrop" class="filter member-filter-drop">
            <ElIcon size="large" @click="() => showDrop = false">
                <CaretBottom />
            </ElIcon>
            <div class="filter-list">
                <ElInput :model-value="getSearchFilter()" @update:model-value="(v) => updateSearchFilter(v)"/>
                <div v-if="data.dataFilters[props.attribute.name] && data.dataFilters[props.attribute.name].length">
                    <div 
                        class='filter-name'
                        v-for="filter in data.dataFilters[props.attribute.name]"
                        :key="filter || 'empty'">
                        <span @click="toggleFilter(filter)">
                            <ElCheckbox :model-value="isChecked(filter)"/>
                            {{ filter === null ? '<empty>' : filter }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>