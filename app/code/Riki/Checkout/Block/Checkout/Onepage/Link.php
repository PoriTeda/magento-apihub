<?php

namespace Riki\Checkout\Block\Checkout\Onepage;

use Magento\Checkout\Block\Onepage\Link as DefaultLink;

class Link extends DefaultLink
{
    protected $httpContext;

    protected $customerUrl;

    protected $urlHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Checkout\Helper\Data                    $checkoutHelper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->customerUrl = $customerUrl;
        $this->urlHelper = $urlHelper;
        parent::__construct($context, $checkoutSession, $checkoutHelper, $data);
    }

    /**
     * @return string
     */
    public function getSimpleCheckoutUrl()
    {
        if ($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return $this->getUrl('checkout').'#single_order_confirm';
        } else {
            return $this->getUrl(
                \Magento\Customer\Model\Url::ROUTE_ACCOUNT_LOGIN,
                [
                    \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl(
                        $this->getUrl('checkout').'#single_order_confirm'
                    ),
                ]);
        }
    }

    /**
     * @return string
     */
    public function getMultiCheckoutUrl()
    {
        return $this->getUrl('multicheckout').'#multiple_order_confirm';
    }

    public function getSubscriptionCheckoutUrl()
    {
        return $this->getUrl('checkout', array(
            'subscription_checkout' => 'true',
        )).'#single_order_confirm';
    }
}
