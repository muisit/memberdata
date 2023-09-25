<script lang="ts" setup>
import { ref } from 'vue';
const props = defineProps<{
    nonce:string;
    url:string;
}>();

const data = useDataStore();
data.nonce = props.nonce;
data.baseUrl = props.url;

</script>
<template>
  <div>
    <h1>Memberdata Manager</h1>
  </div>
</template>
