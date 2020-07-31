<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cybersource\Gateway\Http\SilentOrder\TransferFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Payment\Gateway\Http\Client\Zend;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Multishipping\Model\Checkout\Type\Multishipping.
 *
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MultishippingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Multishipping
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $orderSender = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->create(
            Multishipping::class,
            ['orderSender' => $orderSender]
        );
    }

    /**
     * Checks a case when multiple orders are created successfully using cybersource payment token.
     *
     * @magentoDataFixture Magento/Cybersource/Fixtures/quote_with_split_items.php
     * @magentoConfigFixture current_store payment/cybersource/active 1
     * @return void
     */
    public function testCreateOrders()
    {
        $paymentToken = 'test token';
        $quote = $this->getQuote('multishipping_quote_id');
        $quote->getPayment()->setAdditionalInformation('payment_token', $paymentToken);

        $this->objectManager->addSharedInstance(
            $this->getHttpClientMock(),
            'HtmlConverterZendClient'
        );
        $this->objectManager->addSharedInstance(
            $this->getResponseValidatorMock(),
            'CybersourceSilentOrderValidator'
        );
        $this->objectManager->addSharedInstance(
            $this->getTransferFactoryWithTokenConstraintMock($paymentToken),
            TransferFactory::class
        );

        /** @var CheckoutSession $session */
        $session = $this->objectManager->get(CheckoutSession::class);
        $session->replaceQuote($quote);

        $this->model->createOrders();

        $orderList = $this->getOrderList((int)$quote->getId());
        self::assertCount(3, $orderList);
    }

    /**
     * Retrieves quote by reserved order id.
     *
     * @param string $reservedOrderId
     * @return Quote
     */
    private function getQuote(string $reservedOrderId): Quote
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }

    /**
     * Get list of orders by quote id.
     *
     * @param int $quoteId
     * @return array
     */
    private function getOrderList(int $quoteId): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('quote_id', $quoteId)
            ->create();

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        return $orderRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @return MockObject
     */
    private function getHttpClientMock(): MockObject
    {
        $response = [
            'req_card_expiry_date' => '01-2018-25',
            'req_card_number' => 'xxxxxxxx4463',
            'req_card_type' => '001',
        ];
        $client = $this->getMockBuilder(Zend::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('placeRequest')->willReturn($response);

        return $client;
    }

    /**
     * @return MockObject
     */
    private function getResponseValidatorMock(): MockObject
    {
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockForAbstractClass(ResultInterface::class);
        $result->method('isValid')->willReturn(true);
        $validator->method('validate')->willReturn($result);

        return $validator;
    }

    /**
     * @param string $paymentToken
     * @return MockObject
     */
    private function getTransferFactoryWithTokenConstraintMock(string $paymentToken): MockObject
    {
        $transfer = $this->getMockForAbstractClass(TransferInterface::class);
        $transferFactory = $this->getMockBuilder(TransferFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferFactory->expects($this->exactly(3))
            ->method('create')
            ->with($this->contains($paymentToken))
            ->willReturn($transfer);
        
        return $transferFactory;
    }
}
