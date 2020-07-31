<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerFinance\Test\Unit\Helper;

use Magento\Framework\DataObject;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    protected $model;

    /**
     * @var \Magento\Store\Model\StoreManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsites'])
            ->getMock();

        $this->model = $helper->getObject(
            \Magento\CustomerFinance\Helper\Data::class,
            [
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * @dataProvider populateParamsDataProvider
     */
    public function testPopulateParams($input, $output)
    {
        $website1 = new DataObject(['code' => 'foo_site']);
        $website2 = new DataObject(['code' => 'bar_site']);

        $this->storeManagerMock
            ->method('getWebsites')
            ->willReturn([$website1, $website2]);

        $this->model->populateParams($input);
        $this->assertEquals($output, $input);
    }

    public function populateParamsDataProvider()
    {
        return [
            [
                ['something'],
                ['something']
            ],
            [
                [
                    \Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP => [
                        'store_credit' => [100, 200]
                    ]
                ],
                [
                    \Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP => [
                        'foo_site_store_credit' => [100, 200],
                        'bar_site_store_credit' => [100, 200]
                    ]
                ],
            ]
        ];
    }
}
