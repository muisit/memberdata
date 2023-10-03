<script lang="ts" setup>
import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { random_token } from '@/lib/functions';
const props = defineProps<{
    index:string
}>();

import { useDataStore } from '../stores/data';
import type { FilterSpecByKey } from '../lib/types';
const data = useDataStore();

const currentpage = ref(0);
const pagesize = ref('25');
const filter:Ref<FilterSpecByKey> = ref({});
const sorter = ref('id');
const sortdir = ref('asc');

function getOffset()
{
    if (parseInt(pagesize.value) <= 0) return 0;
    return currentpage.value * parseInt(pagesize.value);
}

function updateData(cb:Function|null = null)
{
    data.getData(
        getOffset(),
        parseInt(pagesize.value), // by default, only get the first 25 items
        filter.value,
        sorter.value,
        sortdir.value,
        500, // if the total is less than this cutoff, everything is returned anyway
        cb // callback to check if updating is still useful
    );
}

function hasWholeList()
{
    return data.originalData.length > 0 && data.originalData.length == data.dataCount;
}

watch(
    () => props.index,
    (nw) => {
        if (nw == 'data') {
            // if we switch tabs, always reload the data
            updateData();
        }
    },
    { immediate: true }
)

var nonewfilter = '';

watch(
    () => [pagesize.value, filter.value, sorter.value, sortdir.value, currentpage.value],
    () => {
        // if we do not have the whole list, use server side sorting and paging
        if (!hasWholeList()) {
            var newtoken = random_token();
            nonewfilter = newtoken;
            window.setTimeout(() => {
                if (newtoken == nonewfilter) {
                    updateData(() => newtoken == nonewfilter);
                }
            }, 500);
        }
        else {
            // else use client side sorting and paging
            data.applyPagerSorterFilter(getOffset(), parseInt(pagesize.value), filter.value, sorter.value, sortdir.value);
        }
    },
    { immediate: true }
)

function addRow()
{
    data.addNewMember();
}

function shouldDisplayPager()
{
    return  (data.dataCount > 25);
}

function switchPage(pagenum:number)
{
    if (pagenum < 0) pagenum = 0;
    if (parseInt(pagesize.value) <= 0) {
        pagenum = 0;
    }
    else {
        var lastpage = Math.ceil(data.dataCount / parseInt(pagesize.value));
        if (pagenum > lastpage) pagenum = lastpage;
    }
    currentpage.value = pagenum;
}

function onDeleteMember()
{
    if (!hasWholeList()) {
        // update the list to apply paging and sorting on the server side
        updateData();
    }
}

function updateFilter(settings:any)
{
    var newFilter = Object.assign({}, filter.value);
    newFilter[settings.attribute] = settings.filter;
    filter.value = newFilter;
}

function exportSheet()
{
    data.exportData(
        filter.value,
        sorter.value,
        sortdir.value
    );
}

import PagerBlock from './PagerBlock.vue';
import MemberGrid from './MemberGrid.vue';
import { ElButton, ElSelect, ElOption } from 'element-plus';
</script>
<template>
    <div class="container">
        <div class="grid-actions">
            <PagerBlock :count="data.dataCount" :page="currentpage" :pagesize="parseInt(pagesize)" @go-to="switchPage" />
            <ElSelect v-model="pagesize" v-if="shouldDisplayPager()">
                <ElOption value="10" label="10"/>
                <ElOption value="25" label="25" v-if="data.dataCount > 25"/>
                <ElOption value="50" label="50" v-if="data.dataCount > 50"/>
                <ElOption value="100" label="100" v-if="data.dataCount > 100"/>
                <ElOption value="250" label="250" v-if="data.dataCount > 250"/>
                <ElOption value="0" label="All" v-if="data.dataCount < 500"/>
            </ElSelect>
            <ElButton type="default" @click="exportSheet">Export</ElButton>
            <ElButton type="primary" @click="addRow">Add</ElButton>
        </div>
        <MemberGrid
            :page="currentpage"
            :pagesize="parseInt(pagesize)"
            :sorter="sorter"
            :sortdir="sortdir"
            :filter="filter"
            @on-delete="onDeleteMember"
            @update-sorter="(v) => { sorter = v[0]; sortdir = v[1]}"
            @update-filter="updateFilter"
            />            
    </div>
</template>