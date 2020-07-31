<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class RuleTest
 * @package Magento\TargetRule\Model
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Rule
     */
    protected $_rule;

    /**
     * SQL Builder mock
     *
     * @var \Magento\Rule\Model\Condition\Sql\Builder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_sqlBuilderMock;

    /**
     * Product factory mock
     *
     * @var \Magento\Catalog\Model\ProductFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productFactory;

    /**
     * Rule factory mock
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\CombineFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ruleFactory;

    /**
     * Action factory mock
     *
     * @var \Magento\TargetRule\Model\Actions\Condition\CombineFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_actionFactory;

    /**
     * Locale date mock
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_localeDate;

    protected function setUp()
    {
        $this->_sqlBuilderMock = $this->_getCleanMock(\Magento\Rule\Model\Condition\Sql\Builder::class);

        $this->_productFactory = $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']);

        $this->_ruleFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule\Condition\CombineFactory::class,
            ['create']
        );

        $this->_actionFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Actions\Condition\CombineFactory::class,
            ['create']
        );

        $this->_localeDate = $this->getMockForAbstractClass(
            \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class,
            ['isScopeDateInInterval'],
            '',
            false
        );

        $this->prepareObjectManager([
            [
                \Magento\Framework\Api\ExtensionAttributesFactory::class,
                $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class)
            ],
            [
                \Magento\Framework\Api\AttributeValueFactory::class,
                $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class)
            ],
            [
                \Magento\Framework\Serialize\Serializer\Json::class,
                $this->getSerializerMock()
            ]
        ]);

        $this->_rule = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Rule::class,
            [
                'context' => $this->_getCleanMock(\Magento\Framework\Model\Context::class),
                'registry' => $this->_getCleanMock(\Magento\Framework\Registry::class),
                'formFactory' => $this->_getCleanMock(\Magento\Framework\Data\FormFactory::class),
                'localeDate' => $this->_localeDate,
                'ruleFactory' => $this->_ruleFactory,
                'actionFactory' => $this->_actionFactory,
                'productFactory' => $this->_productFactory,
                'ruleProductIndexerProcessor' => $this->_getCleanMock(
                    \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
                ),
                'sqlBuilder' => $this->_sqlBuilderMock,
            ]
        );
    }

    /**
     * Get mock for serializer
     *
     * @return \Magento\Framework\Serialize\Serializer\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSerializerMock()
    {
        $serializerMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $serializerMock->expects($this->any())
            ->method('serialize')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return json_encode($value);
                    }
                )
            );

        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return json_decode($value, true);
                    }
                )
            );

        return $serializerMock;
    }

    /**
     * Get clean mock by class name
     *
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getCleanMock($className)
    {
        return $this->createMock($className);
    }

    public function testDataHasChangedForAny()
    {
        $fields = ['first', 'second'];
        $this->assertEquals(false, $this->_rule->dataHasChangedForAny($fields));

        $fields = ['first', 'second'];
        $this->_rule->setData('first', 'test data');
        $this->_rule->setOrigData('first', 'origin test data');
        $this->assertEquals(true, $this->_rule->dataHasChangedForAny($fields));
    }

    public function testGetConditionsInstance()
    {
        $this->_ruleFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue(true));

        $this->assertEquals(true, $this->_rule->getConditionsInstance());
    }

    public function testGetActionsInstance()
    {
        $this->_actionFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue(true));

        $this->assertEquals(true, $this->_rule->getActionsInstance());
    }

    public function testGetAppliesToOptions()
    {
        $result[\Magento\TargetRule\Model\Rule::RELATED_PRODUCTS] = __('Related Products');
        $result[\Magento\TargetRule\Model\Rule::UP_SELLS] = __('Up-sells');
        $result[\Magento\TargetRule\Model\Rule::CROSS_SELLS] = __('Cross-sells');

        $this->assertEquals($result, $this->_rule->getAppliesToOptions());

        $result[''] = __('-- Please Select --');

        $this->assertEquals($result, $this->_rule->getAppliesToOptions('test'));
    }

    public function testPrepareMatchingProducts()
    {
        $productCollection = $this->_getCleanMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);

        $productCollection->expects($this->once())
            ->method('getAllIds')
            ->will($this->returnValue([1, 2, 3]));

        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getCollection', '__sleep', '__wakeup', 'load', 'getId']
        );

        $productMock->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($productCollection));

        $productMock->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());

        $this->_productFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($productMock));

        /**
         * @var \Magento\Rule\Model\Condition\Combine|\PHPUnit_Framework_MockObject_MockObject $conditions
         */
        $conditions = $this->createPartialMock(
            \Magento\Rule\Model\Condition\Combine::class,
            ['collectValidatedAttributes', 'getConditionForCollection']
        );

        $conditions->expects($this->once())
            ->method('collectValidatedAttributes')
            ->with($productCollection);

        $conditions->expects($this->once())
            ->method('getConditionForCollection')
            ->with($productCollection);

        $this->_rule->setConditions($conditions);
        $this->_rule->prepareMatchingProducts();
        $this->assertEquals([1, 2, 3], $this->_rule->getMatchingProductIds());
    }

    public function testCheckDateForStore()
    {
        $storeId = 1;
        $this->_localeDate->expects($this->once())
            ->method('isScopeDateInInterval')
            ->will($this->returnValue(true));
        $this->assertEquals(true, $this->_rule->checkDateForStore($storeId));
    }

    public function testGetPositionsLimit()
    {
        $this->assertEquals(20, $this->_rule->getPositionsLimit());

        $this->_rule->setData('positions_limit', 10);
        $this->assertEquals(10, $this->_rule->getPositionsLimit());
    }

    public function testGetActionSelectBind()
    {
        $this->assertEquals(null, $this->_rule->getActionSelectBind());

        $result = [1 => 'test'];
        $this->_rule->setData('action_select_bind', json_encode($result));
        $this->assertEquals($result, $this->_rule->getActionSelectBind());

        $this->_rule->setActionSelectBind($result);
        $this->assertEquals($result, $this->_rule->getActionSelectBind());
    }

    public function testValidateData()
    {
        $object = $this->_getCleanMock(\Magento\Framework\DataObject::class);
        $this->assertEquals(true, $this->_rule->validateData($object));

        $object = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $array['actions'] = [1 => 'test'];

        $object->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($array));

        $this->assertEquals(true, $this->_rule->validateData($object));

        $object = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $array['actions'] = [2 => ['type' => \Magento\Framework\DataObject::class, 'attribute' => 'test attribute']];

        $object->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($array));

        $result = [ 0 => __(
            'This attribute code is invalid. Please use only letters (a-z), numbers (0-9) or underscores (_),'
            . ' and be sure the code begins with a letter.'
        )];
        $this->assertEquals($result, $this->_rule->validateData($object));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The attribute's model class name is invalid. Verify the name and try again.
     */
    public function testValidateDataWithException()
    {
        $object = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $array['actions'] = [2 => ['type' => 'test type', 'attribute' => 'test attribute']];

        $object->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($array));

        $this->_rule->validateData($object);
    }

    public function testValidateByEntityId()
    {
        $combine = $this->createPartialMock(
            \Magento\Rule\Model\Condition\Combine::class,
            ['setRule', 'setId', 'setPrefix']
        );

        $combine->expects($this->any())
            ->method('setRule')
            ->will($this->returnSelf());

        $combine->expects($this->any())
            ->method('setId')
            ->will($this->returnSelf());

        $this->_ruleFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($combine));

        $this->assertEquals(true, $this->_rule->validateByEntityId(1));
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
        $reflectionClass = new \ReflectionClass(\Magento\Framework\App\ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
