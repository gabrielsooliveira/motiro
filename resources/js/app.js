import './bootstrap';
import 'bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy';

import LayoutDashboard from '@/js/Layouts/Dashboard.vue';
import LayoutAuth from '@/js/Layouts/Auth.vue';
import LayoutSite from '@/js/Layouts/Site.vue';

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { fas } from '@fortawesome/free-solid-svg-icons';
import { fab } from '@fortawesome/free-brands-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';

library.add(fas, fab, far);

createInertiaApp({
    title: title => `Motirõ | ${title}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue')
        const resolvePageComponent = (name, pages) => {
            const importPage = pages[`./Pages/${name}.vue`];
            if (!importPage) {
                throw new Error(`Page not found: ./pages/${name}.vue`);
            }
            return importPage();
        };
        const page = resolvePageComponent(name, pages);
        page.then(module => {
            if (name === 'Auth/SelectContext') {
                module.default.layout = null;
            } else if (name.startsWith('Participant/Auth/') || name.startsWith('Organization/Auth/')) {
                module.default.layout = LayoutAuth;
            } else if (name.startsWith('Participant/Dashboard/') || name.startsWith('Organization/Dashboard/')) {
                module.default.layout = LayoutDashboard;
            } else {
                module.default.layout = LayoutSite;
            }
        });
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .component('font-awesome-icon', FontAwesomeIcon)
            .use(ZiggyVue)
            .use(plugin)
            .mount(el)
    },
    progress: {
        delay: 250,
        color: '#29d',
    }
})
