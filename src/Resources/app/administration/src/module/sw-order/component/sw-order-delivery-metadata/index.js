import template from './sw-order-delivery-metadata.html.twig';
import loader from './../../../../shopgateOrderLoader'

Shopware.Component.override('sw-order-delivery-metadata', {
    template,
    ...loader,
    props: {
        order: {
            type: Object,
            required: true,
            default() {
                return {};
            }
        }
    }
})