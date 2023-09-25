<script lang="ts" setup>
import { ref } from 'vue';
import { useDataStore } from './stores/data';
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
    <h1>Memberdata Frontend</h1>
  </div>
</template>
