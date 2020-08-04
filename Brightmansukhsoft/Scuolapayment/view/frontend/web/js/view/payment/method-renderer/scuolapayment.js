define(
    [
    'Magento_Checkout/js/view/payment/default',  
    'Magento_Checkout/js/action/redirect-on-success',	
    'mage/url',
    'jquery'
    ],
    function (Component,redirectOnSuccessAction,url,$) {
        'use strict';
 
        return Component.extend({
            defaults: {
                template: 'Brightmansukhsoft_Scuolapayment/payment/scuolapayment'
            } ,
			afterPlaceOrder: function () {
            redirectOnSuccessAction.redirectUrl =url.build('scuolapay/redirect/');
                    //redirectOnSuccessAction.redirectUrl =url.build('firstdata/redirect/');
            this.redirectAfterPlaceOrder = true;
            },
        });
    }
);