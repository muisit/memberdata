export function pad(number:number) {
    if (number < 10) {
        return '0' + number;
    }
    return number;
}

export function is_valid(id:any) {
    if (!id) return false;
    if (id.id) return is_valid(id.id);
    if (parseInt(id) > 0) return true;
    return false;
}

export function convertDateToDayJSFormat(format:string)
{
    let retval = '';
    for (let i = 0; i < format.length; i++) {
        const c = format[i];
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

export function random_between(min:number, max:number)
{
    return Math.floor((Math.random() * (max - min)) + 0.5) + min;
}

export function random_from_list(chars:string)
{
    return chars[random_between(0, chars.length)];
}

export function random_token()
{
    const randomChars="abcdefghijklmnopqrstuvwxyz0123456789";
    let retval = '';
    for (let i = 0; i< 16; i++) {
        retval += random_from_list(randomChars);
    }
    return retval;
}