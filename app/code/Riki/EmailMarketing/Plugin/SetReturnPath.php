<?php

namespace Riki\EmailMarketing\Plugin;

use Magento\Store\Model\ScopeInterface;

class SetReturnPath
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SetReturnPath constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Amasty\Smtp\Model\Transport $subject
     * @param \Magento\Framework\Mail\MessageInterface $message
     *
     * @return array
     */
    public function beforeSend($subject, $message)
    {
        $isSetReturnPath = $this->scopeConfig->getValue(
            \Riki\EmailMarketing\Model\Transport::XML_PATH_SENDING_SET_RETURN_PATH,
            ScopeInterface::SCOPE_STORE
        );
        $returnPathValue = $this->scopeConfig->getValue(
            \Riki\EmailMarketing\Model\Transport::XML_PATH_SENDING_RETURN_PATH_EMAIL,
            ScopeInterface::SCOPE_STORE
        );

        $returnPathEmail = null;
        if ($isSetReturnPath == '1') {
            $returnPathEmail = $message->getFrom();
        } elseif ($isSetReturnPath == '2' && $returnPathValue !== null) {
            $returnPathEmail = $returnPathValue;
        }

        if ($returnPathEmail) {
            try {
                $message->setReturnPath($returnPathEmail);
            } catch (\Zend_Mail_Exception $e) {
                // $message has been set return-path, do nothing
            }
        }

        return [$message];
    }
}
