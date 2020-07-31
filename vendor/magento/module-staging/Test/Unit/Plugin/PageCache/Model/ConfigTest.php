<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Plugin\PageCache\Model;

/**
 * Unit test for Page Cache config plugin.
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\Staging\Plugin\PageCache\Model\Config
     */
    private $subject;

    /**
     * @var \Magento\PageCache\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\Magento\PageCache\Model\Config::class);

        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->subject = new \Magento\Staging\Plugin\PageCache\Model\Config(
            $this->versionManagerMock
        );
    }

    public function testAfterIsEnabledPreview()
    {
        $isEnabled = true;

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->assertEquals(false, $this->subject->afterIsEnabled($this->configMock, $isEnabled));
    }

    public function testAfterIsEnabledNotPreview()
    {
        $isEnabled = true;

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);

        $this->assertEquals($isEnabled, $this->subject->afterIsEnabled($this->configMock, $isEnabled));
    }
}
