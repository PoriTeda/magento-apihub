<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;

class ApplyProductPermissionOnCollectionAfterLoadObserver implements ObserverInterface
{
    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * @var ApplyPermissionsOnProduct
     */
    protected $applyPermissionsOnProduct;

    /**
     * @var Index
     */
    private $permissionIndex;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param ApplyPermissionsOnProduct $applyPermissionsOnProduct
     * @param Index $permissionIndex
     * @param Session $customerSession
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        ApplyPermissionsOnProduct $applyPermissionsOnProduct,
        Index $permissionIndex,
        Session $customerSession
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->applyPermissionsOnProduct = $applyPermissionsOnProduct;
        $this->permissionIndex = $permissionIndex;
        $this->customerSession = $customerSession;
    }

    /**
     * Apply category permissions for collection on after load
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $collection = $observer->getEvent()->getCollection();
        foreach ($collection as $product) {
            if ($collection->hasFlag('product_children')) {
                $product->addData(
                    [
                        'grant_catalog_category_view' => -1,
                        'grant_catalog_product_price' => -1,
                        'grant_checkout_items' => -1
                    ]
                );
            } else {
                $this->permissionIndex->addIndexToProduct($product, $this->customerSession->getCustomerGroupId());
            }
            $this->applyPermissionsOnProduct->execute($product);
        }
        return $this;
    }
}
