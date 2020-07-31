<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Block\Adminhtml\Block\Update;

use Magento\CmsStaging\Block\Adminhtml\Block\Update\Provider;
use Magento\Framework\Exception\NoSuchEntityException;

class ProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $blockRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Provider
     */
    private $provider;

    protected function setUp()
    {
        $this->blockRepositoryMock = $this->createMock(\Magento\Cms\Api\BlockRepositoryInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->provider = new Provider(
            $this->requestMock,
            $this->blockRepositoryMock
        );
    }

    public function testGetIdReturnsBlockIdIfBlockExists()
    {
        $blockId = 1;

        $blockMock = $this->createMock(\Magento\Cms\Api\Data\BlockInterface::class);
        $blockMock->expects($this->any())->method('getId')->willReturn($blockId);

        $this->requestMock->expects($this->any())->method('getParam')->with('block_id')->willReturn($blockId);
        $this->blockRepositoryMock->expects($this->any())->method('getById')->with($blockId)->willReturn($blockMock);

        $this->assertEquals($blockId, $this->provider->getId());
    }

    public function testGetIdReturnsNullIfBlockDoesNotExist()
    {
        $blockId = 9999;

        $this->requestMock->expects($this->any())->method('getParam')->with('block_id')->willReturn($blockId);
        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($blockId)
            ->willThrowException(NoSuchEntityException::singleField('block_id', $blockId));

        $this->assertNull($this->provider->getId());
    }

    public function testGetUrlReturnsNull()
    {
        $this->assertNull($this->provider->getUrl(1));
    }
}
