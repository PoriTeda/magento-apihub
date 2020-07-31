define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function ($, priceUtils, $t) {
    'use strict';

    // check for init
    $('.unit-case').each(function(){
        var productid = $(this).attr('productid');
        var unitqty = $(this).attr('unitqty');
        if($(this).val() == 'cs'){
            $('#qty_case_'+productid).closest('div.qty-case').show();
            $('#qty_'+productid).closest('div.qty-piece').hide();
            $('#qty_'+productid).val($('#qty_case_'+productid).val() * unitqty);
            $('#qty_'+productid).change();
        }
        else
        if($(this).val() == 'ea'){
            $('#qty_case_'+productid).closest('div.qty-case').hide();
            $('#qty_'+productid).closest('div.qty-piece').show();
        }
    });

    // check for change
    $('.unit-case').on('change',function(){
        var productid = $(this).attr('productid');
        var unitqty = $(this).attr('unitqty');
        if($(this).val() == 'cs'){
            $('#qty_case_'+productid).closest('div.qty-case').show();
            $('#qty_'+productid).closest('div.qty-piece').hide();
            $('#qty_'+productid).val($('#qty_case_'+productid).val() * unitqty);
        }
        else
        if($(this).val() == 'ea'){
            $('#qty_case_'+productid).closest('div.qty-case').hide();
            $('#qty_'+productid).closest('div.qty-piece').show();
        }
        $('#qty_'+productid).change();
    });


    $('.qty-case').on('change',function(){
        if($(this).val() > 0) {
            $(this).addClass('selected');
        }else {
            $(this).removeClass('selected');
        }
        var productid = $(this).attr('productid');
        if($('#case_display_'+productid).val() == 'cs'){
            var unitqty = $('#case_display_'+productid).attr('unitqty');
            $('#qty_'+productid).val($('#qty_case_'+productid).val() * unitqty);
        }
        else{
            $('#qty_'+productid).val(0);
        }
        $('#qty_'+productid).change();
    });


    $('select.qty').on('change', function() {
        var _this = $(this),
            qty = _this.val(),
            productRow = _this.parents('tr'),
            productId = productRow.attr('data-id'),
            finalPrice = productRow.find('.final-price').attr('data-val'),
            tierPriceObj = window['tierPriceObj_' + productId],
            totalAmount = 0,
            priceFormat = {
                decimalSymbol: ".",
                groupLength: 3,
                groupSymbol: ",",
                integerRequired: 1,
                pattern: "%s" + $t('Yen'),
                precision: "0",
                requiredPrecision: "0"
            };
        if(qty > 0) {
            _this.addClass('selected');
        }else {
            _this.removeClass('selected');
        }
        if(tierPriceObj.hasTierPrice) {
            var key = -1;
            for(var i=0; i<tierPriceObj.tierPriceItem.length; i++) {
                if(qty >= tierPriceObj.tierPriceItem[i].qty)
                    key = i;
            }
            if(key > -1 && finalPrice > tierPriceObj.tierPriceItem[key].price) {
                finalPrice = tierPriceObj.tierPriceItem[key].price;
            }
        }

        productRow.find('.col.price.subtotal').text(priceUtils.formatPrice(finalPrice*qty,priceFormat));

        $('table.table-multiple-products').each(function() {
            $(this).find('tbody tr').each(function() {
                var finalPriceItem = $(this).find('.final-price').attr('data-val');
                if(typeof finalPriceItem !='undefined') {
                    var qtyItem =  $(this).find('select.qty').val(),
                        productIdItem = $(this).attr('data-id'),
                        tierPriceObjItem = window['tierPriceObj_' + productIdItem];
                    if(typeof tierPriceObj != 'undefined' && tierPriceObjItem.hasTierPrice) {
                        var key = -1;
                        for(var i=0; i<tierPriceObjItem.tierPriceItem.length; i++) {
                            if(qtyItem >= tierPriceObjItem.tierPriceItem[i].qty)
                                key = i;
                        }
                        if(key > -1 && finalPriceItem > tierPriceObjItem.tierPriceItem[key].price) {
                            finalPriceItem = tierPriceObjItem.tierPriceItem[key].price;
                        }
                    }

                    totalAmount = totalAmount + (finalPriceItem*qtyItem);
                }
            });
        });
        $('td#total-amount').text(priceUtils.formatPrice(totalAmount,priceFormat));
    });

    $('select.qty option[value="0"]').prop("selected",true);
    $('select.qty-case option[value="0"]').prop("selected",true);
});