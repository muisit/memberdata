<script lang="ts" setup>
import type { Attribute, Member } from "../lib/types";
const props = defineProps<{
    attribute: Attribute;
    member: Member;
}>();

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
