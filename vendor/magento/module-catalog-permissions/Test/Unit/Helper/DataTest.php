<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Helper\Data
     */
    protected $model;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
    {
        $this->sessionMock = $this->createPartialMock(
            \Magento\Customer\Model\Session::class,
            ['__wakeup', 'getCustomerGroupId']
        );

        $this->configMock = $this->getMockForAbstractClass(
            \Magento\CatalogPermissions\App\ConfigInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->urlBuilderMock = $this->getMockForAbstractClass(
            \Magento\Framework\UrlInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\CatalogPermissions\Helper\Data::class,
            [
                'config' => $this->configMock,
                'customerSession' => $this->sessionMock,
                'urlBuilder' => $this->urlBuilderMock
            ]
        );
    }

    /**
     * @param string $method
     * @param string $modeMethod
     * @param string $groupsMethod
     * @param string $mode
     * @param string[] $groups
     * @param int|null $customerGroupId
     * @param bool $result
     * @dataProvider dataProviderIsGrantMethods
     */
    public function testIsGrantMethods($method, $modeMethod, $groupsMethod, $mode, $groups, $customerGroupId, $result)
    {
        $this->configMock->expects($this->once())->method($modeMethod)->with('store')->will($this->returnValue($mode));
        $this->configMock->expects(
            $this->once()
        )->method(
            $groupsMethod
        )->with(
            'store'
        )->will(
            $this->returnValue($groups)
        );
        $this->sessionMock->expects(
            $this->any()
        )->method(
            'getCustomerGroupId'
        )->will(
            $this->returnValue($customerGroupId)
        );
        $this->assertEquals($result, $this->model->{$method}('store', $customerGroupId));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderIsGrantMethods()
    {
        return [
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_NONE,
                [],
                1,
                false,
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_ALL,
                [],
                2,
                true
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                3,
                false
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                0,
                false
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                1,
                true
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_NONE,
                [],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_ALL,
                [],
                null,
                true
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                1,
                true
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_NONE,
                ['1', '2'],
                1,
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_ALL,
                ['1'],
                1,
                true
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                null,
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                '0',
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                \Magento\CatalogPermissions\App\ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                '1',
                true
            ]
        ];
    }

    /**
     * @param string[] $groups
     * @param int|null $customerGroupId
     * @param bool $result
     * @dataProvider dataProviderIsAllowedCatalogSearch
     */
    public function testIsAllowedCatalogSearch($groups, $customerGroupId, $result)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getCatalogSearchDenyGroups'
        )->will(
            $this->returnValue($groups)
        );
        $this->sessionMock->expects(
            $this->any()
        )->method(
            'getCustomerGroupId'
        )->will(
            $this->returnValue($customerGroupId)
        );
        $this->assertEquals($result, $this->model->isAllowedCatalogSearch());
    }

    /**
     * @return array
     */
    public function dataProviderIsAllowedCatalogSearch()
    {
        return [
            [[], 1, true],
            [[], null, true],
            [['1', '2'], null, true],
            [['1', '2'], 3, true],
            [['1', '2'], 1, false]
        ];
    }

    public function testGetLandingPageUrl()
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getRestrictedLandingPage'
        )->will(
            $this->returnValue('some uri')
        );
        $this->urlBuilderMock->expects(
            $this->once()
        )->method(
            'getUrl'
        )->with(
            '',
            ['_direct' => 'some uri']
        )->will(
            $this->returnValue('some url')
        );
        $this->assertEquals('some url', $this->model->getLandingPageUrl());
    }
}
