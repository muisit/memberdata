<script lang="ts" setup>
const props = defineProps<{
    count: number;
    page: number;
    pagesize: number;
}>();
const emits = defineEmits(['goTo']);

function pages()
{
    var retval = [];
    var pageCount = lastPage() + 1;
    if (pageCount < 10) {
        for (var i = 0; i < pageCount; i++) {
            retval.push(i);
        }
    }
    else {
        if (props.page < 5) {
            for (var i = 0; i < props.page; i++) {
                retval.push(i);
            }
        }
        else {
            retval.push(0);
            retval.push(null); // mark separation
            retval.push(props.page - 3);
            retval.push(props.page - 2);
            retval.push(props.page - 1);
        }
        retval.push(props.page);

        if ((lastPage() - props.page) < 5) {
            for (var i = props.page + 1; i <= lastPage(); i++) {
                retval.push(i);
            }
        }
        else {
            retval.push(props.page + 1);
            retval.push(props.page + 2);
            retval.push(props.page + 3);
            retval.push(null); // mark separation
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
        {{ props.count }} / {{ props.pagesize }} / {{ props.page }}
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
                'separator': pageNum === null
        }">
            <span>{{ pageNum === null ? '...' : pageNum }}</span>
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