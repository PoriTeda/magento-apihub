<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\DataCount;

class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes
     */
    protected $attributes;

    /**
     * @var \Magento\Eav\Model\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigFactoryMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Eav\Model\Entity\Type|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityTypeMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->eavConfigFactoryMock = $this->createPartialMock(\Magento\Eav\Model\ConfigFactory::class, ['create']);
        $this->eavConfigMock = $this->createMock(\Magento\Eav\Model\Config::class);

        $this->entityTypeMock = $this->createMock(\Magento\Eav\Model\Entity\Type::class);
        $this->entityTypeMock = $this->getMockBuilder(\Magento\Eav\Model\Entity\Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\Db\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->attributes = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes::class,
            ['eavConfigFactory' => $this->eavConfigFactoryMock]
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute()
    {
        $entityTypeId = 1;
        $type = 'customer';
        $info = [
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'int',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'int',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ]
        ];

        $expectedData = [
            [
                'Customer Attributes',
                23,
                'Attributes Flags: is_user_defined: 0; is_system: 16; is_used_for_customer_segment: 9; is_visible: 7; '
            ],
            [
                '', '', 'Attributes Types: static: 21; int: 2; '
            ]
        ];

        $this->eavConfigFactoryMock->expects($this->once())->method('create')->willReturn($this->eavConfigMock);
        $this->eavConfigMock->expects($this->once())->method('getEntityType')->with($type)->willReturn(
            $this->entityTypeMock
        );
        $this->entityTypeMock->expects($this->once())->method('getId')->willReturn($entityTypeId);

        $this->resourceMock->expects($this->atLeastOnce())->method('getTable')->willReturnMap(
            [
                ['customer_eav_attribute', 'customer_eav_attribute'],
                ['eav_attribute', 'eav_attribute']
            ]
        );
        $this->connectionMock->expects($this->once())->method('fetchAll')->willReturn($info);

        $this->assertSame($expectedData, $this->attributes->getAttributesCount($type, $this->resourceMock));
    }
}
