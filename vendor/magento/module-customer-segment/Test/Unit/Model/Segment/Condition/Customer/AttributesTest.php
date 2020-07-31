<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use Magento\CustomerSegment\Model\ResourceModel\Segment as ResourceSegment;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\LayoutInterface;
use Magento\Rule\Model\Condition\Context;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Attributes */
    protected $model;

    /** @var string  */
    protected $attributeBackendTable = 'backend_table';

    /** @var int  */
    protected $attributeId = 1;

    /** @var string  */
    protected $attributeFrontendLabel = 'frontend_label';

    /** @var  Context |\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var  ResourceSegment |\PHPUnit_Framework_MockObject_MockObject */
    protected $resourceSegment;

    /** @var  ResourceCustomer |\PHPUnit_Framework_MockObject_MockObject */
    protected $resourceCustomer;

    /** @var  EavConfig |\PHPUnit_Framework_MockObject_MockObject */
    protected $eavConfig;

    /** @var  AssetRepository |\PHPUnit_Framework_MockObject_MockObject */
    protected $assetRepository;

    /** @var  TimezoneInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $localeDate;

    /** @var  LayoutInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $layout;

    /** @var  Attribute |\PHPUnit_Framework_MockObject_MockObject */
    protected $attribute;

    /** @var  Customer |\PHPUnit_Framework_MockObject_MockObject */
    protected $customer;

    /** @var  Select |\PHPUnit_Framework_MockObject_MockObject */
    protected $select;

    /** @var  AdapterInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $connectionMock;

    protected function setUp()
    {
        $this->prepareContext();
        $this->prepareResourceSegment();
        $this->prepareResourceCustomer();

        $this->eavConfig = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Attributes(
            $this->context,
            $this->resourceSegment,
            $this->resourceCustomer,
            $this->eavConfig
        );
    }

    protected function prepareContext()
    {
        $this->assetRepository = $this->getMockBuilder(\Magento\Framework\View\Asset\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->getMockForAbstractClass();

        $this->layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAssetRepository',
                'getLocaleDate',
                'getLayout',
            ])
            ->getMock();
        $this->context->expects($this->any())
            ->method('getAssetRepository')
            ->willReturn($this->assetRepository);
        $this->context->expects($this->any())
            ->method('getLocaleDate')
            ->willReturn($this->localeDate);
        $this->context->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layout);
    }

    protected function prepareResourceSegment()
    {
        $this->select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'from',
                'where',
                'limit',
                'reset',
                'columns',
                'join',
                'getConnection',
            ])
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->getMockForAbstractClass();

        $this->resourceSegment = $this->getMockBuilder(\Magento\CustomerSegment\Model\ResourceModel\Segment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->select);

        $this->resourceSegment->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
    }

    protected function prepareResourceCustomer()
    {
        $isUsedForCustomerSegment = true;

        $this->attribute = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getFrontendLabel',
                'getFrontendInput',
                'getIsUsedForCustomerSegment',
                'getAttributeCode',
                'getBackendTable',
                'isStatic',
                'getId',
                'getEntity',
            ])
            ->getMock();

        $this->attribute->expects($this->any())
            ->method('getFrontendLabel')
            ->willReturn($this->attributeFrontendLabel);
        $this->attribute->expects($this->any())
            ->method('getIsUsedForCustomerSegment')
            ->willReturn($isUsedForCustomerSegment);
        $this->attribute->expects($this->any())
            ->method('getBackendTable')
            ->willReturn($this->attributeBackendTable);
        $this->attribute->expects($this->any())
            ->method('getId')
            ->willReturn($this->attributeId);

        $this->resourceCustomer = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceCustomer->expects($this->any())
            ->method('loadAllAttributes')
            ->willReturnSelf();
        $this->resourceCustomer->expects($this->any())
            ->method('getAttributesByCode')
            ->willReturn([$this->attribute]);
    }

    public function testGetMatchedEvents()
    {
        $this->eavConfig->expects($this->once())
            ->method('getAttribute')
            ->willReturn($this->attribute);
        $this->attribute->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('some_attribute_code');

        $this->assertEquals(['customer_save_commit_after'], $this->model->getMatchedEvents());
    }

    public function testGetNewChildSelectOptions()
    {
        $attributeCode = 'default_billing';

        $this->attribute->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);

        $expected = [
            [
                'value' => get_class($this->model) . '|' . $attributeCode,
                'label' => $this->attributeFrontendLabel,
            ],
        ];

        $this->assertEquals($expected, $this->model->getNewChildSelectOptions());
    }

    public function testGetAttributeObject()
    {
        $this->eavConfig->expects($this->once())
            ->method('getAttribute')
            ->with('customer', $this->attribute)
            ->willReturn($this->attribute);

        $this->model->setData('attribute', $this->attribute);

        $this->assertEquals($this->attribute, $this->model->getAttributeObject());
    }

    /**
     * Check getConditionsSql() method logic for attributes that has default_billing or default_shipping types
     *
     * @param string $attributeCode
     * @param int $websiteId
     * @param bool $isFiltered
     * @param string $expressionString
     * @param bool $isStatic
     * @param string $value
     * @dataProvider dataProviderGetConditionsSqlBillingShipping
     */
    public function testGetConditionsSqlBillingShipping(
        $attributeCode,
        $websiteId,
        $isFiltered,
        $expressionString,
        $isStatic,
        $value
    ) {
        $customerEntityTable = 'customer_entity';

        $expression = new \Zend_Db_Expr($expressionString, '1', '0');

        $this->select->expects($this->any())
            ->method('limit')
            ->willReturnMap([
                [1, null, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('reset')
            ->willReturnMap([
                [\Magento\Framework\DB\Select::COLUMNS, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('columns')
            ->willReturnMap([
                [new \Zend_Db_Expr($expression), null, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->eavConfig->expects($this->any())
            ->method('getAttribute')
            ->with('customer', $this->attribute)
            ->willReturn($this->attribute);

        $this->connectionMock->expects($this->any())
            ->method('getCheckSql')
            ->with($expressionString, '1', '0')
            ->willReturn($expression);

        $customerResourceMock = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerResourceMock->expects($this->any())
            ->method('getEntityTable')
            ->willReturn($customerEntityTable);

        $this->attribute->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $this->attribute->expects($this->any())
            ->method('isStatic')
            ->willReturn($isStatic);
        $this->attribute->expects($this->any())
            ->method('getEntity')
            ->willReturn($customerResourceMock);

        $this->resourceSegment->expects($this->any())
            ->method('getTable')
            ->with('customer_entity')
            ->willReturn($customerEntityTable);

        $this->model->setData('attribute', $this->attribute);
        $this->model->setData('value', $value);

        $this->assertEquals($this->select, $this->model->getConditionsSql($this->customer, $websiteId, $isFiltered));
    }

    /**
     * Data provider for testGetConditionsSqlBillingShipping test
     *
     * @return array
     */
    public function dataProviderGetConditionsSqlBillingShipping()
    {
        $websiteId = 1;
        $attributeCode = 'default_billing';

        return [
            [
                'attribute_code' => $attributeCode,
                'website_id' => $websiteId,
                'is_filtered' => false,
                'expression_string' => 'COUNT(*) = 0',
                'is_static' => true,
                'value' => null,
            ],
            [
                'attribute_code' => $attributeCode,
                'website_id' => $websiteId,
                'is_filtered' => true,
                'expression_string' => 'COUNT(*) = 0',
                'is_static' => false,
                'value' => '',
            ],
            [
                'attribute_code' => $attributeCode,
                'website_id' => $websiteId,
                'is_filtered' => true,
                'expression_string' => 'COUNT(*) != 0',
                'is_static' => false,
                'value' => 'is_exists',
            ],
        ];
    }

    /**
     * Check getConditionsSql() method logic for attributes that has not default_billing or default_shipping types
     *
     * @param string $attributeCode
     * @param string $attributeFrontendInput
     * @param bool $isStatic
     * @param array $value
     * @param array $operator
     * @dataProvider dataProviderGetConditionsSqlOther
     */
    public function testGetConditionsSqlOther(
        $attributeCode,
        $attributeFrontendInput,
        $isStatic,
        $value,
        $operator
    ) {
        $field = $isStatic ? 'main.' . $attributeCode : 'main.value';

        $this->select->expects($this->any())
            ->method('from')
            ->willReturnMap([
                [['main' => $this->attributeBackendTable], [new \Zend_Db_Expr(1)], null, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('limit')
            ->willReturnMap([
                [1, null, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('reset')
            ->willReturnMap([
                [\Magento\Framework\DB\Select::COLUMNS, $this->select],
            ]);
        $this->select->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->eavConfig->expects($this->any())
            ->method('getAttribute')
            ->with('customer', $this->attribute)
            ->willReturn($this->attribute);

        $this->connectionMock->expects($this->any())
            ->method('quoteColumnAs')
            ->with($field)
            ->willReturn($field);

        $this->attribute->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $this->attribute->expects($this->any())
            ->method('isStatic')
            ->willReturn($isStatic);
        $this->attribute->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn($attributeFrontendInput);

        $this->resourceSegment->expects($this->once())
            ->method('createConditionSql')
            ->with($field, $operator['expected'], $value['expected'])
            ->willReturn('condition');

        $this->model->setData('attribute', $this->attribute);
        $this->model->setData('value', $value['argument']);
        $this->model->setData('operator', $operator['argument']);

        $this->assertEquals($this->select, $this->model->getConditionsSql(null, null, false));
    }

    /**
     * Data provider for testGetConditionsSqlOther test
     *
     * @return array
     */
    public function dataProviderGetConditionsSqlOther()
    {
        return [
            [
                'attribute_code' => 'test',
                'frontend_input' => 'date',
                'is_static' => true,
                'value' => [
                    'argument' => '2017-01-01 00:00:00',
                    'expected' => '2017-01-01 00:00:00',
                ],
                'operator' => [
                    'argument' => '',
                    'expected' => '',
                ],
            ],
            [
                'attribute_code' => 'test',
                'frontend_input' => 'date',
                'is_static' => false,
                'value' => [
                    'argument' => '2017-01-01 00:00:00',
                    'expected' => [
                        'start' => '2017-01-01 00:00:00',
                        'end' => '2017-01-02 00:00:00',
                    ],
                ],
                'operator' => [
                    'argument' => '==',
                    'expected' => 'between',
                ],
            ],
            [
                'attribute_code' => 'test',
                'frontend_input' => 'multiselect',
                'is_static' => true,
                'value' => [
                    'argument' => '',
                    'expected' => '',
                ],
                'operator' => [
                    'argument' => '',
                    'expected' => '',
                ],
            ],
            [
                'attribute_code' => 'test',
                'frontend_input' => 'multiselect',
                'is_static' => false,
                'value' => [
                    'argument' => ['1'],
                    'expected' => [1],
                ],
                'operator' => [
                    'argument' => '==',
                    'expected' => 'finset',
                ],
            ],
        ];
    }
}
