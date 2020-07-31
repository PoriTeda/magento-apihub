<?php

namespace Riki\Subscription\Plugin;

use \Riki\Checkout\Controller\Index\Single as RikiCheckout;

class CheckApplicationLimitCheckout extends CheckApplicationLimitAbstract
{
    const CART_ROUTER_PATH = 'checkout/cart/index';

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * CheckApplicationLimitCheckout constructor.
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->url = $url;
        $this->messageManager = $messageManager;
        parent::__construct(
            $customerSession,
            $checkoutSession,
            $subscriptionPageHelper
        );
    }

    /**
     * @param RikiCheckout $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(RikiCheckout $subject, callable $proceed)
    {
        $url = $this->checkoutSession->getCartRefererUrl();

        if ($this->customerSession->isLoggedIn()) {
            $applicationLimitValidatingResult = $this->validateApplicationLimit();
            if ($applicationLimitValidatingResult['has_error']) {
                if($url === null || $url === ''){
                    return $this->redirectToCartPage();
                } else{
                    $errorMessage = $this->subscriptionPageHelper->getApplicationLimitErrorMessage(
                        $applicationLimitValidatingResult
                    );
                    $this->messageManager->addError($errorMessage);
                    return $this->resultRedirectFactory->create()->setPath($url);
                }
            }
        }
        return $proceed();
    }

    /**
     * Redirect to cart page
     * @return mixed
     */
    private function redirectToCartPage()
    {
        $redirectionUrl = $this->url->getUrl(self::CART_ROUTER_PATH);
        return $this->resultRedirectFactory->create()->setUrl($redirectionUrl);
    }
}
