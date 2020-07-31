/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'Magento_Checkout/js/action/select-payment-method':
                'Bluecom_PaymentFee/js/action/payment/select-payment-method',
            'Magento_Checkout/js/view/payment/default':
                'Bluecom_PaymentFee/js/view/payment/default',
            'Magento_OfflinePayments/template/payment/cashondelivery.html':
                'Bluecom_PaymentFee/template/payment/cashondelivery.html',
            'Riki_CvsPayment/template/payment/cvspayment.html':
                'Bluecom_PaymentFee/template/payment/cvspayment.html',
            'Bluecom_Paygent/template/payment/paygent-form.html':
                'Bluecom_PaymentFee/template/payment/paygent-form.html',
            'Magento_OfflinePayments/template/payment/checkmo.html':
                'Bluecom_PaymentFee/template/payment/checkmo.html',
            'Bluecom_Bisp/template/payment/invoicedbasedpayment.html':
                'Bluecom_PaymentFee/template/payment/invoicedbasedpayment.html',
            'Magento_Payment/template/payment/free.html':
                'Bluecom_PaymentFee/template/payment/free.html',
        }
    },
    "shim": {
        "ajaxzip3": ["jquery"]
    }
};
