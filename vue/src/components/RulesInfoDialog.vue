<script lang="ts" setup>
const props = defineProps<{
    visible:boolean;
}>();
const emits = defineEmits(['onClose']);

function closeForm()
{
    emits('onClose');
}

import { ElButton, ElDialog } from 'element-plus'
</script>
<template>
    <ElDialog :model-value="props.visible" title="Rules Configuration" :before-close="(done) => { closeForm(); done(false); }">
        <h1>Rules Configuration</h1>
        <p>
            The rules element contains a list of validation rules to apply to the
            attribute input field. Some rules can have a parameter, which is specified
            like: 'max=12' or 'lte=30.2'. Rules are separated by a bar ('|').<br/>
            Rules are applied after converting the value using any formatting specified
            in the options field, if applicable.<br/>
            The following rules are available:
            <h3>Transformation Rules</h3>
            <ul>
                <li><b>int</b>: value is converted to a whole number (integer)</li>
                <li><b>float</b>: value is converted to a floating-point number</li>
                <li><b>bool</b>: value is set to 'Y' for entries containing the text 'yes', 'true', 'on', 't', 'y' (case insensitive), and set to 'N' otherwise</li>
                <li><b>trim</b>: whitespace is trimmed from the value left and right</li>
                <li><b>upper</b>: value is converted to upper case</li>
                <li><b>lower</b>: value is converted to lower case</li>
                <li><b>ucfirst</b>: first letter is converted to upper case</li>
            </ul>

            <h3>Length or Size Rules</h3>            
            <ul>
                <li><b>lte</b>: value has a length (text), value (numeric) or date less than or equal to the parameter</li>
                <li><b>lt</b>: value has a length (text), value (numeric) or date less than the parameter</li>
                <li><b>eq</b>: value has a length (text), value (numeric) or date equal to the parameter</li>
                <li><b>gt</b>: value has a length (text), value (numeric) or date greater than the parameter</li>
                <li><b>gte</b>: value has a length (text), value (numeric) or date greater than or equal to the parameter</li>
                <li><b>min</b>: alias for <b>gte</b></li>
                <li><b>max</b>: alias for <b>lte</b></li>
            </ul>

            <h3>Other Rules</h3>
            <ul>
                <li><b>required</b>: marks the field as a required entry, cannot be left blank</li>
                <li><b>nullable</b>: marks the field as not-required, allowed blank (same as leaving out 'required')</li>
                <li><b>fail</b>: always fails field validation (not usable in this context)</li>
                <li><b>skip</b>: skip validation on this field</li>
                <li><b>email</b>: value is validated to be an e-mail address</li>
                <li><b>url</b>: value is validated to be a URL/website address</li>
                <li><b>date</b>: value is validated to be a date, input field uses the format specified in the options</li>
                <li><b>datetime</b>: value is validated to be a date + time (after 1970-01-01), input field uses the format specified in the options</li>
                <li><b>enum</b>: value is one of a list of allowed values, input field uses the bar-separated list in the options</li>
            </ul>
        </p>
        <template #footer>
            <span class="dialog-footer">
                <ElButton type="primary" @click="closeForm">Close</ElButton>
            </span>
        </template>
    </ElDialog>    
</template>