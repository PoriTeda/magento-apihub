<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\App;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $method
     * @param string $configMethod
     * @param string $path
     * @param string|string[]|bool $value
     * @param string|bool $configValue
     * @dataProvider dataProviderMethods
     */
    public function testMethods($method, $configMethod, $path, $value, $configValue)
    {
        $scopeConfigMock = $this->getMockForAbstractClass(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $scopeConfigMock->expects(
            $this->once()
        )->method(
            $configMethod
        )->with(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            null
        )->will(
            $this->returnValue($configValue)
        );
        $model = new \Magento\CatalogPermissions\App\Config($scopeConfigMock);
        $this->assertEquals($value, $model->{$method}());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderMethods()
    {
        return [
            [
                'isEnabled',
                'isSetFlag',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_ENABLED,
                true,
                true,
            ],
            [
                'isEnabled',
                'isSetFlag',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_ENABLED,
                false,
                false
            ],
            [
                'getCatalogCategoryViewMode',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW,
                'value',
                'value'
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                [],
                ''
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                ['0'],
                '0'
            ],
            [
                'getCatalogProductPriceMode',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE,
                'value',
                'value'
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                [],
                ''
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCheckoutItemsMode',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS,
                'value',
                'value'
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                [],
                ''
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                [],
                null
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getRestrictedLandingPage',
                'getValue',
                \Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_LANDING_PAGE,
                'value',
                'value'
            ]
        ];
    }
}
