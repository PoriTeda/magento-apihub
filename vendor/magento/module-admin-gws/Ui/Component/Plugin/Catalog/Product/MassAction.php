<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Ui\Component\Plugin\Catalog\Product;

use Magento\AdminGws\Model\Role;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin for \Magento\Catalog\Ui\Component\Product\MassAction
 */
class MassAction
{
    /**
     * @var Role
     */
    private $role;

    /**
     * Request object
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Role $role
     * @param RequestInterface $request
     */
    public function __construct(
        Role $role,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Catalog\Ui\Component\Product\MassAction $massAction
     * @param bool $isActionAllowed
     * @param string $actionType
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsActionAllowed(
        \Magento\Catalog\Ui\Component\Product\MassAction $massAction,
        $isActionAllowed,
        $actionType
    ) {
        if ($isActionAllowed
            && in_array($actionType, ['status', 'attributes', 'delete'])
            && !$this->role->getIsAll()
        ) {
            $storeCode = $this->request->getParam('store');
            $storeId = $storeCode ? $this->storeManager->getStore(
                $storeCode
            )->getId() : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            if (!$this->role->hasStoreAccess($storeId)) {
                $isActionAllowed = false;
            }
        }
        return $isActionAllowed;
    }
}
