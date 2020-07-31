<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\DataProvider\Identifier;

use Magento\Staging\Model\Entity\DataProvider\Identifier\DataProviderPlugin;
use Magento\Framework\Exception\NoSuchEntityException;

class DataProviderPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataProviderPlugin
     */
    private $plugin;

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

    protected function setUp()
    {
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);

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

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $this->versionManagerMock->expects($this->once())->method('setCurrentVersionId')->with($updateId);

        $expectedResult = [
            $entityId => [
                'key' => 'value',
                'update_id' => $updateId,
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }

    public function testAroundGetDataReturnsOnlyEntityDataIfUpdateIsNotFound()
    {
        $entityId = 1;
        $updateId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');
        $this->updateRepositoryMock->expects($this->any())
            ->method('get')
            ->with($updateId)
            ->willThrowException(NoSuchEntityException::singleField('id', $updateId));

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
