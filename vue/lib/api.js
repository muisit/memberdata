var controller = null;
import { useDataStore } from '../src/stores/data';

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
                if (json.data && json.data.error) {
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

function validFetch(path, pdata, options, headers = {}) {
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
        .then(validateResponse())
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
    return validFetch(path, data, options, headers);
}
