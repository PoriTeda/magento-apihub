define([
    'jquery',
    'mage/url',
    'mage/mage',
    'mage/translate',
    'jquery/ui'
], function($, urlBuilder) {
    "use strict";

    $.widget('serialcode.js', {
        _create: function() {
            var options = this.options;
            var self = this;
            $('#serial-code-form').mage('validation', {
                submitHandler: function (form) {
                    var form_key = $('#serial-code-form input[name="form_key"]').val(),
                        serialCode = $('#serial-code-form input[name="serial_code"]').val(),
                        serviceUrl = $('#serial-code-form').attr('action');;
                    var validate = self._validateSerialCode(serialCode);
                    if( !validate ){
                        return false;
                    }
                    $('#serial-code-form').addClass('disabled');
                    $.ajax({
                        url: serviceUrl,
                        type: "POST",
                        dataType: 'json',
                        data: {
                            form_key: form_key,
                            serial_code: serialCode
                        },
                        beforeSend: function() {
                            $('.serialcode-messages').html('');
                        },
                        success: function (data) {
                            self._showMessage(data.err, data.msg);
                            if(!data.err)
                                $('#serial-code-form .input-text').val('');
                            $('#serial-code-form').removeClass('disabled');
                        }
                    });
                    return false;
                }
            });
        },
        _validateSerialCode: function(serialCode){
            var rs = true;
            rs = !this._isDoubleByte(serialCode);
            if(rs){
                rs = this._isAlphaNum(serialCode);
            }
            return rs;
        },
        _isDoubleByte: function(str){
            var res = false;
            for( var i = 0; i < str.length; i++ ){
                if(str.charCodeAt(i) > 255){
                    res = true;
                    this._showMessage(true, $.mage.__('Please enter Serial Code in half-byte characters.'));
                    break;
                }
            }
            return res;
        },
        _isAlphaNum: function(str){
            if( /(\d+[a-zA-Z]+|[a-zA-Z]+\d+)/.test(str) && str.length === 12){
                return true;
            } else {
                this._showMessage(true, $.mage.__('The number is invalid. Please re-enter the correct number in the box.'));
                return false;
            }
        },
        _showMessage: function( error, message){
            var msgClass = error == true ? 'message-error error message' : 'message-success success message';
            var msg = '<div class="'+ msgClass +'">';
            msg += '<div>'+ message +'</div>';
            msg += '</div>';
            $('.serialcode-messages').html(msg);
        }
    });
    return $.serialcode.js;
});