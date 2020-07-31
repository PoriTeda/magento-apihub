<?php

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

class ValidateQuoteBeforeCheckout implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->messageManager = $messageManager;
        $this->urlInterface     = $urlInterface;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //check quote exist
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this;
        }

        $validate = $quote->canPlaceOrder();

        if ($validate['error']) {

            $this->messageManager->addErrorMessage($validate['message']);

            $url = $this->urlInterface->getUrl('checkout/cart');
            $observer->getControllerAction()
                ->getResponse()
                ->setRedirect($url);
            return false;
        }

        return $this;
    }
}