<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Model\Block;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testMetadataReplace()
    {
        $metadataProviderMock = $this->createMock(
            \Magento\Staging\Model\Entity\DataProvider\MetadataProvider::class
        );
        $collectionFactoryMock = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Block\CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(\Magento\Cms\Model\ResourceModel\Block\Collection::class));

        $metadataProviderMock->expects($this->once())->method('getMetadata')->willReturn(['key', 'value']);

        new \Magento\CmsStaging\Model\Block\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactoryMock,
            $this->createMock(\Magento\Framework\App\Request\DataPersistorInterface::class),
            $metadataProviderMock
        );
    }
}
