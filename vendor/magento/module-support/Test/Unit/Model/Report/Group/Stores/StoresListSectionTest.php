<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Stores;

class StoresListSectionTest extends AbstractTest
{
    protected function setUp()
    {
        parent::prepareObjects(\Magento\Support\Model\Report\Group\Stores\StoresListSection::class);
    }

    public function testGenerate()
    {
        $stores = [
            '1' => $this->getStoreMock(
                [
                    'id' => '1',
                    'name' => 'Main Website Store',
                    'root_category_id' => '2',
                    'default_store' => $this->getStoreViewMock(
                        [
                            'id' => '1',
                            'name' => 'Default Store View'
                        ]
                    )
                ]
            )
        ];
        $expectedResult = [
            (string)__('Stores List') => [
                'headers' => [__('ID'), __('Name'), __('Root Category'), __('Default Store View')],
                'data' => [
                    ['1', 'Main Website Store', 'Default Category {ID:2}', 'Default Store View {ID:1}']
                ]
            ]
        ];

        $this->storeManagerMock->expects($this->once())
            ->method('getGroups')
            ->willReturn($stores);
        $this->categoryCollectionMock->expects($this->any())
            ->method('getItemById')
            ->willReturnMap(
                [
                    ['2', $this->getCategoryMock(['name' => 'Default Category'])]
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
