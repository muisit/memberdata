import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useDataStore = defineStore('data', () => {
  const nonce = ref('');
  const baseUrl = ref('');

  return { nonce, baseUrl }
})
