<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Controller\ResultController;

use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Controller\Result\Redirect;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Response\RedirectInterface;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\HTTP\PhpEnvironment\Request;

/**
 * Class IndexTest
 *
 * @package Magento\Worldpay\Test\Unit\Controller\ResultController
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultRedirectMock;

    /**
     * @var Response
     */
    private $action;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
                                            ->disableOriginalConstructor()
                                            ->getMock();
        $this->redirectMock = $this->getMockBuilder(RedirectInterface::class)->disableOriginalConstructor()->getMock();
        $this->requestMock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();

        $this->contextMock
            ->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);
        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->once())->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($this->resultRedirectMock);

        $this->action = new \Magento\Worldpay\Controller\ResultController\Index(
            $this->contextMock
        );
    }

    /**
     * Exceptional case when the controller is called without required parameters
     */
    public function testExecuteWithoutParams()
    {
        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $this->assertEquals($this->resultRedirectMock, $this->action->execute());
    }

    /**
     * Request to the ResultController with parameter "type" => "success"
     */
    public function testSuccessRedirectType()
    {
        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/onepage/success')
            ->willReturnSelf();
        $this->requestMock->expects($this->once())->method('getParams')->willReturn(['type' => 'success']);

        $this->assertEquals($this->resultRedirectMock, $this->action->execute());
    }

    /**
     * Request to the ResultController with parameter "type" => "cancel"
     */
    public function testCancelRedirectType()
    {
        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();
        $this->requestMock->expects($this->once())->method('getParams')->willReturn(['type' => 'cancel']);
        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $this->assertEquals($this->resultRedirectMock, $this->action->execute());
    }

    /**
     * Request to the ResultController with parameter "type" => "failure"
     */
    public function testFailureRedirectType()
    {
        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();
        $this->requestMock->expects($this->once())->method('getParams')->willReturn(['type' => 'failure']);
        $this->messageManagerMock->expects($this->once())->method('addErrorMessage');

        $this->assertEquals($this->resultRedirectMock, $this->action->execute());
    }
}
