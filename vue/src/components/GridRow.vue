<script lang="ts" setup>
import type { Member } from '../lib/types';
const props = defineProps<{
    member: Member;
}>();
const emits = defineEmits(['onEdit', 'onDelete']);

import { useDataStore } from '../stores/data';
const data = useDataStore();

function deleteEntry()
{
    emits("onDelete");
}

function editEntry()
{
    emits("onEdit");
}

import GridCell from './GridCell.vue';
import { ElIcon } from 'element-plus';
import { Edit, DeleteFilled } from '@element-plus/icons-vue';
</script>
<template>
    <tr>
        <td>
            <ElIcon size="large"><DeleteFilled @click="deleteEntry"/></ElIcon>
        </td>
        <td>
            <ElIcon size="large"><Edit @click="editEntry" /></ElIcon>
        </td>
        <GridCell :attribute="{name: 'id', type: 'int', filter: 'N'}" :member="props.member"/>
        <GridCell v-for="attribute in data.configuration" :key="attribute.name" :attribute="attribute" :member="props.member"/>
    </tr>
</template>