<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class GiftcardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Block\Adminhtml\Catalog\Product\Edit\Tab\Giftcard
     */
    protected $block;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfig;

    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->setMethods(['isSingleStoreMode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            \Magento\GiftCard\Block\Adminhtml\Catalog\Product\Edit\Tab\Giftcard::class,
            [
                'storeManager' => $this->storeManager,
                'coreRegistry' => $this->coreRegistry,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * @dataProvider getScopeValueDataProvider
     * @param boolean $isSingleStore
     * @param string $scope
     * @param string $expectedResult
     */
    public function testGetScopeValue($isSingleStore, $scope, $expectedResult)
    {
        $this->storeManager->expects($this->any())
            ->method('isSingleStoreMode')
            ->will($this->returnValue($isSingleStore));

        $this->assertEquals($this->block->getScopeValue($scope), $expectedResult);
    }

    /**
     * @return array
     */
    public function getScopeValueDataProvider()
    {
        return [[true, 'test', ''], [false, 'test', 'value-scope="test"']];
    }

    /**
     * @param $prodId
     * @param $result
     * @dataProvider isNewDataProvider
     */
    public function testIsNew($prodId, $result)
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(['getId', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($product));

        $product->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($prodId));

        $this->assertEquals($result, $this->block->isNew());
    }

    /**
     * @return array
     */
    public function isNewDataProvider()
    {
        return [
            ['product_id', false],
            [null, true]
        ];
    }

    public function testGetFieldValueForNewProduct()
    {
        $field = 'some_field';

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(['getId', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($product));
        $product->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('giftcard/general/' . $field, 'store')
            ->will($this->returnValue('config_value'));

        $this->assertEquals('config_value', $this->block->getFieldValue($field));
    }

    public function testGetFieldValueForExistingProduct()
    {
        $field = 'some_field';

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(['getId', 'getDataUsingMethod', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->exactly(2))
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($product));
        $product->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('product_id'));
        $product->expects($this->once())
            ->method('getDataUsingMethod')
            ->with($field)
            ->will($this->returnValue('using_method'));

        $this->assertEquals('using_method', $this->block->getFieldValue($field));
    }

    public function testGetCardTypes()
    {
        $expected = ['Virtual', 'Physical', 'Combined'];

        $this->assertEquals($expected, $this->block->getCardTypes());
    }
}
