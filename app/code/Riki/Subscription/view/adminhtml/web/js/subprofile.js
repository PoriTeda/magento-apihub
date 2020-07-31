define([
    "jquery",
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    "mage/translate",
    "prototype",
    "Magento_Catalog/catalog/product/composite/configure",
    'Magento_Ui/js/lib/view/utils/async',
    'Bluecom_PaymentFee/js/order/create/scripts'
], function(jQuery, confirm, alert,$t){

    AdminOrder.prototype.productCourseGridAddSelected = function () {
        if (this.productGridShowButton) Element.show(this.productGridShowButton);
        var area = ['search', 'items', 'machines', 'shipping_method', 'totals', 'giftmessage', 'billing_method','delivery_info', 'reward_redeem'];
        // prepare additional fields and filtered items of products
        var fieldsPrepare = {};
        var itemsFilter = [];
        var products = this.gridProducts.toObject();
        var frequency = jQuery('#frequency_course').val();
        var machine = jQuery('#machine_course').val();
        //var machineAvailable = jQuery('#machine_not_available').val();
        var container = arguments[0].up('div', 2);
        var availableMachine = container.down('input[name=machine_not_available]');

        if (frequency == '' || Object.keys(products).length == 0){
            alert({content:$t('Please select a frequency and products')});
            return false;
        }
        if(machine == ''){
            alert({content:$t('Please select a machine.')});
            return false;
        }
        if(availableMachine && availableMachine.value == 1){
            alert({content:$t('I\'m sorry, the machine is out of stock at the moment. Please contact us for confirmation of re-arrival.')});
            // remove item checked of invalid sub course
            for (var productId in products) {
                this.gridProducts.unset(productId);
            }
            return false;
        }
        for (var productId in products) {
            itemsFilter.push(productId);
            var paramKey = 'item[' + productId + ']';
            var paramKeyOriginal = 'item['+productId+']';
            for (var productParamKey in products[productId]) {
                if('case_display' == productParamKey || 'unit_qty' == productParamKey){
                    var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                    fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                }
                else
                if('qty' == productParamKey){
                    var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                    if('cs' == products[productId]['case_display']){
                        fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey] * products[productId]['unit_qty'];
                    }
                    else{
                        fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                    }
                }
                else{
                    if('is_additional' == productParamKey){
                        var paramKeyAdditional = paramKeyOriginal+'['+productParamKey+']';
                        fieldsPrepare[paramKeyAdditional] = 1;
                    }
                    else {
                        paramKey += '[' + productParamKey + ']';
                        fieldsPrepare[paramKey] = products[productId][productParamKey];
                    }
                }

            }
        }
        var course_id = jQuery('input[name=course_id]').val();
        var frequency_id =jQuery('#frequency_course').val();
        var machine_id = jQuery('#machine_course').val();
        var hanpukai_qty = jQuery('#hanpukai_quantity').val();
        fieldsPrepare['course_id'] = course_id;
        fieldsPrepare['frequency_id'] = frequency_id;
        if (machine_id) {
            fieldsPrepare['machine_id'] = machine_id;
            fieldsPrepare['item[' + machine_id + '][qty]'] = 1;
            fieldsPrepare['item[' + machine_id + '][case_display]'] = 'ea';
            fieldsPrepare['item[' + machine_id + '][unit_qty]'] = 1;
            fieldsPrepare['item[' + machine_id + '][is_machine]'] = 1;
            itemsFilter.push(machine_id);
        }
        if (hanpukai_qty) {
            fieldsPrepare['hanpukai_qty'] = hanpukai_qty;
        }
        this.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
        productConfigure.clean('quote_items');
        this.hideArea('product_course');
        this.gridProducts = $H({});
    }
    AdminOrder.prototype.machinesGridAddSelected = function () {
        if (this.productGridShowButton) Element.show(this.productGridShowButton);
        var area = ['search', 'items', 'machines', 'shipping_method', 'totals', 'giftmessage', 'billing_method','delivery_info', 'reward_redeem'];
        // prepare additional fields and filtered items of products
        var fieldsPrepare = {};
        var itemsFilter = [];
        var products = this.gridProducts.toObject();
        var container = arguments[0].up('div', 2);

        for (var productId in products) {
            itemsFilter.push(productId);
            var paramKey = 'item[' + productId + ']';
            var paramKeyOriginal = 'item['+productId+']';
            for (var productParamKey in products[productId]) {
                fieldsPrepare['item[' + productId + '][is_machine]'] = 1;
                fieldsPrepare['item[' + productId + '][is_machine_type]'] = 1;
                if('case_display' === productParamKey || 'unit_qty' === productParamKey){
                    var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                    fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                }
                else
                if('qty' === productParamKey){
                    var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                    if('cs' == products[productId]['case_display']){
                        fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey] * products[productId]['unit_qty'];
                    }
                    else{
                        fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                    }
                }
                else{
                    if('is_additional' == productParamKey){
                        var paramKeyAdditional = paramKeyOriginal+'['+productParamKey+']';
                        fieldsPrepare[paramKeyAdditional] = 1;
                    }
                    else {
                        paramKey += '[' + productParamKey + ']';
                        fieldsPrepare[paramKey] = products[productId][productParamKey];
                    }
                }

            }
        }
        var course_id = jQuery('input[name=course_id]').val();
        var frequency_id =jQuery('#frequency_course').val();
        fieldsPrepare['course_id'] = course_id;
        if (typeof frequency_id != 'undefined') {
            fieldsPrepare['frequency_id'] = frequency_id;
        }
        this.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
        productConfigure.clean('quote_items');
        this.hideArea('machines');
        this.gridProducts = $H({});
    }
    AdminOrder.prototype.backCourse = function () {
        this.showArea('course');
        this.hideArea('product_course');
    }
    AdminOrder.prototype.loadProductCourse = function (grid, event ) {
        var trElement = Event.findElement(event, 'tr');
        var url = jQuery(trElement).attr('title');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {form_key: window.FORM_KEY},
            type: 'POST',
            showLoader: true
        }).done(function (data) {
            if(data.success == true){
                var orderProductCourse = jQuery('#order-product_course');
                if (orderProductCourse.length) {
                    orderProductCourse.remove();
                }
                jQuery('input[name=course_id]').val(data.course_id);
                jQuery('#order-course').after(data.message);
                jQuery('#order-course').hide();
                if(!_.isUndefined(data.hanpukai) && data.hanpukai) {
                    var interval = setInterval(function () {
                        jQuery('#sales_order_create_product_course_grid_table tr').trigger('click');
                        clearInterval(interval);
                    }, 500);
                }
            }
            else{
                alert({content:data.message});
            }
        });
    }
    AdminOrder.prototype.addSpot = function (grid,event) {
        var trElement = Event.findElement(event, 'tr');
        console.log(trElement);
    }

});
