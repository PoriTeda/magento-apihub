<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

class ProductEavAttributesSectionTest extends AbstractTest
{
    protected function setUp()
    {
        parent::prepareObjects(\Magento\Support\Model\Report\Group\Attributes\ProductEavAttributesSection::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerate()
    {
        $entityTypeId = '4';
        $this->entityTypeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createEntityTypeMock(['id' => $entityTypeId]));

        $data = [
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '134',
                    'attribute_code' => 'allow_message',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'text',
                    'backend_type' => 'int',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '124',
                    'attribute_code' => 'allow_open_amount',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\GiftCard\Model\Source\Open::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'int',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '102',
                    'attribute_code' => 'category_ids',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => \Magento\Catalog\Model\Product\Attribute\Backend\Category::class,
                    'frontend_model' => '',
                    'frontend_input' => 'text',
                    'backend_type' => 'static',
                ]
            ),
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
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '111',
                    'attribute_code' => 'country_of_manufacturer',
                    'is_user_defined' => '0',
                    'source_model' => \Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture::class,
                    'backend_model' => '',
                    'frontend_model' => '',
                    'frontend_input' => 'select',
                    'backend_type' => 'varchar',
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '98',
                    'attribute_code' => 'custom_design_from',
                    'is_user_defined' => '0',
                    'source_model' => '',
                    'backend_model' => \Magento\Catalog\Model\Attribute\Backend\Startdate::class,
                    'frontend_model' => '',
                    'frontend_input' => 'date',
                    'backend_type' => 'datetime',
                ]
            )
        ];

        $expectedResult = [
            (string)__('Product Eav Attributes') => [
                'headers' => [
                    __('ID'), __('Code'), __('User Defined'), __('Source Model'),
                    __('Backend Model'), __('Frontend Model')
                ],
                'data' => [
                    [
                        '134',
                        'allow_message' . "\n" . '{frontend: text, backend: int}',
                        __('No'),
                        '',
                        '',
                        ''
                    ],
                    [
                        '124',
                        'allow_open_amount' . "\n" . '{frontend: select, backend: int}',
                        __('No'),
                        \Magento\GiftCard\Model\Source\Open::class . "\n" . 'Magento/GiftCard/Model/Source/Open.php',
                        '',
                        ''
                    ],
                    [
                        '102',
                        'category_ids' . "\n" . '{frontend: text, backend: static}',
                        __('No'),
                        '',
                        \Magento\Catalog\Model\Product\Attribute\Backend\Category::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Category.php',
                        ''
                    ],
                    [
                        '90',
                        'color' . "\n" . '{frontend: select, backend: int}',
                        __('Yes'),
                        '',
                        '',
                        ''
                    ],
                    [
                        '77',
                        'cost' . "\n" . '{frontend: price, backend: decimal}',
                        __('Yes'),
                        '',
                        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Price.php',
                        ''
                    ],
                    [
                        '111',
                        'country_of_manufacturer' . "\n" . '{frontend: select, backend: varchar}',
                        __('No'),
                        \Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Source/Countryofmanufacture.php',
                        '',
                        ''
                    ],
                    [
                        '98',
                        'custom_design_from' . "\n" . '{frontend: date, backend: datetime}',
                        __('No'),
                        '',
                        \Magento\Catalog\Model\Attribute\Backend\Startdate::class . "\n"
                        . 'Magento/Catalog/Model/Attribute/Backend/Startdate.php',
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
                        \Magento\GiftCard\Model\Source\Open::class,
                        \Magento\GiftCard\Model\Source\Open::class . "\n" . 'Magento/GiftCard/Model/Source/Open.php'
                    ],
                    [
                        \Magento\Catalog\Model\Product\Attribute\Backend\Category::class,
                        \Magento\Catalog\Model\Product\Attribute\Backend\Category::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Category.php'
                    ],
                    [
                        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
                        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Backend/Price.php'
                    ],
                    [
                        \Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture::class,
                        \Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture::class . "\n"
                        . 'Magento/Catalog/Model/Product/Attribute/Source/Countryofmanufacture.php'
                    ],
                    [
                        \Magento\Catalog\Model\Attribute\Backend\Startdate::class,
                        \Magento\Catalog\Model\Attribute\Backend\Startdate::class . "\n"
                        . 'Magento/Catalog/Model/Attribute/Backend/Startdate.php'
                    ]
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
