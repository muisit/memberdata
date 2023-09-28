import { ref } from 'vue'
import type { Ref } from 'vue';
import { defineStore } from 'pinia'
import {
    getConfiguration as getConfigurationAPI, saveConfiguration as saveConfigurationAPI,
    getData as getDataAPI, saveAttribute as saveAttributeAPI, saveMember as saveMemberAPI, deleteMember as deleteMemberAPI
} from '../lib/api.js';

export interface Attribute {
    id: number;
    name: string;
    type: string;
    rules?: string;
    options?: any;
    optdefault?: any;
}

export interface Member {
    id: number;
    [key:string]: string|number;
}

export interface AttributeByKey {
    [key:string]: Attribute;
}

export const useDataStore = defineStore('data', () => {
    const nonce = ref('');
    const baseUrl = ref('');
    const types:Ref<AttributeByKey> = ref({});
    const configuration:Ref<Array<Attribute>> = ref([]);
    const dataList:Ref<Array<Member>> = ref([]);
    const dataCount = ref(0);

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
                        id: attribute.id,
                        name: attribute.name,
                        type: attribute.type,
                        rules: attribute.rules,
                        options: attribute.options
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
        var maxid=1;
        configuration.value.forEach((attribute) => {
            if (attribute.id >= maxid) {
                maxid = attribute.id + 1;
            }
        });
        data.id = maxid;
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

    function getData(offset:number, pagesize:number, filter:string, sorter:string, sortDirection: string)
    {
        return getDataAPI(offset, pagesize, filter, sorter, sortDirection)
            .then((data:any) => {
                if (data.data) {
                    dataCount.value = parseInt(data.data.total);
                    dataList.value = data.data.list;
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

    function saveMember(member:Member): Array<string>
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
        dataList.value.forEach((member) => {
            if (member.id <= newId) {
                newId = member.id - 1;
            }
        });
        dataList.value.push({id: newId});
        saveAttribute(newId, '', '')
            .then((data) => {
                if (data && data.data && data.data.id) {
                    var newList = dataList.value.map((member) => {
                        if (member.id == newId) {
                            member.id = data.data.id;
                        }
                        return member;
                    });
                    dataList.value = newList;
                }
            });
    }

    function updateMember(member:Member)
    {
        dataList.value = dataList.value.map((m) => {
            if (m.id == member.id) {
                return member;
            }
            return m;
        });
    }

    function deleteMember(member:Member)
    {
        return deleteMemberAPI(member)
            .catch((e) => {
                console.log(e);
                alert("There was a network problem while deleting this entry. Please reload the page and try again");
            })
    }

    return {
        nonce, baseUrl,
        configuration, types,
        getConfiguration, saveConfiguration, addAttribute, updateAttribute,

        dataCount, dataList,
        getData, saveAttribute, saveMember, addNewMember, updateMember, deleteMember
    }
})
