<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Controller\HtmlRedirect;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\PaymentFailuresInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Worldpay\Controller\HtmlRedirect\Response;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $layout;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var Response
     */
    private $action;

    /**
     * @var PaymentFailuresInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentFailures;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepository;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder(
            \Magento\Framework\App\RequestInterface::class
        )->getMockForAbstractClass();
        $this->layout = $this->getMockBuilder(
            \Magento\Framework\View\Result\Layout::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->command = $this->getMockBuilder(
            \Magento\Worldpay\Gateway\Command\ResponseCommand::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(
            \Psr\Log\LoggerInterface::class
        )->getMockForAbstractClass();
        $this->processor = $this->getMockBuilder(
            \Magento\Framework\View\Layout\ProcessorInterface::class
        )->getMockForAbstractClass();

        $this->paymentFailures = $this->getMockBuilder(PaymentFailuresInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $layoutFactory = $this->getMockBuilder(
            \Magento\Framework\View\Result\LayoutFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $layoutFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->layout);

        $om = new ObjectManager($this);

        $this->action = $om->getObject(
            \Magento\Worldpay\Controller\HtmlRedirect\Response::class,
            [
                'request' => $this->request,
                'command' => $this->command,
                'logger' => $this->logger,
                'layoutFactory' => $layoutFactory,
                'paymentFailures' => $this->paymentFailures,
                'orderRepository' => $this->orderRepository
            ]
        );
    }

    public function testExecuteCommandException()
    {
        $params = ['MC_order_id' => 1];
        $quoteId = 1;

        $exception = new \Exception;
        $viewLayout = $this->getMockBuilder(
            \Magento\Framework\View\Layout::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->method('getQuoteId')
            ->willReturn($quoteId);

        $this->request->method('getParams')
            ->willReturn($params);

        $this->layout->expects(static::once())
            ->method('addDefaultHandle');
        $this->layout->expects(static::once())
            ->method('getLayout')
            ->willReturn($viewLayout);
        $viewLayout->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->processor);

        $this->command->expects(static::once())
            ->method('execute')
            ->with(['response' => $params])
            ->willThrowException($exception);

        $this->paymentFailures->expects(static::once())
            ->method('handle')
            ->with($quoteId)
            ->willReturnSelf();

        $this->logger->expects(static::once())
            ->method('critical')
            ->with($exception);

        $this->processor->expects(static::once())
            ->method('load')
            ->with(['response_failure']);

        $this->orderRepository->method('get')
            ->with(1)
            ->willReturn($orderMock);

        static::assertSame(
            $this->layout,
            $this->action->execute()
        );
    }

    public function testExecuteAccept()
    {
        $params = [
            'transStatus' => 'Y'
        ];

        $viewLayout = $this->getMockBuilder(
            \Magento\Framework\View\Layout::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->request->expects(static::once())
            ->method('getParams')
            ->willReturn($params);

        $this->layout->expects(static::once())
            ->method('addDefaultHandle');
        $this->layout->expects(static::once())
            ->method('getLayout')
            ->willReturn($viewLayout);
        $viewLayout->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->processor);

        $this->command->expects(static::once())
            ->method('execute')
            ->with(['response' => $params]);

        $this->processor->expects(static::once())
            ->method('load')
            ->with(['response_success']);

        static::assertSame(
            $this->layout,
            $this->action->execute()
        );
    }

    public function testExecuteCancel()
    {
        $params = [
            'transStatus' => 'C'
        ];

        $viewLayout = $this->getMockBuilder(
            \Magento\Framework\View\Layout::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->request
            ->method('getParams')
            ->willReturn($params);

        $this->layout->expects(static::once())
            ->method('addDefaultHandle');
        $this->layout->expects(static::once())
            ->method('getLayout')
            ->willReturn($viewLayout);
        $viewLayout->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->processor);

        $this->command->expects(static::once())
            ->method('execute')
            ->with(['response' => $params]);

        $this->processor->expects(static::once())
            ->method('load')
            ->with(['response_cancel']);

        static::assertSame(
            $this->layout,
            $this->action->execute()
        );
    }
}
