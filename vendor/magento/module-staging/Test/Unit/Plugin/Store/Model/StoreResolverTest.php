<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Plugin\Store\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test for store resolver plugin.
 */
class StoreResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\Staging\Plugin\Store\Model\StoreResolver
     */
    private $subject;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Store\Api\StoreResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeResolverMock;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeRepositoryMock;

    protected function setUp()
    {
        $this->storeMock = $this->getMockForAbstractClass(
            \Magento\Store\Api\Data\StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['isActive']
        );

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->objectManager = new ObjectManager($this);

        $this->storeResolverMock = $this->createMock(\Magento\Store\Api\StoreResolverInterface::class);

        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->storeRepositoryMock = $this->getMockForAbstractClass(
            \Magento\Store\Api\StoreRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->subject = $this->objectManager->getObject(
            \Magento\Staging\Plugin\Store\Model\StoreResolver::class,
            [
                'request' => $this->requestMock,
                'storeRepository' => $this->storeRepositoryMock,
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param string|null $storeCode
     * @param bool $isException
     * @param bool $isStoreActive
     *
     * @dataProvider dataProviderAroundGetCurrentStoreId
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testAroundGetCurrentStoreId($isPreview, $storeCode, $isException, $isStoreActive)
    {
        $defaultStoreId = '1';
        $requestedStoreId = '2';

        $closureMock = function () use ($defaultStoreId) {
            return $defaultStoreId;
        };

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with(\Magento\Store\Model\StoreManagerInterface::PARAM_NAME)
            ->willReturn($storeCode);

        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($requestedStoreId);
        $this->storeMock->expects($this->any())
            ->method('isActive')
            ->willReturn($isStoreActive);

        // Assertions.
        if (!$isPreview || ($isPreview && !$storeCode)) {
            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && $isException) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willThrowException(
                    new \Magento\Framework\Exception\NoSuchEntityException(
                        new \Magento\Framework\Phrase('Test Exception')
                    )
                );

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && !$isException && !$isStoreActive) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willReturn($this->storeMock);

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && !$isException && $isStoreActive) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willReturn($this->storeMock);

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($requestedStoreId, $result);
        }
    }

    /**
     * @return array
     */
    public function dataProviderAroundGetCurrentStoreId()
    {
        return [
            [false, null, false, false],
            [true, null, false, false],
            [true, 'test_store_2', true, false],
            [true, 'test_store_2', false, false],
            [true, 'test_store_2', false, true]
        ];
    }
}
