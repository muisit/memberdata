let controller:any = null;
import { useDataStore } from '../stores/data';
import type { APIResult, Member } from './types';

export function abort_all_calls() {
    if(controller) {
        controller.abort();
        controller = null;
    }
}

function validateResponse() {
    return (res:Response) => {
        return res.json().then(json => {
            if (!json || !json.success) {
                if (json && (json.data && json.data.error)) {
                    throw new Error('API Validation');
                }
                else {
                    throw new Error("Network error, please try again");
                }
            }
            return json;
        })
    };
}

function getFileNameFromContentDispostionHeader(header:string|null): string {
    const contentDispostion = (header || '').split(';');
    const fileNameToken = `filename=`;

    let fileName = 'downloaded.dat';
    contentDispostion.forEach((thisValue) => {
        if (thisValue.trim().indexOf(fileNameToken) === 0) {
            fileName = decodeURIComponent(thisValue.replace(fileNameToken, ''));
        }
    });
    return fileName;
}

function attachmentResponse() {
    return async (res:Response) => {
        const blob = await res.blob();
        const filename = getFileNameFromContentDispostionHeader(res.headers.get('content-disposition'));
        const mimetype = res.headers.get('content-type');

        const newBlob = new Blob([blob], {type: mimetype || 'text/plain'});
        // workaround for missing msSaveOrOpenBlob in type spec of Navigator
        if (window.navigator && (window.navigator as any).msSaveOrOpenBlob) {
            (window.navigator as any).msSaveOrOpenBlob(newBlob);
        }
        else {
            const objUrl = window.URL.createObjectURL(newBlob);

            const link = document.createElement('a');
            link.href = objUrl;
            link.download = filename;
            link.click();

            // For Firefox it is necessary to delay revoking the ObjectURL.
            setTimeout(() => { window.URL.revokeObjectURL(objUrl); }, 250);
        }
    };
}

function validFetch(path:string, pdata:any, options:any, headers:any, responseHandler:Function): Promise<APIResult> {
    if(!controller) {
        controller = new AbortController();
    }
    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "application/json"} , headers);

    const dataStore = useDataStore();

    const data:any = {
        path: path,
        nonce: dataStore ? dataStore.nonce : ''
    };
    if (pdata && Object.keys(pdata).length > 0) {
        data.model = pdata;
    } 

    const fetchOptions = Object.assign({}, {headers: contentHeaders}, options, {
        credentials: "same-origin",
        redirect: "manual",
        method: 'POST',
        signal: controller.signal,
        body: JSON.stringify(data)
    });

    return (fetch(dataStore ? dataStore.baseUrl : '', fetchOptions)
        .then(responseHandler())
        .catch(err => {
            if(err.name === "AbortError") {
                console.log('disregarding aborted call');
            }
            else {
                throw err;
            }
        })) as Promise<APIResult>;
}

function fetchJson(path:string, data={}, options = {}, headers = {}): Promise<APIResult> {
    return validFetch(path, data, options, headers, validateResponse);
}

function fetchAttachment(path:string, data = {}, options = {}, headers = {}): Promise<APIResult> {
    headers = Object.assign({
        "Accept": "*",
    }, headers);
    return validFetch(path, data, options, headers, attachmentResponse);
}

export function getConfiguration(): Promise<APIResult> {
    return fetchJson('/configuration');
}

export function saveConfiguration(config:any): Promise<APIResult> {
    return fetchJson('/configuration/save', config);
}

export function getData(offset:number, pagesize:number, filter:any, sorter:string, sortDirection:string, cutoff:number): Promise<APIResult> {
    return fetchJson('/data', {offset: offset, pagesize: pagesize, cutoff: cutoff, filter: filter, sorter: sorter, sortDirection: sortDirection});
}
export function exportData(filter:any, sorter:string, sortDirection:string): Promise<APIResult> {
    return fetchAttachment('/data/export', {filter: filter, sorter: sorter, sortDirection: sortDirection});
}
export function saveAttribute(id: number, attribute:string, value:string): Promise<APIResult> {
    return fetchJson('/data/save', {id: id, attribute: attribute, value:value});
}
export function saveMember(member:Member): Promise<APIResult> {
    return fetchJson('/data/save', {member: member});
}
export function deleteMember(member:Member): Promise<APIResult> {
    return fetchJson('/data/delete', {id: member.id});
}