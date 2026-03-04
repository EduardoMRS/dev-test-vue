import { toast } from 'vue-sonner'

export default {
  install(app) {
    const notify = {
      success(msg, options = {}) {
        toast.success(msg, options)
      },
      error(msg, options = {}) {
        toast.error(msg, options)
      },
      warning(msg, options = {}) {
        toast.warning(msg, options)
      },
      info(msg, options = {}) {
        toast(msg, options)
      },
      custom(renderer) {
        toast.custom(renderer)
      }
    }

    // Disponível globalmente nos componentes Vue
    app.config.globalProperties.$notify = notify

    // Disponível fora do Vue (ex: stores, helpers)
    app.provide('notify', notify)
    window.notify = notify
  }
}
