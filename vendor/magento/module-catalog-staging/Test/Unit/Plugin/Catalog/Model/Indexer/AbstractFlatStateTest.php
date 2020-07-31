<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Catalog\Model\Indexer;

/**
 * Unit test for Abstract Flat State plugin.
 */
class AbstractFlatStateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\CatalogStaging\Plugin\Catalog\Model\Indexer\AbstractFlatState
     */
    private $subject;

    /**
     * @var \Magento\Catalog\Model\Indexer\AbstractFlatState|\PHPUnit_Framework_MockObject_MockObject
     */
    private $abstractFlatStateMock;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    protected function setUp()
    {
        $this->abstractFlatStateMock = $this->createMock(\Magento\Catalog\Model\Indexer\AbstractFlatState::class);

        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->subject = new \Magento\CatalogStaging\Plugin\Catalog\Model\Indexer\AbstractFlatState(
            $this->versionManagerMock
        );
    }

    /**
     * @param bool $isPreview
     * @param bool $isAvailable
     * @param bool $expectedResult
     *
     * @dataProvider aroundIsAvailableDataProvider
     */
    public function testAroundIsAvailable($isPreview, $isAvailable, $expectedResult)
    {
        $closureMock = function () use ($isAvailable) {
            return $isAvailable;
        };

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $result = $this->subject->aroundIsAvailable($this->abstractFlatStateMock, $closureMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function aroundIsAvailableDataProvider()
    {
        return [
            [true, true, false],
            [false, true, true]
        ];
    }
}
