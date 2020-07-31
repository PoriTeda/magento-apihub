<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Plugin\Framework\App;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test for front controller interface plugin.
 */
class FrontControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\Staging\Plugin\Framework\App\FrontController
     */
    private $subject;

    /**
     * @var \Magento\Backend\Model\Auth|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    protected function setUp()
    {
        $this->authMock = $this->createMock(\Magento\Backend\Model\Auth::class);

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['initForward']
        );

        $this->objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->subject = $this->objectManager->getObject(
            \Magento\Staging\Plugin\Framework\App\FrontController::class,
            [
                'auth' => $this->authMock,
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param bool $isUserExists
     * @param bool $isUserLoggedIn
     * @param bool $isUserAllowed
     *
     * @dataProvider dataProviderBeforeDispatch
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testBeforeDispatch($isPreview, $isUserExists, $isUserLoggedIn, $isUserAllowed)
    {
        $frontControllerMock = $this->createMock(\Magento\Framework\App\FrontControllerInterface::class);

        $storageMock = $this->getMockForAbstractClass(
            \Magento\Backend\Model\Auth\StorageInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['isAllowed']
        );
        $storageMock->expects($this->any())
            ->method('isAllowed')
            ->with('Magento_Staging')
            ->willReturn($isUserAllowed);

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $this->authMock->expects($this->any())
            ->method('getUser')
            ->willReturn($this->getUserMock($isUserExists));
        $this->authMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn($isUserLoggedIn);
        $this->authMock->expects($this->any())
            ->method('getAuthStorage')
            ->willReturn($storageMock);

        // Assertions.
        if (!$isPreview) {
            $this->requestMock->expects($this->never())
                ->method('setActionName');
        }

        if ($isPreview && !$isUserExists) {
            $this->requestMock->expects($this->once())
                ->method('setActionName')
                ->with('noroute');
        }

        if ($isPreview && $isUserExists && (!$isUserLoggedIn || !$isUserAllowed)) {
            $this->requestMock->expects($this->once())
                ->method('setActionName')
                ->with('noroute');
        }

        if ($isPreview && $isUserExists && $isUserLoggedIn && $isUserAllowed) {
            $this->requestMock->expects($this->never())
                ->method('setActionName');
        }

        $this->subject->beforeDispatch($frontControllerMock, $this->requestMock);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeDispatch()
    {
        return [
            [false, false, false, false],
            [true, false, false, false],
            [true, true, false, false],
            [true, true, true, false],
            [true, true, false, true],
            [true, true, true, true]
        ];
    }

    /**
     * @param bool $isUserExists
     *
     * @return \Magento\Backend\Model\Auth\Credential\StorageInterface|\PHPUnit_Framework_MockObject_MockObject|null
     */
    public function getUserMock($isUserExists)
    {
        if ($isUserExists) {
            return $this->createMock(\Magento\Backend\Model\Auth\Credential\StorageInterface::class);
        }

        return null;
    }
}
