<?php

namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class ValidateSessionUniqueKey implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * ValidateSessionUniqueKey constructor.
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $requestModel */
        $requestModel = $observer->getEvent()->getRequestModel();

        /** @var \Magento\Backend\Model\Session\Quote $session */
        $session = $observer->getEvent()->getSession();

        if ($requestKey = $requestModel->getParam('session_unique_key')) {
            if ($requestKey != $session->getSessionUniqueKey()) {
                $requestModel->setParams(['block'   =>  'unique_session_error']);

                throw new LocalizedException(__('Validation is failed.'));
            }
        }
    }
}