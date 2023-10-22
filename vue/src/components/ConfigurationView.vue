<script lang="ts" setup>
import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { getBasicConfiguration } from '@/lib/api';
import { random_token } from '@/lib/functions';
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
                    basicConfiguration.value = data.data.attributes.map((a:Attribute) => {
                        a.token = random_token();
                        a.originalName = a.name;
                        return a;
                    });
                }
                disableButton.value = true;
            });
        }
    },
    {immediate: true}
)

function onDelete(attribute:Attribute)
{
    if (confirm('Are you sure you want to remove this attribute from the list?')) {
        basicConfiguration.value = basicConfiguration.value.filter((a) => a.token != attribute.token);
        disableButton.value = false;
    }
}

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

interface TokenByName {
    [key:string]: Array<string|undefined>;
}

function saveAll()
{
    disableButton.value = true;

    let messages:Array<string> = [];
    let namesByToken:TokenByName = {};
    basicConfiguration.value.forEach((a:Attribute) => {
        let name = ('' + a.name).trim();

        if (name != a.name) {
            messages.push("Attribute '" + name + "' has white space before or after. Please remove the whitespace in this name");
        }
        else if (name.length == 0) {
            messages.push("Attribute with empty name found. Please provide names with at least 1 character");
        }
        else {
            if (!namesByToken[name]) {
                namesByToken[name] = [];
            }
            namesByToken[name].push(a.token);
            if (!namesByToken[a.originalName || name]) {
                namesByToken[a.originalName || name] = [];
            }
            if (!namesByToken[a.originalName || name].includes(a.token)) {
                namesByToken[a.originalName || name].push(a.token);
            }
        }
    });

    Object.keys(namesByToken).forEach((name) => {
        // assume we have no undefined tokens here
        if (namesByToken[name].length > 1) {
            messages.push("Found duplicate name for attribute: ''" + name + "'. You cannot have two attributes named the same. You also cannot rename attributes to a name currently in use. Please adjust the name.");
        }
    });

    if (messages.length) {
        alert(messages.join("\r\n"));
    }
    else {
        data.saveConfiguration(basicConfiguration.value)
            .then(() => {
                alert("Configuration updated succesfully");
            });
    }
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
            <div class="save-button">
                <ElButton @click="saveAll" type='primary' :disabled="disableButton">Save</ElButton>
            </div>

            <draggable
                v-model="basicConfiguration" 
                handle=".attribute-handle"
                @start="dragStart" 
                item-key="id">
                <template #item="{element}">
                    <AttributeConfiguration
                    :attribute="element"
                    @on-update="(field) => onUpdate(element, field)"
                    @on-delete="() => onDelete(element)" />
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