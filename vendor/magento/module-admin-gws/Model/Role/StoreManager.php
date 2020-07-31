<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Role;

use Magento\AdminGws\Model\Role;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreManager implements StoreManagerInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Role
     */
    private $role;

    /**
     * StoreManager constructor.
     * @param StoreManagerInterface $storeManager
     * @param Role $role
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Role $role
    ) {
        $this->storeManager = $storeManager;
        $this->role = $role;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsSingleStoreModeAllowed($value) : void
    {
        $this->storeManager->setIsSingleStoreModeAllowed($value);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSingleStore() : bool
    {
        return $this->storeManager->hasSingleStore();
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleStoreMode() : bool
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * {@inheritdoc}
     */
    public function getStore($storeId = null) : StoreInterface
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStores($withDefault = false, $codeKey = false) : array
    {

        $stores = $this->storeManager->getStores($withDefault, $codeKey);
        if ($this->role->getIsAll()) {
            return $stores;
        }

        $roleStoreIds = $this->role->getStoreIds();
        foreach ($stores as $key => $store) {
            if (!in_array($store->getId(), $roleStoreIds)) {
                unset($stores[$key]);
            }
        }
        return $stores;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite($websiteId = null) : WebsiteInterface
    {
        return $this->storeManager->getWebsite($websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsites($withDefault = false, $codeKey = false) : array
    {
        $websites = $this->storeManager->getWebsites($withDefault, $codeKey);
        if ($this->role->getIsAll()) {
            return $websites;
        }

        $roleRelevantWebsiteIds = $this->role->getRelevantWebsiteIds();
        foreach ($websites as $key => $website) {
            if (!in_array($website->getId(), $roleRelevantWebsiteIds)) {
                unset($websites[$key]);
            }
        }
        return $websites;
    }

    /**
     * {@inheritdoc}
     */
    public function reinitStores() : void
    {
        $this->storeManager->reinitStores();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultStoreView()
    {
        $defaultStore = $this->storeManager->getDefaultStoreView();
        if (null === $defaultStore) {
            return null;
        }

        $roleStoreIds = $this->role->getStoreIds();
        return in_array($defaultStore->getId(), $roleStoreIds) ? $defaultStore : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($groupId = null) : GroupInterface
    {
        return $this->storeManager->getGroup($groupId);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups($withDefault = false) : array
    {
        $groups = $this->storeManager->getGroups($withDefault);
        if ($this->role->getIsAll()) {
            return $groups;
        }

        $roleGroupIds = $this->role->getStoreGroupIds();
        foreach ($groups as $key => $group) {
            if (!in_array($group->getId(), $roleGroupIds)) {
                unset($groups[$key]);
            }
        }
        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentStore($store) : void
    {
        $this->storeManager->setCurrentStore($store);
    }
}
