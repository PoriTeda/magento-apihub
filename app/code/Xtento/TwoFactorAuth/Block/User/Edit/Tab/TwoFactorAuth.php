<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-17T20:27:32+00:00
 * File:          Block/User/Edit/Tab/TwoFactorAuth.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */
namespace Xtento\TwoFactorAuth\Block\User\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class TwoFactorAuth extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Xtento\TwoFactorAuth\Model\Authenticator\Totp
     */
    protected $authenticator;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $adminhtmlData;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Backend\Helper\Data $adminhtmlData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Backend\Helper\Data $adminhtmlData,
        array $data = []
    ) {
        $this->authenticator = $authenticator;
        $this->encryptor = $encryptor;
        $this->adminhtmlData = $adminhtmlData;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Two-Factor Authentication');
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return $this
     */
    public function _beforeToHtml()
    {
        $this->initForm();
        return parent::_beforeToHtml();
    }

    /**
     * @return void
     */
    protected function initForm()
    {
        $model = $this->_coreRegistry->registry('permissions_user');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('tfa_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Two-Factor Authentication')]);

        $secretKeyBrokenNoticeJs = '';

        $standbyToken = $this->authenticator->createBase32Key();
        $encryptedStandbyToken = $this->encryptor->encrypt($standbyToken);
        if ($model->getTfaLoginSecret() == '') {
            $loginTokenSecret = $standbyToken;
            $model->setTfaLoginSecret($this->encryptor->encrypt($loginTokenSecret));
        } else {
            $loginTokenSecret = $this->encryptor->decrypt($model->getTfaLoginSecret());
            if (strlen($loginTokenSecret) !== 16 || preg_match("/[^a-zA-Z0-9\\+=]/", $loginTokenSecret)) {
                // The secret key is broken. Decryption fails, the Magento encryption key probably changed.
                $secretKeyBrokenNoticeJs = "<script>keyBrokenWarning();</script>";
            }
        }

        $model->setTextLastTokenUsed($model->getTfaLastToken()); // So the last_token_used doesn't get updated in the database

        $urlToQrCode = $this->getQrCodeUrl($model->getUsername(), $loginTokenSecret);
        $fieldset->addField('tfa_login_enabled_toggle', 'checkbox', [
            'name' => 'tfa_login_enabled_toggle',
            'label' => __('Enable for this user'),
            'id' => 'tfa_login_enabled_toggle',
            'title' => __('Enable for this user'),
            'style' => 'margin-top: 10px;',
            'onchange' => 'toggleTokenLoginEnabled(\''.$urlToQrCode.'\')',
            'checked' => ($model->getTfaLoginEnabled() == '1') ? true : false,
        ]);

        $fieldset->addField('token_login_send_mail_toggle', 'checkbox', [
            'name' => 'token_login_send_mail_toggle',
            'label' => __('Send QR code to admin email'),
            'id' => 'token_login_send_mail_toggle',
            'title' => __('Send QR code to admin email'),
            'onchange' => 'toggleTokenSendMail()',
            'checked' => false,
        ]);

        $fieldset->addField('text_last_token_used', 'text', [
            'name' => 'text_last_token_used',
            'label' => __('Last login code used'),
            'id' => 'text_last_token_used',
            'title' => __('Last login code used'),
            'disabled' => true,
            'style' => 'background-color:#f0f0f0; width: 75px;'
        ]);

        $urlToStandbyQrCode = $this->getQrCodeUrl($model->getUsername(), $standbyToken);
        $fieldset->addField('generate_secret', 'note', [
            // Dirty fix as the 'button' type seems to be not working.
            'name' => 'generate_secret',
            'id' => 'generate_secret',
            'label' => __('Create new secret key'),
            'text' => $secretKeyBrokenNoticeJs.'<input type="button" id="tfa_generate_secret" class="form-button" value="' . __('Create new secret key') . '" onclick="regenerateSecretKey(\''.$urlToStandbyQrCode.'\', \''.$encryptedStandbyToken.'\')"/><script>var loginTestUrl = \''.$this->adminhtmlData->getUrl('twofactorauth/token/validate').'\';</script>',
        ]);

        $fieldset->addField('tfa_login_secret', 'hidden', [
            'name' => 'tfa_login_secret',
            'id' => 'tfa_login_secret',
        ]);

        $fieldset->addField('tfa_login_enabled', 'hidden', [
            'name' => 'tfa_login_enabled',
            'id' => 'tfa_login_enabled',
        ]);

        $fieldset->addField('token_login_send_mail', 'hidden', [
            'name' => 'token_login_send_mail',
            'id' => 'token_login_send_mail',
        ]);

        $form->setValues($model->getData());
        $this->setForm($form);
    }

    protected function getQrCodeUrl($adminUsername, $loginTokenSecret)
    {
        $adminUsername = preg_replace("/[^A-Za-z0-9 ]/", "", $adminUsername);
        $gaUrl = urlencode("otpauth://totp/$adminUsername@" . $this->_request->getServer('SERVER_NAME') . "?secret=" . strtoupper($loginTokenSecret));
        $urlToQrCode = "//chart.googleapis.com/chart?cht=qr&chl=$gaUrl&chs=200x200";
        return $urlToQrCode;
    }
}
