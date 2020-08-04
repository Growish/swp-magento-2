define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'scuolapayment',
                component: 'Brightmansukhsoft_Scuolapayment/js/view/payment/method-renderer/scuolapayment'
            }
        );
        return Component.extend({});
    }
);