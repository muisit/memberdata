var controller = null;
import { useDataStore } from '../stores/data';

export function abort_all_calls() {
    if(controller) {
        controller.abort();
        controller = null;
    }
}

function validateResponse() {
    return res => {
        return res.json().then(json => {
            if (!json || !json.success) {
                if (json && (json.data && json.data.error)) {
                    throw new Error('Validation', {cause: json.data});
                }
                else {
                    throw new Error("Network error, please try again");
                }
            }
            return json;
        })
    };
}

function getFileNameFromContentDispostionHeader(header) {
    var contentDispostion = header.split(';');
    const fileNameToken = `filename=`;

    var fileName = 'downloaded.dat';
    contentDispostion.forEach((thisValue) => {
        if (thisValue.trim().indexOf(fileNameToken) === 0) {
            fileName = decodeURIComponent(thisValue.replace(fileNameToken, ''));
        }
    });
    return fileName;
};

function attachmentResponse() {
    return async res => {
        const blob = await res.blob();
        var filename = getFileNameFromContentDispostionHeader(res.headers.get('content-disposition'));
        var mimetype = res.headers.get('content-type');

        var newBlob = new Blob([blob], {type: mimetype});
        if (window.navigator && window.navigator.msSaveOrOpenBlob) {
            window.navigator.msSaveOrOpenBlob(newBlob);
        }
        else {
            const objUrl = window.URL.createObjectURL(newBlob);

            var link = document.createElement('a');
            link.href = objUrl;
            link.download = filename;
            link.click();

            // For Firefox it is necessary to delay revoking the ObjectURL.
            setTimeout(() => { window.URL.revokeObjectURL(objUrl); }, 250);
        }
    };
}

function validFetch(path, pdata, options, headers, responseHandler) {
    if(!controller) {
        controller = new AbortController();
    }
    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "application/json"} , headers);

    const dataStore = useDataStore();

    const data = {
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

    return fetch(dataStore ? dataStore.baseUrl : '', fetchOptions)
        .then(responseHandler())
        .catch(err => {
            if(err.name === "AbortError") {
                console.log('disregarding aborted call');
            }
            else {
                throw err;
            }
        });
}

function fetchJson(path, data={}, options = {}, headers = {}) {
    return validFetch(path, data, options, headers, validateResponse);
}

function fetchAttachment(path, fname, data = {}, options = {}, headers = {}) {
    headers = Object.assign({
        "Accept": "*",
    }, headers);
    return validFetch(path, data, options, headers, attachmentResponse);
}

export function getConfiguration() {
    return fetchJson('/configuration');
}

export function saveConfiguration(config) {
    return fetchJson('/configuration/save', config);
}

export function getData(offset, pagesize, filter, sorter, sortDirection, cutoff) {
    return fetchJson('/data', {offset: offset, pagesize: pagesize, cutoff: cutoff, filter: filter, sorter: sorter, sortDirection: sortDirection});
}
export function exportData(fname, filter, sorter, sortDirection) {
    return fetchAttachment('/data/export', fname, {filter: filter, sorter: sorter, sortDirection: sortDirection});
}
export function saveAttribute(id, attribute, value) {
    return fetchJson('/data/save', {id: id, attribute: attribute, value:value});
}
export function saveMember(member) {
    return fetchJson('/data/save', {member: member});
}
export function deleteMember(member) {
    return fetchJson('/data/delete', {id: member.id});
}