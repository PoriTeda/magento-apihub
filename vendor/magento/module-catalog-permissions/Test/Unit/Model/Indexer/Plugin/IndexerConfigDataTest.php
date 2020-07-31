<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

class IndexerConfigDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\IndexerConfigData
     */
    protected $model;

    /**
     * @var \Magento\CatalogPermissions\App\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    protected function setUp()
    {
        $this->configMock = $this->createPartialMock(\Magento\CatalogPermissions\App\Config::class, ['isEnabled']);
        $this->subjectMock = $this->createMock(\Magento\Indexer\Model\Config\Data::class);

        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\IndexerConfigData($this->configMock);
    }

    /**
     * @param bool $isEnabled
     * @param string $path
     * @param mixed $default
     * @param array $inputData
     * @param array $outputData
     * @dataProvider afterGetDataProvider
     */
    public function testAfterGet($isEnabled, $path, $default, $inputData, $outputData)
    {
        $this->configMock->expects($this->any())->method('isEnabled')->will($this->returnValue($isEnabled));

        $this->assertEquals($outputData, $this->model->afterGet($this->subjectMock, $inputData, $path, $default));
    }

    public function afterGetDataProvider()
    {
        $categoryIndexerData = [
            'indexer_id' => \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID,
            'action' => '\Action\Class',
            'title' => 'Title',
            'description' => 'Description',
        ];
        $productIndexerData = [
            'indexer_id' => \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID,
            'action' => '\Action\Class',
            'title' => 'Title',
            'description' => 'Description',
        ];

        return [
            [
                true,
                null,
                null,
                [
                    \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID => $categoryIndexerData,
                    \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID => $productIndexerData
                ],
                [
                    \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID => $categoryIndexerData,
                    \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID => $productIndexerData
                ],
            ],
            [
                false,
                null,
                null,
                [
                    \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID => $categoryIndexerData,
                    \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID => $productIndexerData
                ],
                []
            ],
            [
                false,
                \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID,
                null,
                $categoryIndexerData,
                null
            ],
            [
                false,
                \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID,
                null,
                $productIndexerData,
                null
            ]
        ];
    }
}
