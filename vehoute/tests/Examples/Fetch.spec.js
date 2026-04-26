import { it, expect } from 'vitest'
import { fetchUser } from './async_user_fetch'

it('retorna usuário', async () => {
  const user = await fetchUser()

  expect(user.name).toBe('João')
})