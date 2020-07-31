/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
    'ajaxzip3',
    'uiRegistry',
    'Magento_Ui/js/form/element/post-code',
    'Riki_ZipcodeValidation/js/zipcode/value-converter',
    'Riki_ZipcodeValidation/js/lib/element/validation/rules'
    ], function (AjaxZip3, registry, defaultPostCode, valueConverter) {
        'use strict';

        return defaultPostCode.extend(
            {
                onUpdate: function () {
                    this._super();
                    valueConverter(this, this.value);

                    if (this.value().replace('-', '').length == 7) {
                        // checkout billing/shipping address form street
                        var street = registry.get(this.parentName + '.street.0'),
                        regionId = registry.get(this.parentName + '.region_id');

                        // admin customer address from street
                        if (typeof street == 'undefined') {
                            street = registry.get(this.parentName + '.street_container.street');
                        }

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

                        AjaxZip3.setElementMap(elementMap);

                        AjaxZip3.zip2addr(
                            document.getElementById(this.uid),
                            '',
                            document.getElementById(regionId.uid),
                            document.getElementById(street.uid),
                            '',
                            '',
                            ''
                        );
                    }

                }
            }
        );
    }
);
