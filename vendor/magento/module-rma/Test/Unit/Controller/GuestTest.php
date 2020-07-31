<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller;

/**
 * Class GuestTest
 * @package Magento\Rma\Controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class GuestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Guest
     */
    protected $controller;

    /**
     * @var \Magento\Framework\Registry | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\App\Request\Http | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Response\Http | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\ObjectManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Url | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface  | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\Message\Manager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Rma\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Sales\Helper\Guest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $salesGuestHelper;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirect;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectFactory;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->messageManager = $this->createMock(\Magento\Framework\Message\Manager::class);
        $this->redirect = $this->createMock(\Magento\Store\App\Response\Redirect::class);
        $this->url = $this->createMock(\Magento\Framework\Url::class);
        $this->rmaHelper = $this->createMock(\Magento\Rma\Helper\Data::class);
        $this->salesGuestHelper = $this->createMock(\Magento\Sales\Helper\Guest::class);

        $this->resultRedirect = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder(
            \Magento\Framework\Controller\Result\RedirectFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirectFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $context = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $context->expects($this->once())->method('getObjectManager')->willReturn($this->objectManager);
        $context->expects($this->once())->method('getMessageManager')->willReturn($this->messageManager);
        $context->expects($this->once())->method('getRedirect')->willReturn($this->redirect);
        $context->expects($this->once())->method('getUrl')->willReturn($this->url);
        $context->expects($this->once())->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactory);

        $this->coreRegistry = $this->createPartialMock(\Magento\Framework\Registry::class, ['registry']);

        $this->controller = $objectManagerHelper->getObject(
            '\\Magento\\Rma\\Controller\\Guest\\' . $this->name,
            [
                'coreRegistry' => $this->coreRegistry,
                'context' => $context,
                'rmaHelper' => $this->rmaHelper,
                'salesGuestHelper' => $this->salesGuestHelper,
            ]
        );
    }
}
