import { describe, it, expect, beforeEach } from 'vitest'
import { cart } from './cart'

describe('cart', () => {
  beforeEach(() => {
    cart.items = [] // resetar estado
  })

  it('adiciona item', () => {
    cart.add({ name: 'Produto', price: 10 })

    expect(cart.items.length).toBe(1)
  })

  it('calcula total', () => {
    cart.add({ price: 10 })
    cart.add({ price: 5 })

    expect(cart.total()).toBe(15)
  })
})