<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-04-07T09:52:53+00:00
 * File:          Observer/AdminUserAuthenticateAfterObserver.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\TwoFactorAuth\Observer;

class AdminUserAuthenticateAfterObserver implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddr;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $httpHeader;

    /**
     * @var \Xtento\TwoFactorAuth\Model\Authenticator\Totp
     */
    protected $authenticator;

    /**
     * @param \Xtento\TwoFactorAuth\Helper\Module $moduleHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddr
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator
     */
    public function __construct(
        \Xtento\TwoFactorAuth\Helper\Module $moduleHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddr,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->resource = $resource;
        $this->remoteAddr = $remoteAddr;
        $this->httpHeader = $httpHeader;
        $this->authenticator = $authenticator;
    }

    protected function isTfaDisabledForIP()
    {
        $disabled = true;

        $allowedIps = $this->scopeConfig->getValue('twofactorauth/general/allow_ips');
        $remoteAddr = $this->remoteAddr->getRemoteAddress();
        $httpHost = $this->httpHeader->getHttpHost();
        $forwardedFor = 'XXXXXXXXX';
        $serverForwardedForValue = $this->request->getServer('HTTP_X_FORWARDED_FOR');
        if (!empty($serverForwardedForValue)) {
            $forwardedFor = explode(',', $serverForwardedForValue); // NginX SSL offloading into Varnish into NginX as PHP-FPM loadbalancer. The resulting IP was <our_ip>, 127.0.0.1
            $forwardedFor = $forwardedFor[0];
        }
        if (!empty($allowedIps) && !empty($remoteAddr)) {
            $allowedIps = preg_split('#\s*,\s*#', $allowedIps, null, PREG_SPLIT_NO_EMPTY);
            if (array_search($remoteAddr, $allowedIps) === false &&
                array_search($httpHost, $allowedIps) === false &&
                array_search($forwardedFor, $allowedIps) === false
            ) {
                $disabled = false;
            }
        } else {
            $disabled = false;
        }

        return $disabled;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void|AdminUserAuthenticateAfterObserver
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->moduleHelper->isModuleEnabled() || !$this->moduleHelper->confirmEnabled(true)) {
            return $this;
        }
        if ($observer->getEvent()->getResult() === false) {
            return $this;
        }

        $enteredToken = $this->request->getPost('token');
        $user = $observer->getEvent()->getUser();

        if (($this->request->getControllerName() == 'user' && $this->request->getActionName() == 'save')
            ||
            ($this->request->getControllerName() == 'user_role' && $this->request->getActionName() == 'saverole')
            ||
            ($this->request->getControllerName() == 'system_account' && $this->request->getActionName() == 'save')
        ) {
            // Do not check when admin user/role or my account is edited
            return $this;
        }

        if ($user->getId() && $user->getTfaLoginEnabled() == '1' && $user->getTfaLoginSecret() !== '') {
            // Is login attempt from an IP address that doesn't need TFA?
            if ($this->isTfaDisabledForIP()) {
                return $this;
            }
            // Check token
            $loginTokenSecret = $this->encryptor->decrypt($user->getTfaLoginSecret());
            if (strlen($loginTokenSecret) !== 16 || preg_match("/[^a-zA-Z0-9\\+=]/", $loginTokenSecret)) {
                // The secret key is broken. Decryption fails, the Magento encryption key probably changed.
                $this->messageManager->addWarningMessage(
                    __(
                        "Two Factor Authentication - WARNING: Your secret key is broken. You have probably updated your Magento installation or have moved the database to another installation and forgot to use the same Magento encryption key.<br/><br/>Please go to System > All Users, select your account, select the 'Two Factor Authentication' tab on the left and click the 'Create New Secret Key' button to create a new secret key and scan it using the Google Authenticator application. This is a VERY important notice. Two-Factor Authentication has been disabled for this account until you've generated a new secret key."
                    )
                );
                return $this;
            } else {
                if (empty($enteredToken)) {
                    $user->unsetData();
                    throw new \Magento\Framework\Exception\LocalizedException(__('Please enter your security code.'));
                }
                if ($user->getLastTokenUsed() == $enteredToken) {
                    // This code can't be used anymore as it was used for the last login. Code was not empty.
                    $user->unsetData();
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            'The security code you have entered has been used for the last login and thus has been disabled. Please wait and login using a new security code.'
                        )
                    );
                } else {
                    // Verify security code
                    if ($this->authenticator->authenticateUser($enteredToken, $loginTokenSecret)) {
                        $writeAdapter = $this->resource->getConnection('write');
                        $condition = $writeAdapter->quoteInto('user_id=?', $user->getId());
                        $writeAdapter->update(
                            $this->resource->getTableName('admin_user'),
                            ['tfa_last_token' => $enteredToken],
                            $condition
                        );
                        // Success!
                        return $this;
                    } else {
                        $user->unsetData();
                        throw new \Magento\Framework\Exception\LocalizedException(__('Wrong security code.'));
                    }
                }
            }
        } else {
            if ($enteredToken !== '' && $this->request->getPost('current_password') == '') {
                $this->messageManager->addNoticeMessage(
                    __(
                        'You have entered a security code even though Two-Factor Authentication is not enabled for your account. You should go to System > All Users to enable Two-Factor Authentication for your account.'
                    )
                );
            }
        }

        return $this;
    }
}
