<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\DataProvider;

use Magento\Staging\Model\Entity\DataProvider\DataProviderPlugin;
use Magento\Framework\Exception\NoSuchEntityException;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var DataProviderPlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->plugin = new DataProviderPlugin(
            $this->requestMock,
            $this->updateRepositoryMock,
            $this->versionManagerMock
        );
    }

    public function testAroundGetData()
    {
        $updateId = 1;
        $entityId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $dataProviderMock = $this->createMock(
            \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class
        );

        $updateMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($updateId);
        $updateMock->expects($this->any())->method('getName')->willReturn('Update Name');
        $updateMock->expects($this->any())->method('getDescription')->willReturn('Update Description');
        $updateMock->expects($this->any())->method('getStartTime')->willReturn(1000);
        $updateMock->expects($this->any())->method('getEndTime')->willReturn(2000);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $this->versionManagerMock->expects($this->once())->method('setCurrentVersionId')->with($updateId);

        $expectedResult = [
            $entityId => [
                'key' => 'value',
                'staging' => [
                    'mode' => 'save',
                    'update_id' => $updateId,
                    'name' => 'Update Name',
                    'description' => 'Update Description',
                    'start_time' => 1000,
                    'end_time' => 2000,
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }

    public function testAroundGetDataReturnsOnlyEntityDataIfUpdateIsNotFound()
    {
        $updateId = 1;
        $entityId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())
            ->method('get')
            ->with($updateId)
            ->willThrowException(NoSuchEntityException::singleField('id', $updateId));
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');

        $dataProviderMock = $this->createMock(
            \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class
        );

        $expectedResult = [
            $entityId => [
                'key' => 'value',
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }
}
