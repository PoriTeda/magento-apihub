<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $model;

    /**
     * Set up instances and mock objects
     */
    protected function setUp()
    {
        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $scopeValueMap = [
            [\Magento\VisualMerchandiser\Model\Rules::XML_PATH_AVAILABLE_ATTRIBUTES, null, 'xxx'],
            [\Magento\VisualMerchandiser\Model\Config\Source\InsertMode::XML_PATH_INSERT_MODE, null, 'xxx']
        ];
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap($scopeValueMap));

        $this->attribute = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attribute
            ->expects($this->any())
            ->method('loadByCode')
            ->willReturn($this->attribute);

        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductsPosition'])
            ->getMock();

        $this->category
            ->expects($this->any())
            ->method('getProductsPosition')
            ->will($this->returnValue([]));

        $this->collection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection
            ->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([])));

        $resource = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\AbstractResource::class)
            ->setMethods(['getIdFieldName', 'load'])
            ->getMockForAbstractClass();

        $resource->expects($this->any())
            ->method('getIdFieldName')
            ->will($this->returnValue('id'));

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = (new ObjectManager($this))->getObject(
            \Magento\VisualMerchandiser\Model\Rules::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'attribute' => $this->attribute,
                'resource' => $resource,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Tests the method getAvailableAttributes
     */
    public function testGetAvailableAttributes()
    {
        $this->assertInternalType('array', $this->model->getAvailableAttributes());
    }

    /**
     * Tests the method getConditions
     */
    public function testGetConditions()
    {
        $this->assertInternalType('array', $this->model->getAvailableAttributes());
    }

    /**
     * Tests the method loadByCategory
     */
    public function testLoadByCategory()
    {
        $this->assertEquals(
            $this->model,
            $this->model->loadByCategory($this->category)
        );
    }

    /**
     * Tests the method applyAllRules
     */
    public function testApplyAllRules()
    {
        $this->assertInternalType(
            'null',
            $this->model->applyAllRules(
                $this->category,
                $this->collection
            )
        );
    }

    /**
     * Tests the method applyConditions
     */
    public function testApplyConditions()
    {
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('visualmerchandiser/options/insert_mode');

        $this->assertInternalType(
            'null',
            $this->model->applyConditions(
                $this->category,
                $this->collection,
                []
            )
        );
    }
}
