define(['uiComponent','jquery'], function(Component,$) {
    return Component.extend({
        initialize: function () {
            this._super();
        },
        generateOption: function (item, event) {
            var self = $(event.target);
            if (self.data('render') == '0') {
                var str = "";
                var len = self.data('quantity');
                var i = 11;
                for (i; i <= len; i++) {
                    str += "<option value='" + i + "'>" + i + "</option>";
                }
                self.append(str);
                self.data('render', '1');
                self.unbind("click");
                self.unbind("touchstart");
            }
            return false;
        },
    });
});