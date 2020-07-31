<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Page\Save;

use Magento\CmsStaging\Controller\Adminhtml\Page\Save\Plugin;
use Psr\Log\LoggerInterface;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Plugin
     */
    protected $controller;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    protected function setUp()
    {
        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->controller = new Plugin(
            $this->logger
        );
    }

    /**
     * @dataProvider dataProviderBeforeExecute
     * @param mixed $customTheme
     * @param bool $hasCustomeTheme
     */
    public function testBeforeExecute(
        $customTheme,
        $hasCustomeTheme
    ) {
        $currentDate = new \DateTime(null, new \DateTimeZone('UTC'));

        $pageSaveMock = $this->getMockBuilder(\Magento\Cms\Controller\Adminhtml\Page\Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($this->once())
            ->method('getPostValue')
            ->with('custom_theme')
            ->willReturn($customTheme);

        if ($hasCustomeTheme) {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', $currentDate->format('m/d/Y'))
                ->willReturnSelf();
        } else {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', null)
                ->willReturnSelf();
        }

        $pageSaveMock->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($requestMock);

        $this->controller->beforeExecute($pageSaveMock);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeExecute()
    {
        return [
            [1, true],
            ['1', true],
            [0, false],
            ['test', false],
            [null, false],
            ['', false],
        ];
    }

    public function testBeforeExecuteException()
    {
        $pageSaveMock = $this->getMockBuilder(\Magento\Cms\Controller\Adminhtml\Page\Save::class)
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

        $this->controller->beforeExecute($pageSaveMock);
    }
}
