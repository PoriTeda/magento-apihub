/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */


InventoryManager = {
    debug: true,
    log: function (method, arguments) {
        if (this.debug)
            console.log("InventoryManager says " + method + "()", arguments);
    },
    apply: function (url) {
        jQuery("FORM").attr("action", url).submit();
        this.log('apply', arguments);
    }
};
