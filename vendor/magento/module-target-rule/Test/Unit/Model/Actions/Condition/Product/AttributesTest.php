<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\TargetRule\Model\Index;
use Magento\Framework\DB\Select;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Actions\Condition\Product\Attributes
     */
    protected $attributes;

    /**
     * Object manager helper
     *
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * Context mock
     *
     * @var \Magento\Rule\Model\Condition\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * Backend helper mock
     *
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendHelperMock;

    /**
     * Config mock
     *
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * Product mock
     *
     * @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * Product resource model mock
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceProductMock;

    /**
     * Attribute set collection mock
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionMock;

    /**
     * Locale format mock
     *
     * @var \Magento\Framework\Locale\FormatInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatInterfaceMock;

    /**
     * Editable block mock
     *
     * @var \Magento\Rule\Block\Editable|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $editableMock;

    /**
     * Product Type mock
     *
     * @var \Magento\Catalog\Model\Product\Type|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeMock;

    /**
     * Index mock
     *
     * @var Index|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexMock;

    /**
     * EAV Attribute mock
     *
     * @var Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectMock;

    /**
     * @var SqlBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sqlBuilderMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(\Magento\Backend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock =
            $this->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatInterfaceMock = $this->getMockBuilder(\Magento\Framework\Locale\FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->editableMock = $this->getMockBuilder(\Magento\Rule\Block\Editable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceProductMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceProductMock->expects($this->any())->method('loadAllAttributes')->will($this->returnSelf());
        $this->resourceProductMock->expects($this->any())->method('getAttributesByCode')->will($this->returnSelf());
        $this->indexMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getTable',
                'bindArrayOfIds',
                'getOperatorCondition',
                'getOperatorBindCondition',
                'getResource',
                'select',
                'getConnection',
                'getStoreId'
            ])
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'assemble', 'where', 'joinLeft'])
            ->getMock();
        $this->sqlBuilderMock = $this->getMockBuilder(SqlBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateWhereClause'])
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->attributes = $this->objectManagerHelper->getObject(
            \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::class,
            [
                'context' => $this->contextMock,
                'backendData' => $this->backendHelperMock,
                'config' => $this->configMock,
                'product' => $this->productMock,
                'productResource' => $this->resourceProductMock,
                'attrSetCollection' => $this->collectionMock,
                'localeFormat' => $this->formatInterfaceMock,
                'editable' => $this->editableMock,
                'type' => $this->typeMock,
                [],
                'sqlBuilder' => $this->sqlBuilderMock
            ]
        );
    }

    /**
     * Test get conditions for collection
     *
     * @return void
     */
    public function testGetConditionForCollection()
    {
        $collection = null;
        $bind = [];
        $expectedWhereClause = 'generated where clause';
        $storeId = 1;

        $this->indexMock->expects($this->any())->method('select')->will($this->returnValue($this->selectMock));
        $this->indexMock->expects($this->any())->method('getStoreId')->will($this->returnValue($storeId));
        $this->sqlBuilderMock->expects($this->once())
            ->method('generateWhereClause')
            ->with($this->attributes, $bind, $storeId, $this->selectMock)
            ->willReturn($expectedWhereClause);

        $result = $this->attributes->getConditionForCollection($collection, $this->indexMock, $bind);
        $this->assertEquals($expectedWhereClause, $result);
        $this->assertEquals($bind, []);
    }
}
