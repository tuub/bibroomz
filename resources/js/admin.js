import { createApp, h } from "vue";
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";

import App from "./Pages/Admin/VuesticDashboard.vue";
import "vuestic-ui/css";

const app = createApp(App);

app.use(createVuestic())
app.mount('#app')
