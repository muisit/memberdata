<script lang="ts" setup>
import type { Member } from '../stores/data';
const props = defineProps<{
    dataList: Array<Member>;
    member: object;
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
            @on-delete="$emit('onDelete', member)"
            @on-edit="$emit('onEdit', member)"
        />
    </tbody>    
</template>