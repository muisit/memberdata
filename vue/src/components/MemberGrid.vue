<script lang="ts" setup>
import { ref } from 'vue';
import type { Ref } from 'vue';
const props = defineProps<{
    page: number;
    pagesize: number;
    sorter: string;
    sortdir: string;
    filter: FilterSpecByKey;
}>();
const emits = defineEmits(['onDelete', 'updateSorter', 'updateFilter']);

import { useDataStore } from '../stores/data';
import type { FilterSpecByKey, Member } from '../stores/data';
const data = useDataStore();

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
                    emits('onDelete');
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

function getDataList()
{
    var retval = data.dataList;
    if (props.pagesize < retval.length && props.pagesize > 0) {
        var startIndex = props.pagesize * props.page;
        var dataCount = props.pagesize;
        if ((startIndex + props.pagesize) > retval.length) {
            dataCount = retval.length - startIndex;
        }
        retval = retval.slice(startIndex, startIndex + dataCount);
    }
    return retval;
}

import GridHeader from './GridHeader.vue';
import GridBody from './GridBody.vue';
import MemberUpdateDialog from './MemberUpdateDialog.vue';
</script>
<template>
    <div class="data-grid">
        {{ props.sorter }} {{ props.sortdir}} / {{  props.filter }}
        <div class="scroll-container">
            <div class="inner-container">
                <table>
                    <GridHeader :sorter="props.sorter" :sortdir="props.sortdir" :filter="props.filter" @update-sorter="(v) => $emit('updateSorter', v)" @update-filter="(v) => $emit('updateFilter', v)" />
                    <GridBody @on-edit="onEdit" @on-delete="onDelete" :data-list="getDataList()"/>
                </table>
            </div>
        </div>
        <MemberUpdateDialog :member="selectedMember" :visible="updateDialogVisible" @on-close="closeUpdate" @on-save="saveUpdate" @on-update="updateMember" />
    </div>
</template>