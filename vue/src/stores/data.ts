import { ref } from 'vue'
import type { Ref } from 'vue';
import { defineStore } from 'pinia'
import {
    getConfiguration as getConfigurationAPI, saveConfiguration as saveConfigurationAPI,
    getData as getDataAPI, saveAttribute as saveAttributeAPI, saveMember as saveMemberAPI, deleteMember as deleteMemberAPI,
    exportData as exportDataAPI
} from '../lib/api.js';
import { sort_members } from '../lib/sort_members';
import { filter_members } from '../lib/filter_members.js';

export interface Attribute {
    name: string;
    type: string;
    rules?: string;
    options?: any;
    optdefault?: any;
    filter: string;
}

export interface Member {
    id: number;
    [key:string]: string|number;
}

export interface AttributeByKey {
    [key:string]: Attribute;
}

export interface FilterByKey {
    [key:string]: Array<string|null>;
}

export interface FilterSpec {
    search: string|null;
    values: Array<string|null>;
}

export interface FilterSpecByKey {
    [key:string]: FilterSpec;
}

export const useDataStore = defineStore('data', () => {
    const nonce = ref('');
    const baseUrl = ref('');
    const types:Ref<AttributeByKey> = ref({});
    const configuration:Ref<Array<Attribute>> = ref([]);
    const dataList:Ref<Array<Member>> = ref([]);
    const originalData:Ref<Array<Member>> = ref([]);
    const dataCount = ref(0);
    const dataFilters:Ref<FilterByKey> = ref({});

    function getConfiguration()
    {
        return getConfigurationAPI().then((data:any) => {
            if (data.data) {
                types.value = data.data.types;
                configuration.value = data.data.attributes;
            }
            else {
                throw new Error("invalid return data");
            }
        })
        .catch((e) => {
            console.log(e);
            alert('There was a network error, please reload the page');
        });
    }

    function saveConfiguration()
    {
        var toSaveObject:Array<Attribute> = [];
        var allowedTypes = Object.keys(types.value);
        var attributeNames:Array<string> = [];
        configuration.value.forEach((attribute:Attribute) => {
            if (attribute.name && attribute.name.length > 0 && attribute.type) {
                if (allowedTypes.includes(attribute.type) && !attributeNames.includes(attribute.name)) {
                    toSaveObject.push({
                        name: attribute.name,
                        type: attribute.type,
                        rules: attribute.rules,
                        options: attribute.options,
                        filter: attribute.filter
                    });
                }
            }
        });
        configuration.value = toSaveObject;

        return saveConfigurationAPI(toSaveObject)
            .catch((e) => {
                console.log(e);
                alert("There was an error storing the data, please reload the page and try again");
            });
    }

    function addAttribute(data:Attribute)
    {
        configuration.value.push(data);
    }

    function updateAttribute(data:Attribute)
    {
        var newConfig = configuration.value.map((attribute) => {
            if (attribute.name == data.name) {
                return data;
            }
            return attribute;
        });
        configuration.value = newConfig;
    }

    function hasEmptyFilter(filter:FilterSpecByKey)
    {
        var retval = true;
        Object.keys(filter).forEach((name) => {
            var search = (filter[name].search || '').trim();
            if (search.length > 0 || filter[name].values.length) {
                retval = false;
            }
        });
        return retval;
    }

    function getData(offset:number, pagesize:number, filter:FilterSpecByKey, sorter:string, sortDirection: string, cutoff: number, cb:Function|null = null)
    {      
        return getDataAPI(offset, pagesize, filter, sorter, sortDirection, cutoff)
            .then((data:any) => {
                // if we have data and the callback indicates our filter/sorting is still applicable, make the changes
                if (data.data) {
                    if (!cb || cb()) {
                        dataCount.value = parseInt(data.data.total);
                        dataList.value = data.data.list;
                        if (hasEmptyFilter(filter)) {
                            originalData.value = data.data.list;
                        }
                    }
                    dataFilters.value = data.data.filters || {};
                }
                else {
                    throw new Error("Invalid data returned");
                }
            })
            .catch((e) => {
                console.log(e);
                alert("There was an error retrieving data. Please reload the page");
            });
    }

    function exportData(filter:FilterSpecByKey, sorter:string, sortDirection: string)
    {
        return exportDataAPI("export.xslx", filter, sorter, sortDirection);
    }

    function saveMember(member:Member)
    {
        return saveMemberAPI(member)
            .then((data:any) => {
                if (data.data && data.data.messages) {
                    return data.data.messages;
                }
                return [];
            });
    }

    function saveAttribute(id:number, attribute:string, value:string)
    {
        return saveAttributeAPI(id, attribute, value)
            .catch((e) => {
                console.log(e);
                alert('There was an error saving the data for attribute ' + attribute + ". Please reload and try again");
            });
    }

    function addNewMember()
    {
        var newId = -1;
        originalData.value.forEach((member) => {
            if (member.id <= newId) {
                newId = member.id - 1;
            }
        });
        originalData.value.push({id: newId});
        saveAttribute(newId, '', '')
            .then((data) => {
                if (data && data.data && data.data.id) {
                    var newList = originalData.value.map((member) => {
                        if (member.id == newId) {
                            member.id = data.data.id;
                            dataCount.value += 1; // succesfully added a new member
                        }
                        return member;
                    });
                    originalData.value = newList;
                }
            });
    }

    function updateMember(member:Member)
    {
        originalData.value = originalData.value.map((m) => {
            if (m.id == member.id) {
                return member;
            }
            return m;
        });        
    }

    function deleteMember(member:Member)
    {
        return deleteMemberAPI(member)
            .then((data:any) => {
                if (data.data && data.success) {
                    originalData.value = originalData.value.filter((item) => item.id != member.id);
                    dataCount.value -= 1;
                }
            })
            .catch((e) => {
                console.log(e);
                alert("There was a network problem while deleting this entry. Please reload the page and try again");
            });
    }

    function applyPagerSorterFilter(offset:number, pagesize:number, filter:FilterSpecByKey, sorter:string, sortdir: string)
    {
        var attr:Attribute = { name: 'id', type: 'int', filter: 'N'};
        configuration.value.forEach((a) => {
            if (a.name == sorter) {
                attr = a;
            }
        });

        console.log('converting originalData list containing ',originalData.value.length,'entries to filtered and sorted list');
        dataList.value = originalData.value
            .slice()
            .sort((m1: Member, m2:Member) => sort_members(m1, m2, sorter, sortdir, attr))
            .filter((v:Member) => filter_members(v, filter));
    }

    return {
        nonce, baseUrl,
        configuration, types,
        getConfiguration, saveConfiguration, addAttribute, updateAttribute,

        dataCount, dataList, originalData, dataFilters,
        getData, exportData, saveAttribute, saveMember, addNewMember, updateMember, deleteMember,
        applyPagerSorterFilter
    }
})
