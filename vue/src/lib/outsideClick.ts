import {onBeforeUnmount, onMounted} from 'vue'

export function useDetectOutsideClick(component:any, callback:Function) {
    if (!component) return;
    const listener = (event:any) => {
        if (component.value && event.composedPath().includes(component.value)) {
            return;
        }
        if (component.value && typeof callback === 'function') {
            callback();
        }
    }
    onMounted(() => { window.addEventListener('click', listener) })
    onBeforeUnmount(() => {window.removeEventListener('click', listener)})

    return {listener}
}
