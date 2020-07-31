function updateShippingAddress(itemsE, url, deliveryE) {
    if(!confirm('The delivery info will be clearing if you change shipping address. Are you sure to continue?'))
        return;

    if ($(itemsE)) {
        var b = $(itemsE).select("input", "select", "textarea");
        var f = Form.serializeElements(b, true);
        url = url + (url.match(new RegExp("\\?")) ? "&isAjax=true" : "?isAjax=true");
        new Ajax.Request(url, {
            parameters: $H(f), loaderArea: itemsE, onSuccess: function (l) {
                try {
                    if (l.responseText.isJSON()) {
                        var g = l.responseText.evalJSON();
                        if (g.error) {
                            alert(g.message)
                        }
                        if (g.ajaxExpired && g.ajaxRedirect) {
                            setLocation(g.ajaxRedirect)
                        }
                    } else {
                        if($(deliveryE))
                            $(deliveryE.parentNode).update(l.responseText)
                    }
                } catch (h) {
                    if($(deliveryE))
                        $(deliveryE.parentNode).update(l.responseText)
                }
            }
        })
    }
}