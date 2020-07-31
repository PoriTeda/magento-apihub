<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2015-07-26T20:58:01+00:00
 * File:          Controller/Adminhtml/Token/Validate.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\TwoFactorAuth\Controller\Adminhtml\Token;

class Validate extends \Magento\Backend\App\Action
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
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Xtento\TwoFactorAuth\Model\Authenticator\Totp $authenticator,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->authenticator = $authenticator;
        $this->encryptor = $encryptor;
    }

    public function execute()
    {
        $enteredCode = $this->getRequest()->getPost('entered_code');
        $secretKey = $this->encryptor->decrypt($this->getRequest()->getPost('secret_key'));
        if ($this->authenticator->authenticateUser($enteredCode, $secretKey)) {
            $responseContent = [
                'error' => false,
                'message' => __(
                    'Login code correct. Congratulations! Please click \'Save User\' to enable Two-Factor Authentication for this user.'
                )
            ];
        } else {
            $responseContent = [
                'error' => true,
                'message' => __(
                    'Login failed, wrong code. If this error keeps occurring, this could be caused by a wrong time setting on either your smartphone or on your server. Please synchronize the time of your smartphone and if that does not help, get in touch with your server administrator so they make sure the server time is correct.'
                )
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
