<?php

namespace Riki\Customer\Plugin\Checkout\Controller\Index;

use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Store\Model\ScopeInterface;

class ValidateCustomerInformation
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var ResponseHttp
     */
    private $responseHttp;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Riki\Customer\Helper\Data
     */
    private $customerHelper;

    /**
     * ValidateCustomerInformation constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param ResponseHttp $responseHttp
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $url
     * @param \Riki\Customer\Helper\Data $customerHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        ResponseHttp $responseHttp,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $url,
        \Riki\Customer\Helper\Data $customerHelper
    ) {
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->responseHttp = $responseHttp;
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @return mixed|null
     */
    protected function getRedirectUrl()
    {
        if ($this->customerSession->isLoggedIn()
            && $this->customerSession->getHasMissingInformation()
        ) {
            $this->customerSession->unsHasMissingInformation();

            $redirectUrl = $this->customerHelper->getKssEditAccountUrl(
                $this->customerSession->getCustomer()
            );

            if ($redirectUrl) {
                $this->customerSession->setHandleEditAccountInformation(true);

                $this->responseHttp->setNoCacheHeaders();
                return $redirectUrl;
            }
        }

        return null;
    }

    /**
     * @param $urlPath
     * @return mixed
     */
    protected function getConfigUrl($urlPath)
    {
        return $this->scopeConfig->getValue(
            'customerksslink/kss_link_edit_customer/' . $urlPath,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
