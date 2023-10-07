<script lang="ts" setup>
import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { getBasicConfiguration } from '@/lib/api';
const props = defineProps<{
    index:string
}>();

import type  {FieldDefinition, Attribute } from '../lib/types';
import { useDataStore } from '../stores/data';
const data = useDataStore();
const basicConfiguration:Ref<Array<Attribute>> = ref([]);

const disableButton = ref(true);
watch(
    () => [props.index, data.currentSheet],
    (nw) => {
        if (nw[0] == 'settings') {
            getBasicConfiguration(data.currentSheet.id).then((data) => {
                if (data.data) {
                    basicConfiguration.value = data.data.attributes;
                }
                disableButton.value = true;
            });
        }
    },
    {immediate: true}
)

function onUpdate(attribute:Attribute, field:FieldDefinition)
{
    switch (field.field) {
        case 'name':
        case 'type':
        case 'rules':
        case 'options':
        case 'filter':
            attribute[field.field] = field.value;
            break;
    }
    disableButton.value = false;
}

function saveAll()
{
    disableButton.value = true;
    data.saveConfiguration(basicConfiguration.value)
        .then(() => {
            alert("Configuration updated succesfully");
        });
}

function add() {
    var type = data.types["text"];
    basicConfiguration.value.push({name: '', type: 'text', rules: type.rules, options: type.optdefault, filter: 'N'});
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
    <div class="configuration-view">
        <div class="configuration-header">
            <ElIcon size="large" @click="() => showRulesDialog = true">
                <QuestionFilled/>
            </ElIcon>
            <RulesInfoDialog :visible="showRulesDialog" @on-close="() => showRulesDialog = false"/>
        </div>
        <div class="configuration-view">
            <draggable
                v-model="basicConfiguration" 
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
    </div>
</template>