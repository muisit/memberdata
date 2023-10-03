<script lang="ts" setup>
const props = defineProps<{
    count: number;
    page: number;
    pagesize: number;
}>();
const emits = defineEmits(['goTo']);

function pages(): Array<number|string>
{
    var retval = [];
    var pageCount = lastPage() + 1;
    if (pageCount < 10) {
        for (var i0 = 0; i0 < pageCount; i0++) {
            retval.push(i0);
        }
    }
    else {
        if (props.page < 5) {
            for (var i1 = 0; i1 < props.page; i1++) {
                retval.push(i1);
            }
        }
        else {
            retval.push(0);
            retval.push('...'); // mark separation
            retval.push(props.page - 3);
            retval.push(props.page - 2);
            retval.push(props.page - 1);
        }
        retval.push(props.page);

        if ((lastPage() - props.page) < 5) {
            for (var i2 = props.page + 1; i2 <= lastPage(); i2++) {
                retval.push(i2);
            }
        }
        else {
            retval.push(props.page + 1);
            retval.push(props.page + 2);
            retval.push(props.page + 3);
            retval.push('...'); // mark separation
            retval.push(lastPage());
        }
    }
    return retval;
}

function lastPage()
{
    if (props.pagesize <= 0) return 1;
    return Math.ceil(props.count / props.pagesize) - 1;
}

function goToFirst()
{
    emits('goTo', 0);
}

function goToLast()
{
    emits('goTo', lastPage());
}

function goToPrevious()
{
    emits('goTo', props.page - 1);
}

function goToNext()
{
    emits('goTo', props.page + 1);
}

import { ElIcon } from 'element-plus';
import { DArrowLeft, ArrowLeft, ArrowRight, DArrowRight } from '@element-plus/icons-vue';
</script>
<template>
    <div class="pager" v-if="props.count > props.pagesize && props.pagesize > 0">
        <div :class="{'page-button': true, 'disabled': props.page == 0}" @click="goToFirst()">
            <ElIcon size="large">
                <DArrowLeft/>
            </ElIcon>
        </div>
        <div :class="{'page-button': true, 'disabled': props.page == 0}" @click="goToPrevious()">
            <ElIcon size="large">
                <ArrowLeft/>
            </ElIcon>
        </div>

        <div v-for="pageNum in pages()" :key="pageNum" @click="$emit('goTo', pageNum)" 
            :class="{
                'page-button': true,
                'page-number': true,
                'active': props.page == pageNum,
                'separator': pageNum === '...'
        }">
            <span>{{ pageNum }}</span>
        </div>

        <div :class="{'page-button': true, 'disabled': props.page == lastPage()}" @click="goToNext()">
            <ElIcon size="large">
                <ArrowRight/>
            </ElIcon>
        </div>
        <div :class="{'page-button': true, 'disabled': props.page == lastPage()}" @click="goToLast()">
            <ElIcon size="large">
                <DArrowRight/>
            </ElIcon>
        </div>
    </div>
</template>