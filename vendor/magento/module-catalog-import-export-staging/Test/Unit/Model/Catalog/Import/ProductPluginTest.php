<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogImportExportStaging\Test\Unit\Model\Catalog\Import;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;

class ProductPluginTest extends \PHPUnit\Framework\TestCase
{
    /** @var  \Magento\CatalogImportExportStaging\Model\Import\ProductPlugin */
    private $model;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\EntityManager\MetadataPool */
    private $metadataPoolMock;

    /** @var  \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManager;

    /** @var  \Magento\CatalogImportExport\Model\Import\Product */
    private $subject;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->metadataPoolMock = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->getMockBuilder(\Magento\CatalogImportExport\Model\Import\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(
            \Magento\CatalogImportExportStaging\Model\Import\ProductPlugin::class,
            ['metadataPool' => $this->metadataPoolMock]
        );
    }

    public function testBeforeSaveProductEntity()
    {
        $idField = 'id';
        $updateRows = ['should', 'not', 'be', 'changed'];
        $metadataMock = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadataMock);
        $metadataMock->expects($this->exactly(6))
            ->method('getIdentifierField')
            ->willReturn($idField);
        $metadataMock->expects($this->exactly(3))
            ->method('generateIdentifier')
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $insertRowsBefore = [
            ['color' => 'red', 'sku' => 'red shirt'],
            ['color' => 'blue', 'sku' => 'blue shirt'],
            ['color' => 'grey', 'sku' => 'grey shirt']
        ];
        $expectedInsertRows = [
            [
                'color' => 'red',
                'sku' => 'red shirt',
                'id' => 1,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
            [
                'color' => 'blue',
                'sku' => 'blue shirt',
                'id' => 2,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
            [
                'color' => 'grey',
                'sku' => 'grey shirt',
                'id' => 3,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
        ];
        $result = $this->model->beforeSaveProductEntity($this->subject, $insertRowsBefore, $updateRows);
        $this->assertEquals($expectedInsertRows, $result[0]);
        $this->assertEquals($updateRows, $result[1]);
    }
}
