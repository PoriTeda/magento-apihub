<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\Rule;

/**
 * Unit test for \Magento\TargetRule\Helper\Data
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Helper\Data
     */
    protected $data;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock
            ->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->data = new Data(
            $this->contextMock
        );
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getMaximumNumberOfProductDataProvider
     */
    public function testGetMaximumNumberOfProduct($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getMaximumNumberOfProduct($type)
        );
    }

    /**
     * @return array
     */
    public function getMaximumNumberOfProductDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_position_limit', 2],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_limit', 4],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_limit', 8]
        ];
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function testGetMaximumNumberOfProductException()
    {
        $this->data->getMaximumNumberOfProduct(-123);
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getShowProductsDataProvider
     */
    public function testGetShowProducts($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getShowProducts($type)
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function testGetShowProductsException()
    {
        $this->data->getShowProducts(-123);
    }

    /**
     * @return array
     */
    public function getShowProductsDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_position_behavior', 1],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_behavior', 3],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_behavior', 7]
        ];
    }

    /**
     * @return void
     */
    public function testGetMaxProductsListResult()
    {
        $this->assertEquals(Data::MAX_PRODUCT_LIST_RESULT, $this->data->getMaxProductsListResult(100500));
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getRotationModeDataProvider
     */
    public function testGetRotationMode($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getRotationMode($type)
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function testGetRotationModeException()
    {
        $this->data->getRotationMode(-12345);
    }

    /**
     * @return array
     */
    public function getRotationModeDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_rotation_mode', 3],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_rotation_mode', 5],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_rotation_mode', 9]
        ];
    }
}
