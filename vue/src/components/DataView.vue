<script lang="ts" setup>
import { ref, watch } from 'vue';
import type { Ref } from 'vue';
const props = defineProps<{
    index:string
}>();

import { useDataStore } from '../stores/data';
import type { Member } from '../stores/data';
const data = useDataStore();

const currentpage = ref(0);
const pagesize = ref('25');
const filter = ref('');
const sorter = ref('id');
const sortdir = ref('asc');

function getOffset()
{
    if (parseInt(pagesize.value) <= 0) return 0;
    return currentpage.value / parseInt(pagesize.value);
}

function updateData()
{
    data.getData(
        getOffset(),
        parseInt(pagesize.value), // by default, only get the first 25 items
        filter.value,
        sorter.value,
        sortdir.value,
        200 // if the total is less than this cutoff, everything is returned anyway
    );
}

function hasWholeList()
{
    return data.dataList.length > 0 && data.dataList.length == data.dataCount;
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

watch(
    () => [pagesize.value, filter.value, sorter.value, sortdir.value, currentpage.value],
    (nw) => {
        console.log("update of pagesize, offset, filter, sorter or sortdir")
        // if we do not have the whole list, use server side sorting and paging
        if (!hasWholeList()) {
            updateData();
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
    console.log('switch page to ',pagenum);
    if (pagenum < 0) pagenum = 0;
    if (parseInt(pagesize.value) <= 0) {
        pagenum = 0;
    }
    else {
        var lastpage = Math.ceil(data.dataCount / parseInt(pagesize.value));
        console.log('lastpage is ', lastpage, data.dataCount, pagesize.value);
        if (pagenum > lastpage) pagenum = lastpage;
    }
    console.log('setting currentpage to ', pagenum);
    currentpage.value = pagenum;
}

function onDeleteMember()
{
    if (!hasWholeList()) {
        // update the list to apply paging and sorting on the server side
        updateData();
    }
}

import Pager from './Pager.vue';
import MemberGrid from './MemberGrid.vue';
import { ElButton, ElSelect, ElOption } from 'element-plus';
</script>
<template>
    <div class="container">
        <div class="grid-actions">
            <Pager :count="data.dataCount" :page="currentpage" :pagesize="parseInt(pagesize)" @go-to="switchPage" />
            <ElSelect v-model="pagesize" v-if="shouldDisplayPager()">
                <ElOption value="10" label="10"/>
                <ElOption value="25" label="25" v-if="data.dataCount > 25"/>
                <ElOption value="50" label="50" v-if="data.dataCount > 50"/>
                <ElOption value="100" label="100" v-if="data.dataCount > 100"/>
                <ElOption value="250" label="250" v-if="data.dataCount > 250"/>
                <ElOption value="0" label="All" v-if="data.dataCount < 500"/>
            </ElSelect>
            <ElButton type="primary" @click="addRow">Add</ElButton>
        </div>
        <MemberGrid :page="currentpage" :pagesize="parseInt(pagesize)" @on-delete="onDeleteMember"/>
    </div>
</template>