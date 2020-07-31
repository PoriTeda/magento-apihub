<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogInventoryStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class AdvancedInventoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogInventoryStaging\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory
     */
    private $model;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $inventoryModifierMock;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->arrayManager = $objectManagerHelper->getObject(\Magento\Framework\Stdlib\ArrayManager::class);
        $this->inventoryModifierMock = $this->createPartialMock(
            \Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory::class,
            ['modifyData', 'modifyMeta']
        );
        $this->model = new \Magento\CatalogInventoryStaging\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory(
            $this->inventoryModifierMock
        );
        $objectManagerHelper->setBackwardCompatibleProperty($this->model, 'arrayManager', $this->arrayManager);
    }

    public function testModifyData()
    {
        $data = ['key' => 'value'];
        $this->inventoryModifierMock->expects($this->once())->method('modifyData')->with($data)->willReturn($data);
        $this->assertEquals($data, $this->model->modifyData($data));
    }

    public function testModifyMeta()
    {
        $meta = [
            'product-details' => [
                'children' => [
                    'quantity_and_stock_status_qty' => [
                        'in_stock' => true,
                        'qty' => 100
                    ],
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'product-details' => [
                'children' => [
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->inventoryModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);

        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }

    public function testModifyMetaWithNotDefaultAttributeSet()
    {
        $meta = [
            'inventory-details' => [
                'children' => [
                    'quantity_and_stock_status_qty' => [
                        'in_stock' => true,
                        'qty' => 100
                    ],
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'inventory-details' => [
                'children' => [
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->inventoryModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);

        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }
}
