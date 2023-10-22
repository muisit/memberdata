import { ref, watch } from 'vue'
import type { Ref } from 'vue';
import { defineStore } from 'pinia'
import {
    getConfiguration as getConfigurationAPI, saveConfiguration as saveConfigurationAPI,
    getData as getDataAPI, addMember as addMemberAPI, updateMember as updateMemberAPI, deleteMember as deleteMemberAPI,
    exportData as exportDataAPI, getSheets as getSheetsAPI
} from '../lib/api';
import { is_valid } from '@/lib/functions';
import { sort_members } from '../lib/sort_members';
import { filter_members } from '../lib/filter_members.js';
import type { Attribute, Sheet, Member, FilterOptionsByAttribute, AttributeByKey, FilterSpecByKey, APIResult, SelectionSettings } from '../lib/types';

export const useDataStore = defineStore('data', () => {
    const nonce = ref('');
    const baseUrl = ref('');
    const types:Ref<AttributeByKey> = ref({});
    const configuration:Ref<Array<Attribute>> = ref([]);
    const dataList:Ref<Array<Member>> = ref([]);
    const originalData:Ref<Array<Member>> = ref([]);
    const dataCount = ref(0);
    const dataFilters:Ref<FilterOptionsByAttribute> = ref({});
    const currentSheet:Ref<Sheet> = ref({id: 0, name:''});
    const sheets:Ref<Array<Sheet>> = ref([]);
    const currentSelection:Ref<SelectionSettings> = ref({offset:0, pagesize:0, sorter:'', sortDirection:'asc', filter:{}, cutoff:0, callback: null});

    watch(
        () => currentSheet.value,
        (nw) => {
            getConfiguration(nw.id);
        }
    )


    function getSheets()
    {
        return getSheetsAPI()
            .then((data:APIResult) => {
                if (data.data && data.data.list) {
                    sheets.value = data.data.list;
                }
            })
            .catch((e) => {
                console.log(e);
                alert('There was a network error, please reload the page');
            });
    }

    function pickSheet(id:number)
    {
        currentSheet.value = {id:0, name: ''};
        sheets.value.forEach((s) => {
            if (s.id == id) {
                currentSheet.value = s;
            }
        });
        if (!is_valid(currentSheet.value.id) && sheets.value.length > 0) {
            currentSheet.value = sheets.value[0];
        }
    }

    function getConfiguration(id:number|null = null)
    {
        return getConfigurationAPI(id || currentSheet.value.id).then((data:any) => {
            if (data.data) {
                types.value = data.data.types;
                configuration.value = data.data.attributes;
            }
            else {
                throw new Error("invalid return data");
            }
        })
        .catch((e:any) => {
            console.log(e);
            alert('There was a network error, please reload the page');
        });
    }

    function saveConfiguration(config:Array<Attribute>)
    {
        const toSaveObject:Array<Attribute> = [];
        const allowedTypes = Object.keys(types.value);
        const attributeNames:Array<string> = [];
        config.forEach((attribute:Attribute) => {
            if (attribute.name && attribute.name.length > 0 && attribute.type) {
                if (allowedTypes.includes(attribute.type) && !attributeNames.includes(attribute.name)) {
                    toSaveObject.push({
                        name: attribute.name,
                        originalName: attribute.originalName,
                        type: attribute.type,
                        rules: attribute.rules,
                        options: attribute.options,
                        filter: attribute.filter
                    });
                }
            }
        });

        return saveConfigurationAPI(currentSheet.value.id, toSaveObject)
            .then(() => {
                // reload the full configuration for this sheet
                getConfiguration();
            })
            .catch((e:any) => {
                console.log(e);
                alert("There was an error storing the data, please reload the page and try again");
            });
    }

    function hasEmptyFilter(filter:FilterSpecByKey)
    {
        let retval = true;
        Object.keys(filter).forEach((name) => {
            const search = (filter[name].search || '').trim();
            if (search.length > 0 || filter[name].values.length) {
                retval = false;
            }
        });
        return retval;
    }

    function regetData()
    {
        return getDataAPI(
            currentSheet.value.id,
            currentSelection.value.offset,
            currentSelection.value.pagesize,
            currentSelection.value.filter,
            currentSelection.value.sorter,
            currentSelection.value.sortDirection,
            currentSelection.value.cutoff)
            .then((data:any) => {
                // if we have data and the callback indicates our filter/sorting is still applicable, make the changes
                if (data.data) {
                    if (!currentSelection.value.callback || currentSelection.value.callback()) {
                        dataList.value = data.data.list;
                        dataCount.value = parseInt(data.data.total);
                        originalData.value = []; // reset this to prevent hasWholeList returning true

                        // check that we are not filtering, in which case our local list may be too small
                        // if we are filtering 'back-side', assume we never filter front-side anymore
                        // No filters, no offset and the returned list is smaller than our cutoff
                        if (hasEmptyFilter(currentSelection.value.filter)
                           && dataCount.value < currentSelection.value.cutoff
                           && currentSelection.value.offset == 0) {
                            originalData.value = data.data.list; // copy in case we apply local paging/filtering
                        }
                    }
                    dataFilters.value = data.data.filters || {};
                }
                else {
                    throw new Error("Invalid data returned");
                }
            })
            .catch((e:any) => {
                console.log(e);
                alert("There was an error retrieving data. Please reload the page");
            });
    }

    function getData(offset:number, pagesize:number, filter:FilterSpecByKey, sorter:string, sortDirection: string, cutoff: number, cb:Function|null = null)
    {
        currentSelection.value = {
            offset: offset,
            pagesize: pagesize,
            filter: filter,
            sorter: sorter,
            sortDirection: sortDirection,
            cutoff: cutoff,
            callback: cb
        };
        return regetData();
    }

    function hasWholeList()
    {
        return originalData.value.length > 0 && originalData.value.length == dataCount.value;
    }
    

    function exportData(filter:FilterSpecByKey, sorter:string, sortDirection: string)
    {
        return exportDataAPI(currentSheet.value.id, filter, sorter, sortDirection);
    }

    function updateMember(member:Member)
    {
        return updateMemberAPI(member)
            .then((data:APIResult) => {
                if (data.data && data.data.messages) {
                    return data.data.messages;
                }
                return [];
            });
    }

    function addMember()
    {
        addMemberAPI(currentSheet.value.id)
            .then((data:APIResult|void) => {
                if (data && data.data && data.data.id) {
                    regetData();
                }
            });
    }


    function deleteMember(member:Member)
    {
        return deleteMemberAPI(member)
            .then((data:APIResult) => {
                if (data.data && data.success) {
                    regetData();
                }
            })
            .catch((e:any) => {
                console.log(e);
                alert("There was a network problem while deleting this entry. Please reload the page and try again");
            });
    }

    function applyPagerSorterFilter(offset:number, pagesize:number, filter:FilterSpecByKey, sorter:string, sortdir: string)
    {
        let attr:Attribute = { name: 'id', type: 'int', filter: 'N'};
        configuration.value.forEach((a) => {
            if (a.name == sorter) {
                attr = a;
            }
        });

        dataList.value = originalData.value
            .slice()
            .sort((m1: Member, m2:Member) => sort_members(m1, m2, sorter, sortdir, attr))
            .filter((v:Member) => filter_members(v, filter));
    }

    return {
        nonce, baseUrl,
        configuration, types, currentSheet, sheets,
        getSheets, pickSheet, getConfiguration, saveConfiguration,

        dataCount, dataList, dataFilters,
        getData, hasWholeList, exportData, addMember, updateMember, deleteMember,
        applyPagerSorterFilter
    }
})
