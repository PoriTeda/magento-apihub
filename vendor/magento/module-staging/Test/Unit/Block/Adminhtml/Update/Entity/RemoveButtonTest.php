<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\RemoveButton;
use Magento\Staging\Block\Adminhtml\Update\IdProvider;
use Magento\Staging\Model\Update;
use PHPUnit\Framework\MockObject\MockObject;

class RemoveButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateIdProviderMock;

    /**
     * @var RemoveButton
     */
    private $button;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var DateTime|MockObject
     */
    private $dateTimeMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->entityProviderMock = $this->getMockBuilder(EntityProviderInterface::class)
            ->getMockForAbstractClass();
        $this->updateIdProviderMock = $this->getMockBuilder(IdProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->button = $objectManager->getObject(
            RemoveButton::class,
            [
                'entityProvider' => $this->entityProviderMock,
                'updateIdProvider' => $this->updateIdProviderMock,
                'entityIdentifier' => '123894',
                'jsRemoveModal' => 'removeJsModal',
                'jsRemoveLoader' => 'removeJsLoader',
                'updateRepository' => $this->updateRepositoryMock,
                'dateTime' => $this->dateTimeMock,
            ]
        );
    }

    public function testGetButtonDataNoUpdate()
    {
        $this->updateIdProviderMock->expects($this->once())->method('getUpdateId')->willReturn(null);
        $this->assertEmpty($this->button->getButtonData());
    }

    /**
     * @param int $startTimeStamp
     * @param int $currentTimeStamp
     * @param bool $hasRemoveButton
     * @dataProvider getButtonDataDataProvider
     */
    public function testGetButtonData($startTimeStamp, $currentTimeStamp, $hasRemoveButton)
    {
        $checkFields = ['label', 'class', 'sort_order', 'data_attribute'];
        $updateId = 223335;
        $this->updateIdProviderMock->expects($this->exactly($hasRemoveButton ? 2 : 1))
            ->method('getUpdateId')
            ->willReturn($updateId);
        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->once())
            ->method('getStartTime')
            ->willReturn('startTime');
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($updateMock);
        $this->entityProviderMock->expects($this->exactly($hasRemoveButton ? 1 : 0))->method('getId');
        $this->dateTimeMock->expects($this->at(0))
            ->method('gmtTimestamp')
            ->willReturn($startTimeStamp);
        $this->dateTimeMock->expects($this->at(1))
            ->method('gmtTimestamp')
            ->willReturn($currentTimeStamp);

        $result = $this->button->getButtonData();
        if ($hasRemoveButton) {
            foreach ($checkFields as $field) {
                $this->assertArrayHasKey($field, $result);
            }
        } else {
            $this->assertEmpty($result);
        }
    }

    public function getButtonDataDataProvider()
    {
        return [
            'just_started' => [123, 123, false],
            'started' => [123, 125, false],
            'not_yet_start' => [123, 120, true],
        ];
    }
}
