import type { Attribute, Member } from '../stores/data';
import { dayjs } from 'element-plus';

export function sort_members(m1:Member, m2:Member, sorter:string, sortdir: string, attr:Attribute): number 
{
    var a1 = m1[sorter] || '';
    var a2 = m2[sorter] || '';
    if (a1 == '') a1 = null;
    if (a2 == '') a2 = null;

    if (a1 && !a2) return -1;
    if (!a1 && a2) return 1;
    if (!a1 && !a2) return 0;

    if (attr && attr.type == 'int') {
        var v1 = parseInt(a1);
        var v2 = parseInt(a2);

        if (!isNaN(v1) && isNaN(v2)) return 1;
        if (isNaN(v1) && !isNaN(v2)) return -1;
        if (isNaN(v1) && isNaN(v2)) return 0;

        if (v1 == v2) return 0;
        if (v1 < v2) return sortdir == 'asc' ? -1 : 1;
        if (v1 > v2) return sortdir == 'asc' ? 1 : -1;
    }
    else if (attr && (attr.type == 'int' || attr.type == 'money')) {
        var v1 = parseFloat(a1);
        var v2 = parseFloat(a2);

        if (!isNaN(v1) && isNaN(v2)) return 1;
        if (isNaN(v1) && !isNaN(v2)) return -1;
        if (isNaN(v1) && isNaN(v2)) return 0;

        if (v1 == v2) return 0;
        if (v1 < v2) return sortdir == 'asc' ? -1 : 1;
        if (v1 > v2) return sortdir == 'asc' ? 1 : -1;
    }
    else if(attr && ['date', 'datetime'].includes(attr.type)) {
        var d1 = dayjs(a1, attr.options);
        var d2 = dayjs(a2, attr.options);

        if (d1.isValid() && !d2.isValid()) 1;
        if (!d1.isValid() && d2.isValid()) -1;
        if (!d1.isValid() && !d2.isValid()) return 0;

        if (d1.isBefore(d2)) return sortdir == 'asc' ? -1 : 1;
        if (d1.isAfter(d2)) return sortdir == 'asc' ? 1 : -1;
    }
    else {
        if(a1 < a2) return sortdir == 'asc' ? -1 : 1;
        if(a1 > a2) return sortdir == 'asc' ? 1 : -1;
    }
    return 0;
}