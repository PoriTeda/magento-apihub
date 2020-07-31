<?php

namespace Riki\Theme\ViewModel\Header;

class Navigation implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Customer\Model\Url
     */
    private $customerUrl;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    private $ssoConfig;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    private $modelDeliveryDate;

    public function __construct(
        \Magento\Customer\Model\Url $customerUrl,
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate
    ) {
        $this->customerUrl = $customerUrl;
        $this->ssoConfig = $ssoConfig;
        $this->modelDeliveryDate = $modelDeliveryDate;
    }

    public function getUserUrl()
    {
        return $this->customerUrl;
    }

    /**
     * @return \Magento\Customer\Model\Url
     */
    public function getCustomerUrl(): \Magento\Customer\Model\Url
    {
        return $this->customerUrl;
    }

    public function getAllTimeSlot()
    {
        return $this->modelDeliveryDate->getListTimeSlot();
    }
}