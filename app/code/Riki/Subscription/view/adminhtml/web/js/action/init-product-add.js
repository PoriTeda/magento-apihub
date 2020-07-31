
define([
    'jquery',
    'Riki_Subscription/js/action/add-product'
], function(jQuery){
    'use strict';

    var $el = jQuery('#form-edit'),
        addUrl,
        profileProductAdd,
        profileAdditionalProductAdd;

    if (!$el.length) {
        return;
    }

    addUrl = $el.data('load-add-url');

    profileProductAdd = new SubscriptionProfileProductAdd({});
    profileProductAdd.setAddUrl(addUrl);

    profileAdditionalProductAdd = new SubscriptionProfileProductAdd({'is_additional':1});
    profileAdditionalProductAdd.setAddUrl(addUrl);

    window.profileProductAdd = profileProductAdd;
    window.profileAdditionalProductAdd = profileAdditionalProductAdd;

});