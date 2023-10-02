import type { Member, FilterSpecByKey } from "../stores/data";

export function filter_members(value: Member, filter:FilterSpecByKey)
{
    if (!Object.keys(filter)) return true;

    var retval = true;
    Object.keys(filter).forEach((attrname) => {
        // for each filter with a set of values, the value must match it, or the filter is false
        var foundMatch = false;
        var shouldSearch = false;
        if (filter[attrname].search && filter[attrname].search?.trim().length) {
            shouldSearch = true;
            var searchFor = filter[attrname].search?.toLowerCase().trim();
            if (value[attrname] && value[attrname].toString().toLowerCase().includes(searchFor || '')) {
                foundMatch = true;
            }
        }

        if (filter[attrname].values.length) {
            shouldSearch = true;
        filter[attrname].values.forEach((filterValue:string|null) => {
                if (filterValue) {
                    if (value[attrname] && value[attrname] == filterValue) {
                        foundMatch = true;
                    }
                }
                else if(filterValue == null && !value[attrname]) {
                    foundMatch = true;
                }
            });
        }

        if (shouldSearch && !foundMatch) {
            retval = false;
        }
    });

    return retval;
}