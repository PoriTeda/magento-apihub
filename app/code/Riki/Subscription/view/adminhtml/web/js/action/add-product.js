
define([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/modal/alert",
    "prototype",
    "Magento_Catalog/catalog/product/composite/configure",
    'Magento_Ui/js/lib/view/utils/async'
], function(jQuery, $t, alert){
    
    window.SubscriptionProfileProductAdd = new Class.create();

    SubscriptionProfileProductAdd.prototype = {
        initialize: function (data) {
            if(!data) data = {};
            this.addUrl = data.add_url ? data.add_url : false;
            this.isAdditional = data.is_additional ? data.is_additional : 0;
            this.gridProducts = $H({});
            this.overlayData = $H({});
        },

        setAddUrl: function (url) {
            this.addUrl = url;
        },

        productGridRowInit: function (grid, row) {
            var checkbox = $(row).select('.checkbox')[0];
            var inputs = $(row).select('.input-text');
            if (checkbox && inputs.length > 0) {
                checkbox.inputElements = inputs;
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    input.checkboxElement = checkbox;

                    var product = this.gridProducts.get(checkbox.value);
                    if (product) {
                        var defaultValue = product[input.name];
                        if (defaultValue) {
                            input.value = defaultValue;
                        }
                    }

                    input.disabled = !checkbox.checked || input.hasClassName('input-inactive');

                    Event.observe(input, 'keyup', this.productGridRowInputChange.bind(this));
                    Event.observe(input, 'change', this.productGridRowInputChange.bind(this));
                }
            }
        },

        productGridRowInputChange: function (event) {
            var element = Event.element(event);
            if (element && element.checkboxElement && element.checkboxElement.checked) {
                if (element.name!='giftmessage' || element.checked) {
                    this.gridProducts.get(element.checkboxElement.value)[element.name] = element.value;
                }
            }
        },

        productGridRowClick: function (grid, event) {
            var trElement = Event.findElement(event, 'tr');
            var isInputQty = Event.element(event).tagName == 'INPUT' && Event.element(event).type == 'text';
            var isInputCheckbox = Event.element(event).tagName == 'INPUT' && Event.element(event).type == 'checkbox';
            var checkbox = Element.select(trElement, 'input[type="checkbox"]');
            var inputQty = Element.select(trElement, 'input[type="text"]');
            if (trElement && !isInputQty) {
                if(checkbox[0] && !checkbox[0].disabled){
                    var checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                    if(isInputCheckbox)
                    {
                        jQuery(trElement).trigger('click');
                    }else{
                        if(!isInputQty )
                        {
                            if (!isInputQty) {
                                if(checked)
                                {
                                    inputQty[0].disabled=false;
                                }else{
                                    inputQty[0].disabled=true;
                                    //inputQty[0].value = 1;
                                }
                            }
                        }
                        grid.setCheckboxChecked(checkbox[0], checked);
                    }
                }
            }
        },

        productGridCheckboxCheck: function (grid, element, checked) {
            if(checked){
                if(element.inputElements) {
                    //this.gridData.set(element.value, {});
                    this.gridProducts.set(element.value, {});
                    var product = this.gridProducts.get(element.value);
                    for(var i = 0; i < element.inputElements.length; i++) {
                        element.inputElements[i].disabled = false;
                        //this.gridData.get(element.value)[element.inputElements[i].name] = element.inputElements[i].value;

                        var input = element.inputElements[i];
                        if (input.checked || input.name != 'giftmessage') {
                            product[input.name] = input.value;
                        } else if (product[input.name]) {
                            delete(product[input.name]);
                        }
                    }
                }
            }
            else{
                if(element.inputElements){
                    for(var i = 0; i < element.inputElements.length; i++) {
                        element.inputElements[i].disabled = true;
                    }
                }
               // this.gridData.unset(element.value);
                this.gridProducts.unset(element.value);
            }

            grid.reloadParams = {'products[]': this.gridProducts.keys()};
        },

        productGridAddSelected : function(){
            // prepare additional fields and filtered items of products
            var fieldsPrepare = {};
            var selectedIds = [];
            fieldsPrepare['is_additional'] = this.isAdditional;
            var products = this.gridProducts.toObject();
            for (var productId in products) {
                selectedIds.push(productId);
                var paramKey = 'items[' + productId + ']';
                for (var productParamKey in products[productId]) {
                    fieldsPrepare[paramKey + '[' + productParamKey + ']'] = products[productId][productParamKey];
                }
            }

            if (selectedIds.length == 0) {
                alert({
                    content: $t("Please select a product")
                });
                return;
            }

            this.resetGrid();

            this.gridProducts = $H({});

            new Ajax.Request(this.addUrl, {
                parameters:fieldsPrepare,
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();
                    if(response.success == true) {
                        jQuery('<input />').attr({
                            type: 'hidden',
                            id: 'is_added',
                            name: 'is_added',
                            value: selectedIds.join()
                        }).appendTo(jQuery('#form-submit-profile'));
                        jQuery('#form-submit-profile').find('input[name=save_profile]').val('add_product');
                        jQuery('#form-submit-profile').find('input[name=profile_type]').attr('disabled','disabled');
                        jQuery('#form-submit-profile').submit();
                        jQuery('#form-submit-profile').find('input[name=profile_type]').removeAttr('disabled');
                        jQuery('#is_added').remove();
                    }else{
                        if(typeof jQuery('#messages') != 'undefined') {
                            jQuery('#messages').remove();
                        }
                        jQuery('html, body').animate({scrollTop: '0px'}, 0);
                        var mess = '<div id="messages"><div class="messages"><div class="message message-error error"><div data-ui-id="messages-message-error">'+response.message+'</div></div></div></div>';
                        jQuery('.page-columns').before(mess);
                    }
                }.bind(this)
            });
        },

        resetGrid: function () {
            if (this.isAdditional) {
                var checkAllCheckbox = jQuery('#add-additional-products .data-grid-actions-cell .admin__control-checkbox');
            } else {
                var checkAllCheckbox = jQuery('#add-products .data-grid-actions-cell .admin__control-checkbox');
            }

            if(checkAllCheckbox.is(':checked')) {
                checkAllCheckbox.trigger('click');
            }else {
                checkAllCheckbox.trigger('click');
                checkAllCheckbox.trigger('click');
            }

            jQuery("#add-products .admin__control-text.qty").val(1);
        }
    }

});