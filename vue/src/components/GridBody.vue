<script lang="ts" setup>
import type { Member } from '../lib/types';
const props = defineProps<{
    dataList: Array<Member>;
}>();
const emits = defineEmits(['onEdit', 'onDelete']);

import GridRow from './GridRow.vue';
</script>
<template>
    <tbody>
        <GridRow
            v-for="member in props.dataList"
            :key="member.id"
            :member="member"
            @on-delete="emits('onDelete', member)"
            @on-edit="emits('onEdit', member)"
        />
    </tbody>    
</template>