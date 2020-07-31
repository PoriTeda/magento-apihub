define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    var ampromoForm = {
        update: function (type) {
            var action = '',
                actionFieldSet = $('#' + type +'rule_actions_fieldset_').parent(),
                discountRuleTree = $('.rule-tree fieldset');
            this.resetFields(type);
            window.amPromoHide = 0;

            actionFieldSet.show();
            if (typeof window.amRulesHide !="undefined" && window.amRulesHide == 1) {
                actionFieldSet.hide();
            }

            var selector = $('[data-index="simple_action"] select');
            if (selector.length) {
                if (type !== 'sales_rule_form') {
                    action = selector[1] ? selector[1].value : selector[0].value ? selector[0].value : undefined;
                } else {
                    action = selector.val();
                }
            }

            if (!action) {
                action = 'by_percent';
            }

            if (action.match(/^ampromo/)) {
                this.hideFields(['simple_free_shipping', 'apply_to_shipping'], type);
                this.showFields(['ampromorule[att_visible_cart]', 'ampromorule[att_visible_user_account]'], type);

                var rule_ampromo_type = $('[data-index="ampromorule[type]"] select').val();

                if(rule_ampromo_type == 1){
                    this.hideFields(['ampromorule[att_visible_cart]'], type);
                }else{
                    this.showFields(['ampromorule[att_visible_cart]'], type);
                }
            }

            discountRuleTree.show();
            switch (action) {
                case 'ampromo_cart':
                    actionFieldSet.hide();
                    window.amPromoHide = 1;

                    this.hideFields(['discount_qty', 'discount_step'], type);
                    this.showFields(['ampromorule[sku]', 'ampromorule[type]'], type);
                    break;
                case 'ampromo_items':
                    this.showFields(['ampromorule[sku]', 'ampromorule[type]'], type);
                    break;
                case 'ampromo_product':
                    break;
                case 'ampromo_spent':
                    this.showFields(['ampromorule[sku]', 'ampromorule[type]'], type);
                    break;
                case 'ampromo_eachn':
                    actionFieldSet.hide();
                    this.showFields(['ampromorule[sku]', 'ampromorule[type]'], type);
                    break;
            }
        },
        showPromoItemPriceTab: function () {
            $('[data-index=ampromorule_items_price]').show();
        },

        hidePromoItemPriceTab: function () {
            $('[data-index=ampromorule_items_price]').hide();
        },

        resetFields: function (type) {
            this.showFields([
                'discount_qty', 'discount_step', 'apply_to_shipping', 'simple_free_shipping'
            ], type);
            this.hideFields(['ampromorule[sku]', 'ampromorule[type]', 'ampromorule[att_visible_cart]', 'ampromorule[att_visible_user_account]'], type);
        },

        hideFields: function (names, type) {
            return this.toggleFields('hide', names, type);
        },

        showFields: function (names, type) {
            return this.toggleFields('show', names, type);
        },

        addPrefix: function (names, type) {
            for (var i = 0; i < names.length; i++) {
                names[i] = type + '.' + type + '.' + 'actions.' + names[i];
            }

            return names;
        },

        toggleFields: function (method, names, type) {
            registry.get(this.addPrefix(names, type), function () {
                for (var i = 0; i < arguments.length; i++) {
                    arguments[i][method]();
                }
            });
        },

        /**
         *
         * @param action
         */
        renameRulesSetting: function (action) {
            var discountStep = $('[data-index="discount_step"] label span'),
                discountAmount = $('[data-index="discount_amount"] label span');

            switch (action) {
                case 'ampromo_eachn':
                    discountStep.text($.mage.__("Each N-th"));
                    discountAmount.text($.mage.__("Number Of Gift Items"));
                    break;
                case 'ampromo_cart':
                case 'ampromo_items':
                case 'ampromo_product':
                case 'ampromo_spent':
                    discountAmount.text($.mage.__("Number Of Gift Items"));
                    break;
                default:
                    discountAmount.text($.mage.__("Discount Amount"));
                    discountStep.text($.mage.__("Discount Qty Step (Buy X)"));
                    break;
            }
        },

        hideTabs: function () {
            this.hidePromoItemPriceTab();
        }
    };

    return ampromoForm;
});
