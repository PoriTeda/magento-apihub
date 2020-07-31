<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Plugin\Theme\Block\Html;

class TopmenuTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Plugin\Theme\Block\Html\Topmenu
     */
    private $topmenuPlugin;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionsConfigMock;

    /**
     * @var \Magento\Customer\Model\Session\Storage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionStorageMock;

    /**
     * @var \Magento\Theme\Block\Html\Topmenu|\PHPUnit_Framework_MockObject_MockObject
     */
    private $topmenuMock;

    /**
     * @var array
     */
    private $baseResult = [
        'key' => 'value',
        'another key' => 'another value'
    ];

    protected function setUp()
    {
        $this->catalogPermissionsConfigMock = $this->getMockForAbstractClass(
            \Magento\CatalogPermissions\App\ConfigInterface::class
        );
        $this->customerSessionStorageMock = $this->getMockBuilder(\Magento\Customer\Model\Session\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMock();
        $this->topmenuMock = $this->getMockBuilder(\Magento\Theme\Block\Html\Topmenu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->topmenuPlugin = new \Magento\CatalogPermissions\Plugin\Theme\Block\Html\Topmenu(
            $this->catalogPermissionsConfigMock,
            $this->customerSessionStorageMock
        );
    }

    /**
     * @param bool $catalogPermissionsEnabled
     * @param int $getCustomerGroupIdCallCount
     * @param array $expectedResult
     *
     * @dataProvider afterGetCacheKeyInfoDataProvider
     */
    public function testAfterGetCacheKeyInfo(
        bool $catalogPermissionsEnabled,
        int $getCustomerGroupIdCallCount,
        array $expectedResult
    ) {
        $this->catalogPermissionsConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($catalogPermissionsEnabled);
        $this->customerSessionStorageMock->expects($this->exactly($getCustomerGroupIdCallCount))
            ->method('getCustomerGroupId')
            ->willReturn('customerGroupId');

        $cacheKeyInfo = $this->topmenuPlugin->afterGetCacheKeyInfo($this->topmenuMock, $this->baseResult);
        $this->assertEquals($cacheKeyInfo, $expectedResult);
    }

    public function afterGetCacheKeyInfoDataProvider()
    {
        return [
            'Catalog Permissions Enabled' => [
                true,
                1,
                array_merge($this->baseResult, ['customer_group_id' => 'customerGroupId'])
            ],
            'Catalog Permissions Disabled' => [
                false,
                0,
                $this->baseResult
            ]
        ];
    }
}
