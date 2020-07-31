define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'jquery'
], function (SelectElement,registry,$) {
    return SelectElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.b2b_flag:value'
            }
        },
        update: function (value) {
            this.visible(value);

            if(value == 0){
                var options = this.options();
                var option_empty = $.extend({}, options[1]);
                option_empty.value = 0;
                option_empty.label = 'Empty';
                options.push(option_empty);
                this.setOptions(options);
                this.value(0);
            }
            else{
                var options_new = this.options();
                var options_old = options_new.slice();
                var option_empty = options_new.pop();
                if(option_empty.label == 'Empty'){
                    this.setOptions(options_new);
                }
                else{
                    this.setOptions(options_old);
                }
            }

            if(!window.adminhtml_shoshacustomer_acl){
                this.visible(false);
            }
        }
    });
});