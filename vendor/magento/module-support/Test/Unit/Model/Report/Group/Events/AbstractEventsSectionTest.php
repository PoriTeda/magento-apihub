<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Events;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Group\Events\AbstractEventsSection;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Config\Reader;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractEventsSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var AbstractEventsSection
     */
    protected $eventsSection;

    /**
     * @var Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $readerMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)->getMock();
        $this->readerMock = $this->getMockBuilder(\Magento\Framework\Event\Config\Reader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock
            ->expects($this->any())
            ->method('create')
            ->with(\Magento\Framework\Event\Config\Reader::class)
            ->willReturn($this->readerMock);
        $this->eventsSection = $this->objectManagerHelper->getObject($this->getSectionName(), [
            'logger' => $this->loggerMock,
            'objectManager' => $this->objectManagerMock,
        ]);
    }

    /**
     * @return string
     */
    abstract protected function getExpectedTitle();

    /**
     * @return string|null
     */
    abstract protected function getExpectedType();

    /**
     * @return string
     */
    abstract protected function getExpectedAreaCode();

    /**
     * @return string
     */
    abstract protected function getSectionName();

    /**
     * @return AbstractEventsSection
     */
    protected function getSection()
    {
        return $this->eventsSection;
    }

    /**
     * @return void
     */
    public function testGetTitle()
    {
        $this->assertSame($this->getExpectedTitle(), $this->getSection()->getTitle());
    }

    /**
     * @return void
     */
    public function testGetType()
    {
        $this->assertSame($this->getExpectedType(), $this->getSection()->getType());
    }

    /**
     * @return void
     */
    public function testGetExpectedAreaCode()
    {
        $this->assertSame($this->getExpectedAreaCode(), $this->getSection()->getAreaCode());
    }
}
