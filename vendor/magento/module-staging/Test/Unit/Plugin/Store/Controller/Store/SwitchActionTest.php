<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Plugin\Store\Controller\Store;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test for plugin for store switch action.
 */
class SwitchActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\Staging\Plugin\Store\Controller\Store\SwitchAction
     */
    private $subject;

    /**
     * @var string
     */
    private $redirectUrl = 'http://magento.dev/sales/guest/form/?___store=';

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Store\Controller\Store\SwitchAction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $switchActionMock;

    /**
     * @var array
     */
    private $storeCodes = [
        'old' => 'test_store_old',
        'new' => 'test_store_new'
    ];

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
        $storeMock = $this->getMockForAbstractClass(
            \Magento\Store\Api\Data\StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn($this->storeCodes['new']);

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getPost']
        );
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with(\Magento\Store\Model\StoreManagerInterface::PARAM_NAME)
            ->willReturn($this->storeCodes['new']);

        $this->responseMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setRedirect']
        );

        $this->redirectMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\Response\RedirectInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->redirectMock->expects($this->any())
            ->method('getRedirectUrl')
            ->willReturn($this->redirectUrl . $this->storeCodes['old']);

        $this->objectManager = new ObjectManager($this);

        $this->switchActionMock = $this->createMock(\Magento\Store\Controller\Store\SwitchAction::class);
        $this->switchActionMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

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
        $this->storeRepositoryMock->expects($this->any())
            ->method('getActiveStoreByCode')
            ->with($this->storeCodes['new'])
            ->willReturn($storeMock);

        $this->subject = $this->objectManager->getObject(
            \Magento\Staging\Plugin\Store\Controller\Store\SwitchAction::class,
            [
                'request' => $this->requestMock,
                'versionManager' => $this->versionManagerMock,
                'redirect' => $this->redirectMock,
                'storeRepository' => $this->storeRepositoryMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param bool $isException
     *
     * @dataProvider dataProviderAroundExecute
     */
    public function testAroundExecute($isPreview, $isException)
    {
        $closureMock = function () {
            return;
        };

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if (!$isPreview) {
            $this->responseMock->expects($this->never())
                ->method('setRedirect');
        }

        if ($isPreview && $isException) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('getActiveStoreByCode')
                ->willThrowException(
                    new \Magento\Framework\Exception\LocalizedException(
                        new \Magento\Framework\Phrase('Test Exception')
                    )
                );

            $this->responseMock->expects($this->never())
                ->method('setRedirect');
        }

        if ($isPreview && !$isException) {
            $this->responseMock->expects($this->once())
                ->method('setRedirect')
                ->with($this->redirectUrl . $this->storeCodes['new']);
        }

        $this->subject->aroundExecute($this->switchActionMock, $closureMock);
    }

    /**
     * @return array
     */
    public function dataProviderAroundExecute()
    {
        return [
            [false, true],
            [true, true],
            [true, false]
        ];
    }
}
