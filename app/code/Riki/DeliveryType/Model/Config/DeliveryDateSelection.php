<?php
namespace Riki\DeliveryType\Model\Config;

class DeliveryDateSelection
{
    const XML_PATH_DELIVERY_DATE_SELECTION_CONFIG_IS_DISABLED_CHECKOUT =
        'deliverydate/delivery_date_selection/is_disabled_checkout';

    const XML_PATH_DELIVERY_DATE_SELECTION_CONFIG_IS_DISABLED_CREATE_ORDER_API =
        'deliverydate/delivery_date_selection/is_disabled_create_order_api';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * DeliveryDateSelection constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $state
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
    }

    /**
     * Only apply for Frontend and Watson API
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDisableChangeDeliveryDateConfig()
    {
        if ($this->state->getAreaCode() === 'adminhtml') {
            return false;
        }

        if ($this->state->getAreaCode() !== 'frontend') {
            $path = self::XML_PATH_DELIVERY_DATE_SELECTION_CONFIG_IS_DISABLED_CREATE_ORDER_API;
        } else {
            $path = self::XML_PATH_DELIVERY_DATE_SELECTION_CONFIG_IS_DISABLED_CHECKOUT;
        }

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }
}
