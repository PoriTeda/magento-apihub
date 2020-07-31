<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckRestrictWebsiteOnValidateSession implements ObserverInterface
{
    /**
     * @var \Riki\Customer\Model\WebsiteRestrictionValidator
     */
    protected $websiteRestrictionValidator;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Encryption\UrlCoder
     */
    protected $urlCoder;

    /**
     * CheckRestrictWebsiteOnValidateSession constructor.
     *
     * @param \Riki\Customer\Model\WebsiteRestrictionValidator $websiteRestrictionValidator
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Encryption\UrlCoder $urlCoder
     */
    public function __construct(
        \Riki\Customer\Model\WebsiteRestrictionValidator $websiteRestrictionValidator,
        \Magento\Framework\UrlInterface $url,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Encryption\UrlCoder $urlCoder
    )
    {
        $this->websiteRestrictionValidator = $websiteRestrictionValidator;
        $this->url = $url;
        $this->customerSession = $customerSession;
        $this->urlCoder = $urlCoder;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getData('result');

        if ($result->getData('status') && !$this->websiteRestrictionValidator->validate()) {
            $result->setData('status', false);
            $result->setData('cleanStorage', true);

            $redirectUrl = $this->url->getUrl('customer/account', ['_scope' => 'ec']);
            if (!$this->customerSession->isLoggedIn()) {
                $redirectUrl = $this->url->getUrl('customer/account/login', [
                    \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlCoder->encode(
                        $observer->getEvent()->getData('current_url')
                    )
                ]);
            }

            $result->setData('redirectUrl', $redirectUrl);
        }
    }
}
