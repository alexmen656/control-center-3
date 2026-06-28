// Augment Vue's component instance type with the global properties
// registered in main.ts (app.config.globalProperties).
// The `import 'vue'` makes this file a module so the `declare module`
// block augments — rather than replaces — the real 'vue' module.
import 'vue'

declare module 'vue' {
  interface ComponentCustomProperties {
    $axios: typeof import('axios').default
    $qs: typeof import('qs')
    $toast: any
    emitter: any
  }
}
