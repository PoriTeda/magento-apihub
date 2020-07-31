<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

class UserDefinedEavAttributesSectionTest extends AbstractTest
{
    protected function setUp()
    {
        parent::prepareObjects(\Magento\Support\Model\Report\Group\Attributes\UserDefinedEavAttributesSection::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerate()
    {
        $data = [
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '90',
                    'attribute_code' => 'color',
                    'is_user_defined' => '1',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                    'entity_type' => $this->createEntityTypeMock(
                        ['id' => '11', 'entity_type_code' => 'catalog_product']
                    )
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '77',
                    'attribute_code' => 'cost',
                    'is_user_defined' => '1',
                    'source_model' => '',
                    'backend_model' => \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
                    'frontend_model' => '',
                    'frontend_input' => 'price',
                    'backend_type' => 'decimal',
                    'entity_type' => $this->createEntityTypeMock(
                        ['id' => '11', 'entity_type_code' => 'catalog_product']
                    )
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '79',
                    'attribute_code' => 'manufacturer',
                    'is_user_defined' => '1',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                    'entity_type' => $this->createEntityTypeMock(
                        ['id' => '11', 'entity_type_code' => 'catalog_product']
                    )
                ]
            )
        ];

        $expectedResult = [
            (string)__('User Defined Eav Attributes') => [
                'headers' => [
                    __('ID'), __('Code'), __('Entity Type Code'), __('Source Model'),
                    __('Backend Model'), __('Frontend Model')
                ],
                'data' => [
                    [
                        '90',
                        'color' . "\n" . '{frontend: select, backend: int}',
                        'catalog_product',
                        '',
                        '',
                        ''
                    ],
                    [
                        '77',
                        'cost' . "\n" . '{frontend: price, backend: decimal}',
                        'catalog_product',
                        '', \Magento\Catalog\Model\Product\Attribute\Backend\Price::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Price.php',
                        ''
                    ],
                    [
                        '79',
                        'manufacturer' . "\n" . '{frontend: select, backend: int}',
                        'catalog_product',
                        '',
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
                        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
                        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Price.php'
                    ]
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
