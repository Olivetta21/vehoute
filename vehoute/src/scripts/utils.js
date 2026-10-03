export function getBaseOfDestApi() {
    return process.env.VUE_APP_BACKEND_ADDRESS;
}

export function getDateWithOffset(dayOffset = 0) {
  const date = new Date();
  date.setDate(date.getDate() + dayOffset);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export function compararVariaveis(a, b) {
    if (a === b) return 0;
    if ((a === null || a === undefined || String(a) === '') && (b === null || b === undefined || String(b) === '')) return 0;
    if (a === null || a === undefined) return -1;
    if (b === null || b === undefined) return 1;
    if (a < b) return -1;
    if (a > b) return 1;
    return 0;
}

export function parseDateToTimestamp(value) {
    let date;

    if (value instanceof Date) {
        date = value;
    } else if (typeof value === 'string') {
        const match = value.match(
            /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?$/
        );
        if (!match) return null;
        
        const [
            ,
            y,
            m,
            d,
            h,
            min,
            sec = '0',
            ms = '0'
        ] = match;
        
        date = new Date(
            Number(y),
            Number(m) - 1,
            Number(d),
            Number(h),
            Number(min),
            Number(sec),
            Number(ms.substring(0, 3).padEnd(3, '0'))
        );
    }

    if (isNaN(date.getTime())) return null;

    return Math.floor(date.getTime() / 1000) * 1000;
}

export function parseTimeStampToStrObj(timestamp) {
    let date = new Date(timestamp);
    let result = {
        y: String(date.getFullYear()).padStart(4, '0'),
        m: String(date.getMonth() + 1).padStart(2, '0'),
        d: String(date.getDate()).padStart(2, '0'),
        h: String(date.getHours()).padStart(2, '0'),
        min: String(date.getMinutes()).padStart(2, '0'),
        sec: String(date.getSeconds()).padStart(2, '0'),
        ms: String(date.getMilliseconds()).padStart(3, '0')
    };
    return result;
}

export function compararDatas(data1, data2) {
    if (!data1 && !data2) return 0;
    if (!data1) return -1;
    if (!data2) return 1;

    let d1 = parseDateToTimestamp(data1);
    let d2 = parseDateToTimestamp(data2);

    return compararVariaveis(d1, d2);
}