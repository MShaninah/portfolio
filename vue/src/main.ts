import { createApp } from "vue"
import App from "./App.vue"
import "./style.css"

import { i18n } from "./i18n"

const app = createApp(App)

// Global directive: v-reveal — fades elements in when they enter the viewport
app.directive("reveal", {
  mounted(el: HTMLElement) {
    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (prefersReduced) return

    // Ensure initial hidden state
    el.classList.add('reveal-init')

    const observer = new IntersectionObserver((entries, obs) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          el.classList.add('reveal-show')
          obs.unobserve(entry.target)
        }
      }
    }, { root: null, threshold: 0.12 })

    observer.observe(el)
  }
})

app.use(i18n)
app.mount("#app")