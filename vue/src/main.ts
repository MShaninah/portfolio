import { createApp } from "vue"
import App from "./App.vue"
import "./style.css"

import { i18n } from "./i18n"

const app = createApp(App)

app.directive("reveal", {
  mounted(el: HTMLElement) {
    const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    // No IntersectionObserver (or motion disabled): show the content outright.
    // Content must never depend on an animation in order to be readable.
    if (prefersReduced || typeof IntersectionObserver === 'undefined') return

    el.classList.add('reveal-init')

    const show = () => el.classList.add('reveal-show')

    const observer = new IntersectionObserver((entries, obs) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          show()
          obs.unobserve(entry.target)
        }
      }
    }, {
      root: null,
      // threshold MUST stay at 0. It is the fraction of the TARGET that is
      // visible, so a section taller than the viewport can never exceed
      // viewportHeight / sectionHeight. The old 0.12 was unreachable for the
      // Skills and Projects sections, which stayed at opacity: 0 forever.
      threshold: 0,
      // Start the animation slightly before the section edge reaches the fold.
      rootMargin: '0px 0px -5% 0px',
    })

    observer.observe(el)

    // Last-resort safety net: whatever happens with layout, scrolling or the
    // observer, nothing stays invisible. A blank portfolio is worse than an
    // un-animated one.
    window.setTimeout(show, 2500)
  }
})

app.use(i18n)
app.mount("#app")
