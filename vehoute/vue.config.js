const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  devServer: {
    host: '0.0.0.0',
    allowedHosts: 'all',
    proxy: {
      '/api': {
        target: 'http://vehoute.serv',
        changeOrigin: true,
        pathRewrite: {
          '^/api': ''
        }
      },
      '/ws/': {
        target: 'ws://vehoute.serv:12344',
        changeOrigin: true,
        pathRewrite: {
          '^/ws/': ''
        }
      }
    }
  }
})
