/**
 * Copyright XTENTO / http://www.xtento.com
 */
function keyBrokenWarning()
{
    alert('Two Factor Authentication - WARNING: Your secret key is broken. You have probably updated your Magento installation or have moved the database to another installation and forgot to use the same Magento encryption key. Please click \'Create New Secret Key\' to create a new secret key and scan it using the Google Authenticator application. This is a VERY important notice. Two-Factor Authentication has been disabled for this account until you\'ve generated a new secret key.');
}

function getGenerationHtml(qrCodeUrl)
{
    return '<div id=\'tfa_qr_tr\' class=\'admin__field field\'><label class=\'label admin__field-label\' for=\'tfa_qrcode\'><span>Secret key</span></label><div class=\'admin__field-control control\' style=\'width:650px;\'>' +
        '<strong>Step 1:</strong> Please scan the following QR code using the Google Authenticator application:<br/><img src=\'' + qrCodeUrl + '\' style=\'padding-top:5px;padding-bottom:5px;\'><br/>' +
        '<strong>Step 2:</strong> After scanning the QR code, please enter the current code you see in Google Authenticator to test-run the login:<br/><input id=\'tfa_test_login_code\' type=\'text\' maxlength=\'6\' style=\'width:80px; vertical-align:top; margin-top:5px;\' onkeypress=\'submitLogin(this, event)\'/>&nbsp;<input type=\'button\' class=\'form-button\' value=\'Test Login\' onclick=\'testLogin();\' style=\'vertical-align:top; margin-top:5px;\'/>' +
        '<div id=\'tfa_step3\' style=\'display:none;\'><br/><strong>Step 3:</strong> Token validated successfully. Click \'Save User\' to enable Two-Factor Authentication for this account.</div></div></div>';
}

function toggleTokenLoginEnabled(urlToQrCode)
{
    if ($('tfa_tfa_login_enabled_toggle').checked) {
        $('tfa_tfa_login_enabled').value = '1';
        if ($('tfa_qr_tr')) {
            $('tfa_qr_tr').show();
        } else {
            $('tfa_text_last_token_used').parentNode.parentNode.insert({after:getGenerationHtml(urlToQrCode)});
        }
    } else {
        $('tfa_tfa_login_enabled').value = '0';
        $('tfa_tfa_login_enabled_toggle').value = '0';
        if ($('tfa_qr_tr')) {
            $('tfa_qr_tr').hide();
        }
    }
}

function toggleTokenSendMail()
{
    if ($('tfa_token_login_send_mail_toggle').checked) {
        $('tfa_token_login_send_mail').value = '1';
    } else {
        $('tfa_token_login_send_mail').value = '0';
    }
}

function regenerateSecretKey(urlToStandbyQrCode, encryptedStandbyToken)
{
    if ($('tfa_tfa_login_secret').value !== '') {
        var regenerate_confirmation = confirm('Warning: By regenerating a new secret key you will have to scan the new code which will be shown in Google Authenticator, as your old key gets voided by clicking OK. Otherwise you won\'t be able to log in anymore. Are you sure you want to proceed?');
    } else {
        var regenerate_confirmation = true;
    }
    if (regenerate_confirmation) {
        $('tfa_tfa_login_enabled_toggle').checked = true;
        $('tfa_tfa_login_enabled').value = '1';
        $('tfa_tfa_login_secret').value = encryptedStandbyToken;
        if ($('tfa_qr_tr')) {
            $('tfa_qr_tr').update(getGenerationHtml(urlToStandbyQrCode));
        } else {
            $('tfa_text_last_token_used').parentNode.parentNode.insert({after:getGenerationHtml(urlToStandbyQrCode)});
        }
        $('tfa_generate_secret').parentNode.parentNode.hide();
    }
}

function testLogin()
{
    new Ajax.Request(loginTestUrl, {
        method:'POST',
        parameters:{form_key: FORM_KEY, entered_code:$('tfa_test_login_code').value, secret_key:$('tfa_tfa_login_secret').value},
        onFailure:function (transport) {
            alert("Code validation failed. The login test controller couldn't be called.");
        },
        onSuccess:function (transport) {
            if (transport.responseText.isJSON()) {
                var response = transport.responseText.evalJSON();
                if (!response.error) {
                    $('tfa_step3').show();
                }
                alert(response.message);
            } else {
                alert(transport.responseText);
            }
        }.bind(this)
    });
}

function submitLogin(field, event)
{
    var keycode;
    if (window.event) {
        keycode = window.event.keyCode; } else if (event) {
        keycode = event.which; } else {
            return true; }

        if (keycode == 13) {
            testLogin();
            return false;
        } else {
            return true; }
}