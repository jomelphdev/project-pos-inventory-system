# retail-right-ui-v3

Auto-deploy to production (https://retailright.app):

`master`: [![Netlify Status](https://api.netlify.com/api/v1/badges/17d70f2d-37c1-4a0c-bcc4-11930d92511e/deploy-status)](https://app.netlify.com/sites/retail-right-ui-v3-prod/deploys)

## Project setup

Since we are using dependabot to manage dependency updates, using `npm ci` ensures a clean-slate installation and avoids modifying `package-lock.json`.

```
npm ci
```

### Compiles and hot-reloads for development

```
npm run serve
```

### Compiles and minifies for production

```
npm run build
```

### Lints and fixes files

```
npm run lint
```

### Icons

https://vue-hero-icons.netlify.app

```
# <script>
import { ViewGridAddIcon } from "@vue-hero-icons/outline";

# <template>
<ViewGridAddIcon size="20" class="mr-1 self-center" />
```

### Customize configuration

See [Configuration Reference](https://cli.vuejs.org/config/).

### Testing (Cypress)

```
npx cypress open
```
