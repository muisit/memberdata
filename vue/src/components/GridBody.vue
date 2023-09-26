<script lang="ts" setup>
const emits = defineEmits(['onEdit', 'onDelete']);
import { useDataStore } from '../stores/data';
const data = useDataStore();

import GridRow from './GridRow.vue';
</script>
<template>
    <tbody>
        <GridRow
            v-for="member in data.dataList"
            :key="member.id"
            :member="member"
            @on-delete="$emit('onDelete', member)"
            @on-edit="$emit('onEdit', member)"
        />
    </tbody>    
</template>