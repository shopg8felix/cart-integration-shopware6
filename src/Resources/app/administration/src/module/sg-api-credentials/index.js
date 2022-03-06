import './page/sg-api-credentials-list';
import './page/sg-api-credentials-create';
import './page/sg-api-credentials-detail';
import './component/sg-channel-language-single-select';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const {Module} = Shopware;

Module.register('sg-api-credentials', {
    type: 'plugin',
    name: 'sg-api-credentials',
    title: 'sg-api-credentials.general.mainMenuItemGeneral',
    description: 'sg-api-credentials.general.description',
    color: '#9AA8B5',
    icon: 'default-shopping-paper-bag',
    favicon: 'shopgate_logo.png',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'sg-api-credentials-list',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        detail: {
            component: 'sg-api-credentials-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sg.api.credentials.index'
            }
        },
        create: {
            component: 'sg-api-credentials-create',
            path: 'create',
            meta: {
                parentPath: 'sg.api.credentials.index'
            }
        }
    },

    settingsItem: [
        {
            name: 'sg-api-credentials',
            to: 'sg.api.credentials.index',
            label: 'sg-api-credentials.general.mainMenuItemGeneral',
            group: 'plugins',
            icon: 'default-shopping-paper-bag'
        }
    ]
});
