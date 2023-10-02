<script lang="ts" setup>
const props = defineProps<{
    sorter: string;
    sortdir: string;
    filter: FilterSpecByKey;
}>();
const emits = defineEmits(['updateSorter', 'updateFilter']);

function toggleSorter(sortval: string)
{
    if (props.sorter == sortval) {
        if (props.sortdir == 'asc') {
            console.log('emitting change to desc');
            emits('updateSorter', [sortval, 'desc']);
        }
        else {
            console.log('emitting change to asc');
            emits('updateSorter', [sortval, 'asc']);
        }
    }
    else {
        console.log('emitting change to ', sortval);
        emits('updateSorter', [sortval, 'asc']);
    }
}

import { type FilterSpecByKey, useDataStore } from '../stores/data';
const data = useDataStore();

function onFilter(settings: object)
{
    emits('updateFilter', settings);
}

function isFiltered(attribute:Attribute)
{
    return props.filter[attribute.name] 
        && (
            (props.filter[attribute.name].search && props.filter[attribute.name].search.trim().length)
            || (props.filter[attribute.name].values.length)
        );
}

import DropFilter from './DropFilter.vue';
import { ElIcon } from 'element-plus';
import { Sort, SortUp, SortDown } from '@element-plus/icons-vue';
</script>
<template>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th>
                <span class='sortable'>#</span>
                <ElIcon size="large" @click="() => toggleSorter('id')" class="sorter">
                    <Sort v-if="props.sorter != 'id'"/>
                    <SortUp v-if="props.sorter == 'id' && props.sortdir == 'desc'"/>
                    <SortDown v-if="props.sorter == 'id' && props.sortdir == 'asc'"/>
                </ElIcon>
            </th>
            <th v-for="attribute in data.configuration" :key="attribute.name" :class="{'is-filtered': isFiltered(attribute)}">
                <span class='sortable'>{{ attribute.name }}</span>
                <ElIcon size="large" @click="() => toggleSorter(attribute.name)" class="sorter">
                    <Sort v-if="props.sorter != attribute.name"/>
                    <SortUp v-if="props.sorter == attribute.name && props.sortdir == 'desc'"/>
                    <SortDown v-if="props.sorter == attribute.name && props.sortdir == 'asc'"/>
                </ElIcon>
                <DropFilter :attribute="attribute" :filter="props.filter[attribute.name] || null" @on-filter="onFilter"/>
            </th>
        </tr>
    </thead>
</template>