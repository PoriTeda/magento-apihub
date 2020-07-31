require([
    'jquery',
    'jquery/validate',
    'mage/translate'
], function ($, $t) {
    $(function () {

        $.validator.addMethod('validate-business-code', function (value) {
            var len = value.match(/\d/g);

            if(len != null && (len.length !=  10)){
                return false;
            }
            return (/^\d{10}$/.test(value));
        }, $.mage.__('Business code must be 10 digits'));

        $.validator.addMethod('validate-commission', function (value) {
            return value.match(/(^([1-9]([0-9])?|0)(\.[0-9]{1,2})?$)/);
        }, $.mage.__('Invalid commission percentage'));

        $.validator.addMethod('validate-special-character', function (value) {
            var specialCharacters  = [
                '∠','⊥','⌒','∂','∇','≡','≒','≪','≫','√',
                '∽','∝','∵','∫','∬','∈','∋','⊆','⊇','⊂',
                '⊃','∪','∩','∧','∨','￢','⇒','⇔','∀','∃',
                'Å','‰','♯','♭','♪','†','‡','¶','◯','─',
                '│','┌','┐','┘','└','├','┬','┤','┴	','┼',
                '━','┃','┏','┓','┛','┗','┣','┳','┫','┻',
                '╋','┠','┯','┨','┷','┿','┝','┰','┥','┸',
                '╂','凜','熙'
            ];
            if(value != ''){
                for(var index in specialCharacters){
                    if (value.indexOf(specialCharacters[index]) > -1)
                    {
                        return false;
                    }
                }
            }
            return true;
        }, $.mage.__('These character couldn\'t be registered'));


        $.validator.addMethod('validate-cedyna-length-shosha_cmp-shosha_dept-shosha_in_charge', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp').value.length + document.getElementById('shoshacustomer_shosha_dept').value.length + document.getElementById('shoshacustomer_shosha_in_charge').value.length  > 72){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name,Company department name,Name of person in charge exceeds 72'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp-shosha_dept', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp').value.length + document.getElementById('shoshacustomer_shosha_dept').value.length  > 73){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name,Company department name exceeds 73'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp-shosha_in_charge', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp').value.length  + document.getElementById('shoshacustomer_shosha_in_charge').value.length  > 73){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name,Name of person in charge exceeds 73'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp').value.length > 74){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name exceeds 74'));


        $.validator.addMethod('validate-cedyna-length-shosha_address1-shosha_address2', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_address1').value.length + document.getElementById('shoshacustomer_shosha_address2').value.length > 65){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company address 1,Company address 2 exceeds 65'));


        //kana
        $.validator.addMethod('validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana-shosha_in_charge_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp_kana').value.length + document.getElementById('shoshacustomer_shosha_dept_kana').value.length + document.getElementById('shoshacustomer_shosha_in_charge_kana').value.length  > 72){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name kana,Company department name kana,Name of person kana in charge exceeds 72'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp_kana').value.length + document.getElementById('shoshacustomer_shosha_dept_kana').value.length  > 73){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name kana,Company department kana name exceeds 73'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp_kana-shosha_in_charge_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp_kana').value.length  + document.getElementById('shoshacustomer_shosha_in_charge_kana').value.length  > 73){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name kana,Name of person kana in charge exceeds 73'));

        $.validator.addMethod('validate-cedyna-length-shosha_cmp_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_cmp_kana').value.length > 74){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company name kana exceeds 74'));

        $.validator.addMethod('validate-cedyna-length-shosha_address1_kana-shosha_address2_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_address1_kana').value.length + document.getElementById('shoshacustomer_shosha_address2_kana').value.length > 65){
                    return false;
                }
            }
            return true;
        }, $.mage.__('Total length of Company address 1 kana,Company address 2 kana exceeds 65'));

        $.validator.addMethod('validate-cedyna-length-shoshacustomer_shosha_phone', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3){
                if(document.getElementById('shoshacustomer_shosha_phone').value.length > 12){
                    return false;
                }
            }

            return true;
        }, $.mage.__('Total length of Company phone number exceeds 12'));


        $.validator.addMethod('validate-shoshacustomer_shosha_phone', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3) {
                if (value != '') {
                    if (value.indexOf('-') > -1) {
                        return false;
                    }

                    if (value.charAt(0) != 0) {
                        return false;
                    }
                }
            }
            return true;
        }, $.mage.__('Company phone number cannot contain \'-\' and must start with 0'));

        $.validator.addMethod('validate-phone-number', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value != 3) {
                if (value != '') {
                    var len = value.match(/\d/g);
                    if (len != null && (len.length > 11 || len.length < 10)) {
                        return false;
                    }
                    return /(^\d+(-|\d)+)$/.test(value);
                }
            }
            return true;
        }, $.mage.__('Please enter a valid phone number. For example 123-456-7890.'));



        $.validator.addMethod('validate-shoshacustomer_shosha_address2_kana', function (value) {
            if(document.getElementById('shoshacustomer_shosha_code').value == 3) {
                if (value.charAt(0) == '(') {
                    return false;
                }

                if (value.charAt(value.length - 1) == ')') {
                    return false;
                }
            }
            return true;
        }, $.mage.__('Company address 2 - Kana cannot start with \'(\' nor end with \')\''));

        $.validator.addMethod('validate-shoshacustomer_shosha_address2_kana_special_character', function (value) {
            if (value != '') {
                if (document.getElementById('shoshacustomer_shosha_code').value == 3) {
                    return /[^ﾞﾟ.\s]+/.test(value);
                }
            }
            return true;
        }, $.mage.__('Company address 2 - Kana cannot include only  (ﾞ)(ﾟ)(.)'));


        $.validator.addMethod('validate-katakana',  function (value) {
            return !/[^\uFF67-\uFF9F0-9A-z\~\!\@\#\$\%\^\&\*\(\)\_\+\{\}\[\]\`\=\\\:\;\"\'\,\･\.\<\>\?\/\|\s-]+/.test(value);
        }, $.mage.__('Please enter half-width katakana character.'));

        $.validator.addMethod('validate-katakana-address',  function (value) {
            return !/[^\uFF67-\uFF9F0-9a-zA-Z\~\!\@\#\$\%\^\&\*\(\)\_\+\{\}\[\]\`\=\\\:\;\"\'\,\.\<\>\?\/\|\s-]+/.test(value);
        }, $.mage.__('Please enter half-width katakana character, ASCII character, number and symbol.'));

        $.validator.addMethod('validate-custom-postal-code',  function (value) {
            if (value != '') {
                return /^\d{3}-\d{4}$/.test(value);
            }
            return true;
        }, $.mage.__('Your Postcode must be in the format 000-0000'));


        $.validator.addMethod( 'validate-full-width',  function (value) {
            if (value != '') {
                var regexJapanese = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g;
                return (regexJapanese.test(value) && !/[\uFF67-\uFF9FA-z0-9\(\)-]+/.test(value));
            }
            return true;
        }, $.mage.__('Please enter full-width character.'));

        function cedynaValidator()
        {
            if ($('#shoshacustomer_shosha_code').length > 0 ) {

                var shoshaType = $('#shoshacustomer_shosha_code').val();

                /*shosha type is Cedyna*/
                if (shoshaType == 3) {
                    $('.cedyna-required').addClass('required-entry');
                    $('.cedyna-required').parents('.admin__field').addClass('_required');
                } else {
                    $('.cedyna-required').removeClass('required-entry');
                    $('.cedyna-required').parents('.admin__field').removeClass('_required');
                    $('.cedyna-required').parents('.admin__field').find('.mage-error').hide();
                }
            }
        }

        $(document).ready(function(){
            cedynaValidator();
            $("#shoshacustomer_shosha_code").change(function(){
                $("#shoshacustomer_shosha_first_code").val($("#shoshacustomer_shosha_code").val());
                $("#shoshacustomer_shosha_second_code").val($("#shoshacustomer_shosha_code").val());
                cedynaValidator();
            });
        });

    });
});