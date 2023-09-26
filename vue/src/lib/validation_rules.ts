import type {Attribute, Member} from '@/stores/data';
import validator from 'validator';
import { dayjs } from 'element-plus';

interface RuleValidationResult {
    value: string;
    message?: string;
}

export function validateAttribute(attribute:Attribute, value:string): Array<string>
{
    var rules = (attribute.rules || '').split('|');
    var foundSkip = false;
    var messages:Array<string> = rules.map((rule) => {
        if (rule == 'skip') {
            foundSkip = true;
        }
        var result = validateRule(rule, attribute, value, rules)
        value = result.value;
        if (result.message) {
            return result.message;
        }
        return null;
    })
    .filter((msg) => msg !== null);
    return foundSkip ? [] : messages;
}

function validateRule(rule:string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult
{
    var elements = rule.split('=', 1);
    if (ruleImplementations[elements[0]]) {
        var ruleName = elements[0];
        if (ruleName == 'max') ruleName = 'lte';
        if (ruleName == 'min') ruleName = 'gte';
        return ruleImplementations[ruleName](elements[1] || '', attribute, value, rules);
    }
    return {value: value};
}

function convertDateToDayJSFormat(format:string)
{
    var retval = '';
    for (var i = 0; i < format.length; i++) {
        var c = format[i];
        switch (c) {
            case 'a': retval += 'a'; break;
            case 'A': retval += 'A'; break;
            case 'y': retval += 'YY'; break;
            case 'Y': retval += 'YYYY'; break;
            case 'n': retval += 'M'; break;
            case 'm': retval += 'MM'; break;
            case 'j': retval += 'D'; break;
            case 'd': retval += 'DD'; break;
            case 'H': retval += 'HH'; break;
            case 'h': retval += 'H'; break;
            case 'G': retval += 'hh'; break;
            case 'g': retval += 'h'; break;
            case 'i': retval += 'mm'; break;
            case 's': retval += 'ss'; break;
            case 'P': retval += 'Z'; break;
            default: retval += c; break;
        }
    }
    return retval;
}

function comparisonFunc(params: string, attribute:Attribute, value:string, rules:Array<string>, callback:Function): RuleValidationResult {
    if (rules.includes('int')) {
        var limit = parseInt(params);
        var val = parseInt(value);
        return callback(attribute, 'value', val, limit);
    }
    else if (rules.includes('float')) {
        var limit = parseFloat(params);
        var val = parseFloat(value);
        if (!isNaN(limit) && !isNaN(val)) {
            return callback(attribute, 'value', val, limit);
        }
    }
    else if(rules.includes('date') || rules.includes('datetime')) {
        var dt = dayjs(value, convertDateToDayJSFormat(attribute.options));
        var dt2 = dayjs(params, convertDateToDayJSFormat(attribute.options));
        if (dt.isValid() && dt2.isValid()) {
            return callback(attribute, 'date', dt, dt2);
        }
    }
    else {
        var limit = parseInt(params);
        var val = value.length;
        return callback(attribute, 'length', val, limit);
    }
    return {value: value};
}

interface RuleImplementationObject {
    [key:string]: Function
}
const ruleImplementations:RuleImplementationObject = {
    "required": function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        if ((typeof value == undefined) || !value || value.length == 0) {
            return {value: value, message: attribute.name + ' is a required field'};
        }
        return {value: value};
    },
    "nullable": function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value};
    },
    "fail": function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value, message: attribute.name + ' always fails'};
    },
    "skip": function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value};
    },
    'email': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        if (!validator.isEmail(value)) {
            return {value: value, message: attribute.name + ' is not a valid e-mail address'};
        }
        return {value: value};
    },
    'url': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        if (!validator.isURL(value)) {
            return {value: value, message: attribute.name + ' is not a valid URL'};
        }
        return {value: value};
    },
    'date': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        var dt = dayjs(value, convertDateToDayJSFormat(attribute.options));
        if (!dt.isValid()) {
            return {value: value, message: attribute.name + ' is not a valid date'};
        }
        return {value: value};
    },
    'datetime': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        var dt = dayjs(value, convertDateToDayJSFormat(attribute.options));
        if (!dt.isValid()) {
            return {value: value, message: attribute.name + ' is not a valid date + time'};
        }
        return {value: value};
    },
    'enum': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        var options = (attribute.options || '').split('|')
        if (!options.includes(value)) {
            return {value: value, message: attribute.name + ' must be one of the allowed values'};
        }
        return {value: value};
    },
    'lte': function (params: string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult {
        return comparisonFunc(params, attribute, value, rules, (attribute:Attribute, tp: string, val: number|Object, limit: number|Object) => {
            switch (tp) {
                case 'value':
                    if (val > limit) {
                        return {value: value, message: attribute.name + ' must smaller than or equal to ' + limit};
                    }
                    break;
                case 'length':
                    if (val > limit) {
                        return {value: value, message: attribute.name + ' must not exceed ' + limit + ' in length'};
                    }
                    break;
                case 'date':
                    if (val.isAfter(limit)) {
                        return {value: value, message: attribute.name + ' must not be after ' + limit.format(convertDateToDayJSFormat(attribute.options))};
                    }
                    break;
            }
            return {value: value};
        });
    },
    'lt': function (params: string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult {
        return comparisonFunc(params, attribute, value, rules, (attribute:Attribute, tp: string, val: number|Object, limit: number|Object) => {
            switch (tp) {
                case 'value':
                    if (val >= limit) {
                        return {value: value, message: attribute.name + ' must be smaller than ' + limit};
                    }
                    break;
                case 'length':
                    if (val >= limit) {
                        return {value: value, message: attribute.name + ' must be smaller than ' + limit + ' in length'};
                    }
                    break;
                case 'date':
                    if (!val.isBefore(limit)) {
                        return {value: value, message: attribute.name + ' must be before ' + limit.format(convertDateToDayJSFormat(attribute.options))};
                    }
                    break;
            }
            return {value: value};
        });
    },
    'eq': function (params: string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult {
        return comparisonFunc(params, attribute, value, rules, (attribute:Attribute, tp: string, val: number|Object, limit: number|Object) => {
            switch (tp) {
                case 'value':
                    if (val == limit) {
                        return {value: value, message: attribute.name + ' must be equal to ' + limit};
                    }
                    break;
                case 'length':
                    if (val == limit) {
                        return {value: value, message: attribute.name + ' must contain exactly ' + limit + ' characters'};
                    }
                    break;
                case 'date':
                    if (!val.isSame(limit)) {
                        return {value: value, message: attribute.name + ' must be ' + limit.format(convertDateToDayJSFormat(attribute.options))};
                    }
                    break;
            }
            return {value: value};
        });
    },
    'gt': function (params: string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult {
        return comparisonFunc(params, attribute, value, rules, (attribute:Attribute, tp: string, val: number|Object, limit: number|Object) => {
            switch (tp) {
                case 'value':
                    if (val <= limit) {
                        return {value: value, message: attribute.name + ' must be greater than ' + limit};
                    }
                    break;
                case 'length':
                    if (val <= limit) {
                        return {value: value, message: attribute.name + ' must be greater than ' + limit + ' in length'};
                    }
                    break;
                case 'date':
                    if (!val.isAfter(limit)) {
                        return {value: value, message: attribute.name + ' must be after ' + limit.format(convertDateToDayJSFormat(attribute.options))};
                    }
                    break;
            }
            return {value: value};
        });
    },
    'gte': function (params: string, attribute:Attribute, value:string, rules:Array<string>): RuleValidationResult {
        return comparisonFunc(params, attribute, value, rules, (attribute:Attribute, tp: string, val: number|Object, limit: number|Object) => {
            switch (tp) {
                case 'value':
                    if (val < limit) {
                        return {value: value, message: attribute.name + ' must be greater than or equal to ' + limit};
                    }
                    break;
                case 'length':
                    if (val < limit) {
                        return {value: value, message: attribute.name + ' must not be smaller than ' + limit + ' in length'};
                    }
                    break;
                case 'date':
                    if (val.isBefore(limit)) {
                        return {value: value, message: attribute.name + ' must not be before ' + limit.format(convertDateToDayJSFormat(attribute.options))};
                    }
                    break;
            }
            return {value: value};
        });
    },
    'int': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: '' + parseInt(value)};
    },
    'float': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: '' + parseFloat(value)};
    },
    'bool': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: ['t','y','yes','true','on','1'].includes(value.toLocaleLowerCase()) ? 'Y' : 'N'};
    },
    'trim': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value.trim()};
    },
    'upper': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value.toUpperCase()};
    },
    'lower': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value.toLowerCase()};
    },
    'ucfirst': function (params: string, attribute:Attribute, value:string): RuleValidationResult {
        return {value: value[0].toUpperCase() + value.substring(1)};
    }
}
