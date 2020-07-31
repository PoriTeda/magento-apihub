<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model;

use Magento\Staging\Model\CacheState;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\App\Cache\StateInterface;

/**
 * Class CacheStateTest
 */
class CacheStateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CacheState
     */
    private $cacheState;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var StateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stateMock;

    protected function setUp()
    {
        $this->stateMock = $this->getMockBuilder(StateInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cacheState = $objectManager->getObject(
            CacheState::class,
            [
                'state' => $this->stateMock,
                'versionManager' => $this->versionManagerMock,
                'cacheTypes' => [
                    'block_html' => false
                ],
            ]
        );
    }

    public function testIsPreviewCacheDisabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->assertFalse($this->cacheState->isEnabled('block_html'));
    }

    public function testIsPreviewCacheEnabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with('ddl_cache')
            ->willReturn(true);
        $this->assertTrue($this->cacheState->isEnabled('ddl_cache'));
    }

    public function testIsNotPreviewCacheEnabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);
        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with('block_html')
            ->willReturn(true);
        $this->assertTrue($this->cacheState->isEnabled('block_html'));
    }
}
