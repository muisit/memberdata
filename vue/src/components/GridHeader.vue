<script lang="ts" setup>
import { useDataStore } from '../stores/data';
const data = useDataStore();
</script>
<template>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th>#</th>
            <th v-for="attribute in data.configuration" :key="attribute.id">
                {{ attribute.name }}
            </th>
        </tr>
    </thead>
</template>