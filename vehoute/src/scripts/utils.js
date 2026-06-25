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