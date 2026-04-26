
export const cart = {
  items: [],

  add(item) {
    this.items.push(item)
  },

  total() {
    return this.items.reduce((sum, i) => sum + i.price, 0)
  }
}