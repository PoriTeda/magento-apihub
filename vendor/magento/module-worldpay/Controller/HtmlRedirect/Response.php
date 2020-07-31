<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Controller\HtmlRedirect;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Worldpay\Gateway\Command\ResponseCommand;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\PaymentFailuresInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder;

/**
 * Displays message and redirect to the ResultController with appropriate parameter
 *
 * Class Response
 */
class Response extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var ResponseCommand
     */
    private $command;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private static $transStatusSuccess = 'Y';

    /**
     * @var string
     */
    private static $transStatusCancel = 'C';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentFailuresInterface
     */
    private $paymentFailures;

    /**
     * @param Context $context
     * @param ResponseCommand $command
     * @param LoggerInterface $logger
     * @param LayoutFactory $layoutFactory
     * @param OrderRepositoryInterface|null $orderRepository
     * @param PaymentFailuresInterface|null $paymentFailures
     */
    public function __construct(
        Context $context,
        ResponseCommand $command,
        LoggerInterface $logger,
        LayoutFactory $layoutFactory,
        OrderRepositoryInterface $orderRepository = null,
        PaymentFailuresInterface $paymentFailures = null
    ) {
        parent::__construct($context);

        $this->command = $command;
        $this->layoutFactory = $layoutFactory;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository ?: $this->_objectManager->get(OrderRepositoryInterface::class);
        $this->paymentFailures = $paymentFailures ?: $this->_objectManager->get(PaymentFailuresInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultLayout = $this->layoutFactory->create();
        $resultLayout->addDefaultHandle();
        $processor = $resultLayout->getLayout()->getUpdate();
        try {
            $this->command->execute(['response' => $params]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->handleCommandException($e->getMessage());
            $processor->load(['response_failure']);
            return $resultLayout;
        }

        switch ($params['transStatus']) {
            case self::$transStatusSuccess:
                $processor->load(['response_success']);
                break;
            case self::$transStatusCancel:
                $this->handleCommandException(__('Transaction has been canceled'));
                $processor->load(['response_cancel']);
                break;
            default:
                $this->handleCommandException(__('Transaction has been declined'));
                $processor->load(['response_failure']);
                break;
        }

        return $resultLayout;
    }

    /**
     * Gateway command exceptions handler.
     *
     * @param string|\Magento\Framework\Phrase $errorMessage
     * @return PaymentFailuresInterface
     */
    private function handleCommandException($errorMessage): PaymentFailuresInterface
    {
        $response = $this->getRequest()->getParams();
        if (isset($response[OrderDataBuilder::ORDER_ID])) {
            $order = $this->orderRepository->get((int)$response[OrderDataBuilder::ORDER_ID]);

            return $this->paymentFailures->handle((int)$order->getQuoteId(), $errorMessage);
        }

        return $this->paymentFailures;
    }
}
