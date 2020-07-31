define(['Magento_Customer/js/customer-data'], function(customerData) {
    "use strict";

    return function(param){
        if(param['clear'] != "-1") {
            customerData.invalidate([param['clear']]);
        }

    };
});

