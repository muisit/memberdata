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
const pagesize = ref(0);
const filter = ref('');
const sorter = ref('id');
const sortdir = ref('asc');
watch(
    () => props.index,
    (nw) => {
        if (nw == 'data') {
            data.getData(offset.value, pagesize.value, filter.value, sorter.value, sortdir.value);
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
            .then((retval) => {
                console.log('delete data', retval);
                if (retval.data && retval.success && !retval.data.error) {
                    console.log('reloading data based on settings');
                    data.getData(offset.value, pagesize.value, filter.value, sorter.value, sortdir.value);
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
        .then((data) => {
            if (data && data.length) {
                alert("There were back-end validation issues. Data was not completely saved.\r\n" + data.join('\r\n'));
            }
            else {
                updateDialogVisible.value = false;
                data.updateMember(selectedMember);
                selectedMember.value = {id:0};
            }
        });
}

function updateMember(fieldDef:any)
{
    selectedMember.value[fieldDef.attribute.name] = fieldDef.value;
}

import GridHeader from './GridHeader.vue';
import GridBody from './GridBody.vue';
import MemberUpdateDialog from './MemberUpdateDialog.vue';
import { ElButton } from 'element-plus';
</script>
<template>
    <div class="grid-actions">
        <ElButton type="primary" @click="addRow">Add</ElButton>
    </div>
    <div class="data-grid">
        <table>
            <GridHeader />
            <GridBody @on-edit="onEdit" @on-delete="onDelete"/>
        </table>
        <MemberUpdateDialog :member="selectedMember" :visible="updateDialogVisible" @on-close="closeUpdate" @on-save="saveUpdate" @on-update="updateMember" />
    </div>
</template>