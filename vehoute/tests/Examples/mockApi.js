import { getData } from './api_forMock'

export async function load() {
  const data = await getData()
  return data.length
}