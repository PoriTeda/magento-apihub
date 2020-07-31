<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Model\ResourceModel\Catalogrule;

class ReadHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Catalogrule\ReadHandler
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $bannerModelFactoryMock;

    protected function setUp()
    {
        $this->bannerModelFactoryMock = $this->createPartialMock(
            \Magento\Banner\Model\BannerFactory::class,
            ['create']
        );
        $this->metadataMock = $this->createMock(\Magento\Framework\EntityManager\MetadataPool::class);

        $this->model = new \Magento\Banner\Model\ResourceModel\Catalogrule\ReadHandler(
            $this->bannerModelFactoryMock,
            $this->metadataMock
        );
    }

    public function testExecute()
    {
        $entityType = \Magento\CatalogRule\Api\Data\RuleInterface::class;
        $entityData = [
            'entity_id' => 100
        ];
        $relatedBanners = [1, 2, 3];

        $entityMetadataMock = $this->createPartialMock(
            \Magento\Framework\EntityManager\EntityMetadata::class,
            ['getIdentifierField']
        );
        $entityMetadataMock->expects($this->once())->method('getIdentifierField')->willReturn('entity_id');

        $this->metadataMock->expects($this->once())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($entityMetadataMock);

        $bannerModelMock = $this->createMock(\Magento\Banner\Model\Banner::class);
        $this->bannerModelFactoryMock->expects($this->once())->method('create')->willReturn($bannerModelMock);

        $bannerModelMock->expects($this->once())
            ->method('getRelatedBannersByCatalogRuleId')
            ->with(100)
            ->willReturn($relatedBanners);

        $expectedResult = array_merge($entityData, ['related_banners' => $relatedBanners]);

        $this->assertEquals(
            $expectedResult,
            $this->model->execute($entityType, $entityData)
        );
    }
}
