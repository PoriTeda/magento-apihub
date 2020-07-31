<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-18T08:18:31+00:00
 * File:          Observer/AdminUserSaveAfterObserver.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\TwoFactorAuth\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

class AdminUserSaveAfterObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Xtento\TwoFactorAuth\Helper\Module
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AdminUserSaveAfterObserver constructor.
     *
     * @param \Xtento\TwoFactorAuth\Helper\Module $moduleHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param Registry $registry
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Xtento\TwoFactorAuth\Helper\Module $moduleHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        Registry $registry,
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->registry = $registry;
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $user = $observer->getEvent()->getObject();
        if ($this->request->getPost('token_login_send_mail', false) > 0 && $user->getId()) {
            if (!$this->moduleHelper->isModuleEnabled()) {
                return $this;
            }
            if ($user->getTfaLoginSecret() != '' && $this->registry->registry('tfa_token_email_sent') === null) {
                // TFA enabled, send email to user
                $adminUsername = preg_replace("/[^A-Za-z0-9 ]/", "", $user->getUsername());
                $gaUrl = urlencode(
                    "otpauth://totp/$adminUsername@" . $_SERVER['SERVER_NAME'] . "?secret=" . strtoupper(
                        $this->encryptor->decrypt($user->getTfaLoginSecret())
                    )
                );
                $urlToQrCode = "https://chart.googleapis.com/chart?cht=qr&chl=$gaUrl&chs=200x200";

                /** @var \Magento\Framework\Mail\Message $message */
                $mail = $this->objectManager->create('Magento\Framework\Mail\MessageInterface');
                $mail->setFrom($this->scopeConfig->getValue('trans_email/ident_general/email'), $this->scopeConfig->getValue('trans_email/ident_general/name'));
                $mail->addTo($user->getEmail(), '=?utf-8?B?' . base64_encode($user->getEmail()) . '?=');
                $mail->setSubject('=?utf-8?B?' . base64_encode(__('Two-Factor Authentication QR Code')) . '?=');
                $mail->setMessageType(\Magento\Framework\Mail\Message::TYPE_HTML)->setBody(
                    __(
                        'Please open and scan the following QR code using the Google Authenticator application on your smartphone: <a href="%1">QR Code</a><br/><br/>The code that is shown in the Google Authenticator application needs to be entered when logging into the admin panel then.',
                        $urlToQrCode
                    )
                );
                $mail->send($this->objectManager->create('\Magento\Framework\Mail\TransportInterfaceFactory')->create(['message' => clone $mail]));

                $this->registry->register('tfa_token_email_sent', true, true);
            }
        }
        return $this;
    }
}
