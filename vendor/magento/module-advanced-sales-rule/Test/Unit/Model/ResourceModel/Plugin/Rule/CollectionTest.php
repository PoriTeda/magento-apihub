<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\ResourceModel\Plugin\Rule;

use Magento\AdvancedRule\Model\Condition\Filter as FilterModel;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as FilterResource;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorFactory;

/**
 * Class CollectionTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\ResourceModel\Plugin\Rule\Collection
     */
    protected $model;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleCollectionMock;

    /**
     * @var \Closure
     */
    protected $closureMock;

    /**
     * @var \Magento\Quote\Model\Quote\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    /**
     * @var FilterTextGeneratorFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterTextGeneratorFactoryMock;

    /**
     * @var FilterResource|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterResourceMock;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->closureMock = function () {
            return $this->ruleCollectionMock;
        };

        $this->ruleCollectionMock = $this->getMockBuilder(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterResourceMock = $this->getMockBuilder(
            \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter::class
        )->disableOriginalConstructor()
            ->getMock();

        $filterResourceFactoryMock = $this->getMockBuilder(
            \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\FilterFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $filterResourceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->filterResourceMock);

        $this->filterTextGeneratorFactoryMock = $this->getMockBuilder(
            \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorFactory::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\ResourceModel\Plugin\Rule\Collection::class,
            [
                'filterResourceFactory' => $filterResourceFactoryMock,
                'filterTextGeneratorFactory' => $this->filterTextGeneratorFactoryMock,
            ]
        );
    }

    public function testAroundSetValidationFilter()
    {
        $websiteId = 1;
        $customerGroupId = 2;
        $filteredRuleIds = [1, 2];

        $connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectRendererMock = $this->getMockBuilder(\Magento\Framework\DB\Select\SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject $selectMock */
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->setConstructorArgs([$connectionMock, $selectRendererMock])
            ->getMock();

        $this->ruleCollectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->ruleCollectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);

        $connectionMock
            ->expects($this->any())
            ->method('quoteInto')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $this->setupFilterResourceMock($this->addressMock, $filteredRuleIds);

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            null,
            null,
            $this->addressMock
        );
        $this->assertNotNull($result);
    }

    /**
     * Setup method
     * @param $addressMock
     * @param $filteredRuleIds
     */
    private function setupFilterResourceMock($addressMock, $filteredRuleIds)
    {
        $filterTextGeneratorClass1 =
            \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute::class;
        $filterTextGeneratorData1 = ['attribute' => 'sku'];
        $filterTextGeneratorClass2 =
            \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category::class;
        $filterTextGeneratorData2 = [];

        $filterText1 = ['product:attribute:sku:123'];
        $filterText2 = ['product:attribute:sku:123', 'product:category:4'];

        $expectedFilterArray = ['true', 'product:attribute:sku:123', 'product:attribute:sku:123', 'product:category:4'];
        $expectedFilterArray = array_unique($expectedFilterArray);

        $filterTextGenerators = [
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS => $filterTextGeneratorClass1,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode($filterTextGeneratorData1),
            ],
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS => $filterTextGeneratorClass2,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode($filterTextGeneratorData2),
            ],
        ];

        $this->filterResourceMock->expects($this->once())
            ->method('getFilterTextGenerators')
            ->willReturn($filterTextGenerators);

        $filterTextGenerator1 = $this->createMock(
            \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface::class
        );
        $filterTextGenerator1->expects($this->once())
            ->method('generateFilterText')
            ->with($addressMock)
            ->willReturn($filterText1);

        $filterTextGenerator2 = $this->createMock(
            \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface::class
        );
        $filterTextGenerator2->expects($this->once())
            ->method('generateFilterText')
            ->with($addressMock)
            ->willReturn($filterText2);

        $this->filterTextGeneratorFactoryMock->expects($this->at(0))
            ->method('create')
            ->with(
                $filterTextGeneratorClass1,
                ['data' => $filterTextGeneratorData1]
            )->willReturn($filterTextGenerator1);
        $this->filterTextGeneratorFactoryMock->expects($this->at(1))
            ->method('create')
            ->with(
                $filterTextGeneratorClass2,
                ['data' => $filterTextGeneratorData2]
            )->willReturn($filterTextGenerator2);

        $this->filterResourceMock->expects($this->once())
            ->method('filterRules')
            ->with(
                $expectedFilterArray
            )->willReturn($filteredRuleIds);
    }

    public function testAroundSetValidationFilterWithCoupon()
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            'couponCode',
            null,
            $this->addressMock
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }

    public function testAroundSetValidationFilterWithoutQuoteAddress()
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            null,
            null,
            null
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }

    public function testAroundSetValidationFilterSkipValidation()
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->addressMock->expects($this->once())
            ->method('getData')
            ->with('skip_validation_filter')
            ->willReturn(true);

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            null,
            null,
            $this->addressMock
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }
}
