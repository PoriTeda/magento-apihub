<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Helper;

class Messages extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $resourceSession,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $resourceSession;
        $this->messageManager = $messageManager;
    }

    public function addAvailabilityError($product)
    {
        $this->showMessage(
            __(
                "We apologize, but your free gift <strong>%1</strong> is not available at the moment",
                $product->getName()
            )
        );
    }

    public function showMessage($message, $isError = true, $showEachTime = false)
    {
        $displayErrors = $this->scopeConfig->isSetFlag(
            'ampromo/messages/display_error_messages',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$displayErrors && $isError)
            return;

        $displaySuccess = $this->scopeConfig->isSetFlag(
            'ampromo/messages/display_success_messages',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$displaySuccess && !$isError)
            return;

        $all = $this->messageManager->getMessages(false);

        foreach ($all as $existingMessage) {
            if ($message == $existingMessage->getText()) {
                return;
            }
        }

        if ($isError && $this->_request->getParam('debug')){
            $this->messageManager->addError($message);
        }
        else {
            $arr = $this->_checkoutSession->getAmpromoMessages();
            if (!is_array($arr)){
                $arr = [];
            }
            if (!in_array($message, $arr) || $showEachTime){
                $this->messageManager->addNotice($message);
                $arr[] = $message;
                $this->_checkoutSession->setAmpromoMessages($arr);
            }
        }
    }
}
