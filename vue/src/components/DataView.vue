<script lang="ts" setup>
import { ref, watch } from 'vue';
import type { Ref } from 'vue';
const props = defineProps<{
    index:string
}>();

import { useDataStore } from '../stores/data';
import type { Member } from '../stores/data';
const data = useDataStore();

const offset = ref(0);
const pagesize = ref('10');
const filter = ref('');
const sorter = ref('id');
const sortdir = ref('asc');

function updateData()
{
    data.getData(
        offset.value,
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
    () => [pagesize.value, filter.value, sorter.value, sortdir.value, offset.value],
    (nw) => {
        console.log("update of pagesize, offset, filter, sorter or sortdir")
        // if we do not have the whole list, use server side sorting and paging
        if (!hasWholeList()) {
            updateData();
        }
        else {
            // else use client side sorting and paging
            data.applyPagerSorterFilter(offset.value, parseInt(pagesize.value), filter.value, sorter.value, sortdir.value);
        }
    },
    { immediate: true }
)


function addRow()
{
    data.addNewMember();
}

const updateDialogVisible = ref(false);
const selectedMember:Ref<Member> = ref({id:0});
function onEdit(member:Member)
{
    updateDialogVisible.value = true;
    selectedMember.value = member;
}

function onDelete(member:Member)
{
    if (confirm("Delete this entry from the database? The data will be difficult to retrieve.")) {
        data.deleteMember(member)
            .then((retval:any) => {
                if (retval.data && retval.success && !retval.data.error) {
                    if (!hasWholeList()) {
                        // update the list to apply paging and sorting on the server side
                        updateData();
                    }
                    alert("Entry was succesfully removed");
                }
            });
    }
}

function closeUpdate()
{
    updateDialogVisible.value = false;
    selectedMember.value = {id:0};
}

function saveUpdate()
{
    data.saveMember(selectedMember.value)
        .then((result:any) => {
            if (result && result.length) {
                alert("There were back-end validation issues. Data was not completely saved.\r\n" + result.join('\r\n'));
            }
            else {
                updateDialogVisible.value = false;
                data.updateMember(selectedMember.value);
                selectedMember.value = {id:0};
            }
        });
}

function updateMember(fieldDef:any)
{
    selectedMember.value[fieldDef.attribute.name] = fieldDef.value;
}

function shouldDisplayPager()
{
    return  (data.dataCount > 25);
}

function getDataList()
{
    var retval = data.dataList;
    if (shouldDisplayPager()) {
        if (parseInt(pagesize.value) < retval.length) {
            var startIndex = offset.value;
            var dataCount = parseInt(pagesize.value) == 0 ? retval.length : parseInt(pagesize.value);
            if ((startIndex + dataCount) > retval.length) {
                dataCount = retval.length - startIndex;
            }
            retval = retval.slice(startIndex, startIndex + dataCount);
        }
    }
    return retval;
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
    offset.value = pagenum * parseInt(pagesize.value);
}

import GridHeader from './GridHeader.vue';
import GridBody from './GridBody.vue';
import MemberUpdateDialog from './MemberUpdateDialog.vue';
import Pager from './Pager.vue';
import { ElButton, ElSelect, ElOption } from 'element-plus';
</script>
<template>
    <div class="container">
        <div class="grid-actions">
            <Pager :count="data.dataCount" :offset="offset" :pagesize="pagesize" @go-to="switchPage" />
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
        <div class="data-grid">
            <div class="scroll-container">
                <div class="inner-container">
                    <table>
                        <GridHeader />
                        <GridBody @on-edit="onEdit" @on-delete="onDelete" :data-list="getDataList()"/>
                    </table>
                </div>
            </div>
            <MemberUpdateDialog :member="selectedMember" :visible="updateDialogVisible" @on-close="closeUpdate" @on-save="saveUpdate" @on-update="updateMember" />
        </div>
    </div>
</template>