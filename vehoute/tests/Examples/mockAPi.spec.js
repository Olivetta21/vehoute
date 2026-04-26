import { vi, it, expect } from 'vitest'
import { load } from './mockApi'
import * as api from './api_forMock'

vi.spyOn(api, 'getData').mockResolvedValue([1, 2, 3])

it('usa dados da API', async () => {
  const result = await load()

  expect(result).toBe(3)
})