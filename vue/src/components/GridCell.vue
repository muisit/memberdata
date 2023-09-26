<script lang="ts" setup>
const props = defineProps<{
    attribute: object;
    member: object;
}>();
import { useDataStore } from '@/stores/data';
const data = useDataStore();

</script>
<template>
    <td>
        {{ props.member[props.attribute.name] }}
    </td>
</template>
