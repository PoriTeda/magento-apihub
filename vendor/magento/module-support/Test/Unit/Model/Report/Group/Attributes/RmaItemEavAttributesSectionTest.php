<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

class RmaItemEavAttributesSectionTest extends AbstractTest
{
    protected function setUp()
    {
        parent::prepareObjects(\Magento\Support\Model\Report\Group\Attributes\RmaItemEavAttributesSection::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerate()
    {
        $entityTypeId = '9';
        $this->entityTypeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createEntityTypeMock(['id' => $entityTypeId]));

        $data = [
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '157',
                    'attribute_code' => 'condition',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '154',
                    'attribute_code' => 'product_name',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'text',
                    'backend_type' => 'static',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '158',
                    'attribute_code' => 'reason',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '159',
                    'attribute_code' => 'reason_other',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'text',
                    'backend_type' => 'varchar',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '156',
                    'attribute_code' => 'resolution',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '148',
                    'attribute_code' => 'rma_entity_id',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'text',
                    'backend_type' => 'static',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '153',
                    'attribute_code' => 'status',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\Rma\Model\Item\Attribute\Source\Status::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'static',
                ]
            )
        ];

        $expectedResult = [
            (string)__('Rma Item Eav Attributes') => [
                'headers' => [
                    __('ID'), __('Code'), __('User Defined'), __('Source Model'),
                    __('Backend Model'), __('Frontend Model')
                ],
                'data' => [
                    [
                        '157',
                        'condition' . "\n" . '{frontend: select, backend: int}',
                        __('No'), \Magento\Eav\Model\Entity\Attribute\Source\Table::class . "\n"
                        . 'Magento/Eav/Model/Entity/Attribute/Source/Table.php',
                        '',
                        ''
                    ],
                    [
                        '154',
                        'product_name' . "\n" . '{frontend: text, backend: static}',
                        __('No'),
                        '',
                        '',
                        ''
                    ],
                    [
                        '158',
                        'reason' . "\n" . '{frontend: select, backend: int}',
                        __('No'), \Magento\Eav\Model\Entity\Attribute\Source\Table::class . "\n"
                        . 'Magento/Eav/Model/Entity/Attribute/Source/Table.php',
                        '',
                        ''
                    ],
                    [
                        '159',
                        'reason_other' . "\n" . '{frontend: text, backend: varchar}',
                        __('No'),
                        '',
                        '',
                        ''
                    ],
                    [
                        '156',
                        'resolution' . "\n" . '{frontend: select, backend: int}',
                        __('No'), \Magento\Eav\Model\Entity\Attribute\Source\Table::class . "\n"
                        . 'Magento/Eav/Model/Entity/Attribute/Source/Table.php',
                        '',
                        ''
                    ],
                    [
                        '148',
                        'rma_entity_id' . "\n" . '{frontend: text, backend: static}',
                        __('No'),
                        '',
                        '',
                        ''
                    ],
                    [
                        '153',
                        'status' . "\n" . '{frontend: select, backend: static}',
                        __('No'), \Magento\Rma\Model\Item\Attribute\Source\Status::class . "\n"
                        . 'Magento/Rma/Model/Item/Attribute/Source/Status.php',
                        '',
                        ''
                    ]
                ]
            ]
        ];

        $this->attributeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createEntityAttributeCollectionMock($data));

        $this->dataFormatterMock->expects($this->any())
            ->method('prepareModelValue')
            ->willReturnMap(
                [
                    [
                        \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                        \Magento\Eav\Model\Entity\Attribute\Source\Table::class . "\n"
                        . 'Magento/Eav/Model/Entity/Attribute/Source/Table.php'
                    ],
                    [
                        \Magento\Rma\Model\Item\Attribute\Source\Status::class,
                        \Magento\Rma\Model\Item\Attribute\Source\Status::class . "\n"
                        . 'Magento/Rma/Model/Item/Attribute/Source/Status.php'
                    ]
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
