<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\App\Backend;

use Magento\CatalogPermissions\App\ConfigInterface;

/**
 * Global configs
 */
class Config implements ConfigInterface
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $coreConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->coreConfig = $scopeConfig;
    }

    /**
     * Check, whether permissions are enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->coreConfig->isSetFlag(ConfigInterface::XML_PATH_ENABLED, 'default');
    }

    /**
     * Return category browsing mode
     *
     * @return string
     */
    public function getCatalogCategoryViewMode()
    {
        return $this->coreConfig->getValue(ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW, 'default');
    }

    /**
     * Return category browsing groups
     *
     * @return string[]
     */
    public function getCatalogCategoryViewGroups()
    {
        $groups = $this->coreConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
            'default'
        );
        return is_string($groups) && $groups !== '' ? explode(',', $groups) : [];
    }

    /**
     * Return display products mode
     *
     * @return string
     */
    public function getCatalogProductPriceMode()
    {
        return $this->coreConfig->getValue(ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE, 'default');
    }

    /**
     * Return display products groups
     *
     * @return string[]
     */
    public function getCatalogProductPriceGroups()
    {
        $groups = $this->coreConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
            'default'
        );
        return $groups ? explode(',', $groups) : [];
    }

    /**
     * Return adding to cart mode
     *
     * @return string
     */
    public function getCheckoutItemsMode()
    {
        return $this->coreConfig->getValue(ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS, 'default');
    }

    /**
     * Return adding to cart groups
     *
     * @return string[]
     */
    public function getCheckoutItemsGroups()
    {
        $groups = $this->coreConfig->getValue(ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups', 'default');
        return $groups ? explode(',', $groups) : [];
    }

    /**
     * Return catalog search prohibited groups
     *
     * @return string[]
     */
    public function getCatalogSearchDenyGroups()
    {
        $groups = $this->coreConfig->getValue(ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH, 'default');
        return $groups !== null ? explode(',', $groups) : [];
    }

    /**
     * Return restricted landing page
     *
     * @return string
     */
    public function getRestrictedLandingPage()
    {
        return $this->coreConfig->getValue(ConfigInterface::XML_PATH_LANDING_PAGE, 'default');
    }
}
