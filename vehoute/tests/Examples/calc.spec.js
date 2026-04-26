// tests/calc.spec.js
import { describe, it, expect } from 'vitest'
import { calc } from './calc'

describe('calc', () => {
  it('soma corretamente', () => {
    expect(calc.sum(2, 3)).toBe(5)
  })

  it('multiplica corretamente', () => {
    expect(calc.multiply(2, 3)).toBe(6)
  })
})