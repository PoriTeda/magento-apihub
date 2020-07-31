define([
    "jquery",
    'Magento_Customer/js/customer-data',
    "jquery/ui",
    'Magento_Checkout/js/sidebar'
],function ($, customerData) {
    $.widget('mage.sidebar', $.mage.sidebar, {
        _initContent: function() {
            this._super();

            this._off(this.element, 'click ' + this.options.button.checkout);

            var events = {};
            
            events['click ' + this.options.button.checkout] = $.proxy(function() {
                var cart = customerData.get('cart'),
                    customer = customerData.get('customer');

                if (!customer().firstname && !cart().isGuestCheckoutAllowed) {
                    location.href = window.checkout.ssoLoginUrl+this.options.url.checkout;
                    return;
                }
                location.href = this.options.url.checkout;
            }, this);

            this._on(this.element, events);
        }

    });
    
    return $.mage.sidebar;
});
