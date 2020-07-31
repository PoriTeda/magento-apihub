<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Page\Update\Save;

use Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save\Plugin;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Psr\Log\LoggerInterface;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Plugin
     */
    protected $controller;

    /**
     * @var UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateRepository;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    protected function setUp()
    {
        $this->updateRepository = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->controller = new Plugin(
            $this->updateRepository,
            $this->logger
        );
    }

    /**
     * @dataProvider dataProviderBeforeExecute
     * @param mixed $customTheme
     * @param bool $hasCustomeTheme
     * @param string $mode
     */
    public function testBeforeExecute(
        $customTheme,
        $hasCustomeTheme,
        $mode
    ) {
        $currentDate = new \DateTime(null, new \DateTimeZone('UTC'));

        $staging = [
            'select_id' => 1,
            'start_time' => $currentDate->format('Y-m-d H:i:s'),
            'mode' => $mode,
        ];

        $pageSaveMock = $this->getMockBuilder(\Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturnMap([
                ['custom_theme', null, $customTheme],
                ['staging', null, $staging],
            ]);

        $pageSaveMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        if ($hasCustomeTheme) {
            if ($mode == 'assign' || $mode == 'save') {
                $requestMock->expects($this->once())
                    ->method('setPostValue')
                    ->with('custom_theme_from', $currentDate->format('m/d/Y'))
                    ->willReturnSelf();
            }
        } else {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', null)
                ->willReturnSelf();
        }

        if ($mode == 'assign') {
            $updateMock = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
                ->getMockForAbstractClass();

            $updateMock->expects($this->once())
                ->method('getStartTime')
                ->willReturn($currentDate->format('Y-m-d H:i:s'));

            $this->updateRepository->expects($this->once())
                ->method('get')
                ->with()
                ->willReturn($updateMock);
        }

        $result = $this->controller->beforeExecute($pageSaveMock);
        $this->assertNull($result);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeExecute()
    {
        return [
            [1, true, 'assign'],
            [1, true, 'save'],
            [1, true, ''],
            ['1', true, ''],
            [0, false, ''],
            ['test', false, ''],
            [null, false, ''],
            ['', false, ''],
        ];
    }

    public function testBeforeExecuteException()
    {
        $pageSaveMock = $this->getMockBuilder(\Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new \Exception(__('Error'));

        $pageSaveMock->expects($this->once())
            ->method('getRequest')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception)
            ->willReturnSelf();

        $result = $this->controller->beforeExecute($pageSaveMock);
        $this->assertNull($result);
    }
}
