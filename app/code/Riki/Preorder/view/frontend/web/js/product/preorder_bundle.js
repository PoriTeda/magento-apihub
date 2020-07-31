define([
    "jquery",
    "jquery/ui",
    'Riki_Preorder/js/product/preorder'

], function($) {
    $.widget('mage.rikiPreorderBundle', $.mage.rikiPreorder, {
        options: {
            map: {},
            optionsData: {},
            checkedElements: {}
        },
        _create: function(){
            this._saveOriginal();
            var self = this;
            var option;
            for(var optionId in this.options.optionsData) {
                option = this.options.optionsData[optionId];
                if(option.isRequired && option.isPreorder && option.isSingle) {
                    self._enableOrDisablePreorder({
                        mapId: optionId + "-" +option.selectionId,
                        optionId: optionId,
                        selectionId: option.selectionId
                    }, $("#bundle-option-"+optionId+"-qty-input").parent());
                }
            }
            $('.bundle-options-wrapper .radio, .bundle-options-wrapper .checkbox, .bundle-options-wrapper .bundle-option-select, .bundle-options-wrapper .multiselect')
                .change(function(event){
                    var element = event.currentTarget;
                    var elementInfo = self._getElementInfo(element);

                    var $place = $($(element).parents('.options-list')[0]);
                    if($place.length == 0) {
                        $place = $($(element).parents('.field.option')[0]);
                    }

                    if($(element).prop('type').toLowerCase() == "checkbox") {
                        var checkboxInfo;
                        $.each($place.find('.checkbox'), function(key, checkbox){
                            checkboxInfo = self._getElementInfo(this);
                            if($(this).attr('checked') == 'checked' && self.options.map[checkboxInfo.mapId]) {
                                return false;
                            }
                        });
                        self._enableOrDisablePreorder(checkboxInfo, $place);
                        return;
                    }

                    self._enableOrDisablePreorder(elementInfo, $place);
                    return;
            });
        },
        _changeLabels: function() {
               $.mage.catalogAddToCart.prototype.options.addToCartButtonTextDefault = this.options.addToCartLabel;
               this.options.addToCartButton.html(this.options.addToCartLabel);
        },
        _getElementInfo: function(element){
            var elementInfo = {
                mapId: 0,
                optionId: 0,
                selectionId: 0
            };
            elementInfo.mapId = element.id.substring(element.id.indexOf("bundle-option-")+String("bundle-option-").length);
            if($(element).prop('tagName').toLowerCase() == "select") {
                elementInfo.optionId = elementInfo.mapId;
                if(Object.prototype.toString.call( $(element).val() ) === '[object Array]') {
                    var self = this;
                    $.each($(element).val(), function(key, value){
                        elementInfo.selectionId = value;
                        if(self.options.map[elementInfo.mapId + "-" + elementInfo.selectionId]) {
                            return false;
                        }
                    });
                } else {
                    elementInfo.selectionId = $(element).val();
                }

                elementInfo.mapId += "-" + elementInfo.selectionId;
            } else {
                if(elementInfo.mapId.indexOf("-") > -1) {
                    elementInfo.optionId = elementInfo.mapId.substring(0, elementInfo.mapId.indexOf("-"));
                    elementInfo.selectionId = elementInfo.mapId.substring(elementInfo.mapId.indexOf("-")+1);
                } else {
                    elementInfo.optionId = elementInfo.mapId;
                }

            }
            return elementInfo;
        },
        _enableOrDisablePreorder: function (elementInfo, $place) {
            if(this.options.map[elementInfo.mapId]){
                var $container = $('#bundle-option-'+elementInfo.optionId+'-preorder-note');
                if($container.length == 0) {
                    $place.append('<div class="field"><span id="bundle-option-'+elementInfo.optionId+'-preorder-note">'+this.options.map[elementInfo.mapId].note+'</span></div>');
                } else {
                    $container.html(this.options.map[elementInfo.mapId].note);
                }
                this.options.checkedElements[elementInfo.optionId] = true;
                this.enable();
            } else {
                var $container = $('#bundle-option-'+elementInfo.optionId+'-preorder-note');
                if($container.length > 0) {
                    $container.html('');
                }
                this.options.checkedElements[elementInfo.optionId] = false;

                var counter = 0;

                for (var key in this.options.checkedElements) {
                    if(this.options.checkedElements[key]) {
                        counter++;
                    }
                }
                if(counter == 0) {
                    this.disable();
                }
            }
        }
    }

    );
});
