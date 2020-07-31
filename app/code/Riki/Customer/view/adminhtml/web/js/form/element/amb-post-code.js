define([
    'ajaxzip3',
    'uiRegistry',
    'Magento_Ui/js/form/element/post-code',
    'Riki_ZipcodeValidation/js/zipcode/value-converter',
    'Riki_ZipcodeValidation/js/lib/element/validation/rules'
], function (AjaxZip3, registry, defaultPostCode, valueConverter) {
    'use strict';
    
    return defaultPostCode.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.amb_type:value'
            }
        },
        update: function (value) {
            this.visible(value);
        },
        onUpdate: function () {
            this._super();
            valueConverter(this, this.value);

            if (this.value().replace('-', '').length == 7) {
                // checkout billing/shipping address form street
                var street = registry.get(this.parentName + '.COM_ADDRESS3'),
                    regionId = registry.get(this.parentName + '.COM_ADDRESS1'),
                    city = registry.get(this.parentName + '.COM_ADDRESS2');

                // set default country to JP
                registry.get(
                    this.parentName + '.country_id', function (country) {
                        country.value('JP');
                    }
                );

                var elementMap = [];

                elementMap[this.uid] = this;
                elementMap[street.uid] = street;
                elementMap[regionId.uid] = regionId;
                elementMap[city.uid] = city;

                AjaxZip3.setElementMap(elementMap);

                AjaxZip3.zip2addr(
                    document.getElementById(this.uid),
                    '',
                    document.getElementById(regionId.uid),
                    document.getElementById(city.uid),
                    document.getElementById(street.uid),
                    '',
                    '',
                    1
                );
            }

        }
    });
});