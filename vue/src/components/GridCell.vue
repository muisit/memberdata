<script lang="ts" setup>
const props = defineProps<{
    attribute: object;
    member: object;
}>();
//import { useDataStore } from '@/stores/data';
//const data = useDataStore();

function alignRight()
{
    return ['int', 'number'].includes(props.attribute.type);
}

</script>
<template>
    <td :class="{'text-left': !alignRight(), 'text-right': alignRight()}">
        {{ props.member[props.attribute.name] }}
    </td>
</template>
