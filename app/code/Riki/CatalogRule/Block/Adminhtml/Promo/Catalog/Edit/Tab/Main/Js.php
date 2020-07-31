<?php
namespace Riki\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab\Main;

class Js extends \Magento\Framework\View\Element\Template
{
    /**
     * Get apply spot only option
     *
     * @return int
     */
    public function getSpotOnlyOption()
    {
        return \Riki\CatalogRule\Model\Rule::APPLY_SPOT_ONLY;
    }

    /**
     * Get apply subscription only option
     *
     * @return int
     */
    public function getSubscriptionOnlyOption()
    {
        return \Riki\CatalogRule\Model\Rule::APPLY_SUBSCRIPTION_ONLY;
    }

    /**
     * Get apply spot subscription option
     *
     * @return int
     */
    public function getSpotSubscriptionOption()
    {
        return \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION;
    }

    /**
     * Get apply all delivery option
     *
     * @return int
     */
    public function getAllDeliveriesOption()
    {
        return \Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL;
    }
}
