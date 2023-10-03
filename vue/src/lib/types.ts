export interface FieldDefinition {
    field: string;
    value: string;
}

export interface Attribute {
    name: string;
    type: string;
    rules?: string;
    options?: any;
    optdefault?: any;
    filter: string;
}

export interface FilterSpec {
    search: string|null;
    values: Array<string|null>;
}

export interface Member {
    id: number;
    [key:string]: string|number;
}

export interface AttributeByKey {
    [key:string]: Attribute;
}

export interface FilterOptionsByAttribute {
    [key:string]: Array<string|null>;
}

export interface FilterSpecByKey {
    [key:string]: FilterSpec;
}

export interface APIResult {
    success?: boolean;
    data?: any;
}