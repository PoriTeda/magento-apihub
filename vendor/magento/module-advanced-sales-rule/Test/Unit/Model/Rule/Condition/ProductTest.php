<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedSalesRule\Model\Rule\Condition\Product;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory;

/**
 * Class ProductTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\Product
     */
    protected $model;

    /**
     * @var \Magento\Rule\Model\Condition\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendData;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Magento\Catalog\Model\ProductFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productResource;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrSetCollection;

    /**
     * @var \Magento\Framework\Locale\FormatInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeFormat;

    /**
     * @var Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $concreteConditionFactory;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\Rule\Model\Condition\Context::class;
        $this->context = $this->createMock($className);

        $className = \Magento\Backend\Helper\Data::class;
        $this->backendData = $this->createMock($className);

        $className = \Magento\Eav\Model\Config::class;
        $this->config = $this->createMock($className);

        $className = \Magento\Catalog\Model\ProductFactory::class;
        $this->productFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\Catalog\Api\ProductRepositoryInterface::class;
        $this->productRepository = $this->createMock($className);

        $className = \Magento\Catalog\Model\ResourceModel\Product::class;
        $this->productResource = $this->createMock($className);

        $className = \Magento\Eav\Model\Entity\AbstractEntity::class;
        $abstractEntity = $this->createMock($className);

        $this->productResource->expects($this->any())
              ->method('loadAllAttributes')
              ->willReturn($abstractEntity);

        $abstractEntity->expects($this->any())
            ->method('getAttributesByCode')
            ->willReturn([]);

        $className = \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class;
        $this->attrSetCollection = $this->createMock($className);

        $className = \Magento\Framework\Locale\FormatInterface::class;
        $this->localeFormat = $this->createMock($className);

        $className = \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory::class;
        $this->concreteConditionFactory = $this->createPartialMock($className, ['create']);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\Product::class,
            [
                'context' => $this->context,
                'backendData' => $this->backendData,
                'config' => $this->config,
                'productFactory' => $this->productFactory,
                'productRepository' => $this->productRepository,
                'productResource' => $this->productResource,
                'attrSetCollection' => $this->attrSetCollection,
                'localeFormat' => $this->localeFormat,
                'concreteConditionFactory' => $this->concreteConditionFactory,
                'data' => [],
            ]
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class;
        $filterGroupInterface =$this->createMock($className);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
