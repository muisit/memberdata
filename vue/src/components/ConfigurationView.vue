<script lang="ts" setup>
import { ref, watch } from 'vue';
const props = defineProps<{
    index:string
}>();

import { useDataStore } from '../stores/data';
const data = useDataStore();

const disableButton = ref(true);
watch(
    () => props.index,
    (nw) => {
        if (nw == 'settings') {
            data.getConfiguration().then(() => {
                disableButton.value = true;
            });
        }
    },
    {immediate: true}
)

function onUpdate(attribute, field)
{
    switch (field.field) {
        case 'name':
        case 'type':
        case 'rules':
        case 'options':
            attribute[field.field] = field.value;
            break;
    }
    disableButton.value = false;
    data.updateAttribute(attribute);
}

function saveAll()
{
    disableButton.value = true;
    data.saveConfiguration()
        .then(() => {
            alert("Configuration updated succesfully");
        });
}

function add() {
    var type = data.types["text"];
    data.addAttribute({id: 0, name: '', type: 'text', rules: type.rules, options: type.optdefault});
    disableButton.value = false;
}

function dragStart()
{
    disableButton.value = false;
}

const showRulesDialog = ref(false);

import AttributeConfiguration from './AttributeConfiguration.vue';
import RulesInfoDialog from './RulesInfoDialog.vue';
import { ElButton, ElIcon } from 'element-plus';
import { QuestionFilled } from '@element-plus/icons-vue';
import draggable from 'vuedraggable';
</script>
<template>
    <div class="configuration-header">
        <ElIcon size="large" @click="() => showRulesDialog = true">
            <QuestionFilled/>
        </ElIcon>
        <RulesInfoDialog :visible="showRulesDialog" @on-close="() => showRulesDialog = false"/>
    </div>
    <div class="configuration-view">
        <draggable
            v-model="data.configuration" 
            handle=".attribute-handle"
            @start="dragStart" 
            item-key="id">
            <template #item="{element}">
                <AttributeConfiguration
                :attribute="element"
                @on-update="(field) => onUpdate(element, field)" />
            </template>

            <template #header>
                <div class="add-button">
                    <ElButton @click="add">Add Attribute</ElButton>
                </div>
            </template>
        </draggable>

        <div class="save-button">
            <ElButton @click="saveAll" type='primary' :disabled="disableButton">Save</ElButton>
        </div>
    </div> 
</template>