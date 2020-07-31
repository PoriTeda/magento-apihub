<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Api;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Checkout\Model\PaymentInformationManagement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentInformationManagementTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $management;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->httpClient = $this->getMockForAbstractClass(ClientInterface::class);

        $this->objectManager->addSharedInstance($this->httpClient, 'HtmlConverterZendClient');
        $responseValidator = $this->getResponseValidatorMock();
        $this->objectManager->addSharedInstance($responseValidator, 'CybersourceSilentOrderValidator');

        $this->management = $this->objectManager->get(PaymentInformationManagementInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->objectManager->removeSharedInstance('HtmlConverterZendClient');
        $this->objectManager->removeSharedInstance('CybersourceSilentOrderValidator');
    }

    /**
     * Checks a case when order should be placed via Cybersource with "Sale" payment action.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_shipping_method.php
     * @magentoConfigFixture current_store payment/cybersource/active 1
     * @magentoConfigFixture current_store payment/cybersource/payment_action authorize_capture
     * @return void
     */
    public function testSavePaymentInformationAndPlaceOrder()
    {
        $gatewayToken = 'dk2ls92v';
        $quote = $this->getQuote('test_order_1');
        $payment = $this->getPayment();
        $quote->getPayment()
            ->setAdditionalInformation('payment_token', $gatewayToken);

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $quoteRepository->save($quote);

        $response = [
            'req_card_expiry_date' => '12-2021',
            'req_card_number' => 'xxxxxxxxxxxx1111',
            'req_card_type' => '001',
            'transaction_id' => '5185290602696187304103',
        ];
        $this->httpClient->method('placeRequest')
            ->willReturn($response);

        $orderId = $this->management->savePaymentInformationAndPlaceOrder($quote->getId(), $payment);
        self::assertNotEmpty($orderId);

        $transactions = $this->getPaymentTransactionList((int) $orderId);
        self::assertCount(1, $transactions, 'Only one transaction should be present.');

        /** @var TransactionInterface $transaction */
        $transaction = array_pop($transactions);
        self::assertEquals(
            'capture',
            $transaction->getTxnType(),
            'Order should contain only the "capture" transaction.'
        );
        self::assertFalse((bool) $transaction->getIsClosed(), 'Transaction should not be closed.');
    }

    /**
     * Creates mock of response validator.
     *
     * @return MockObject
     */
    private function getResponseValidatorMock(): MockObject
    {
        $validator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $result = $this->getMockForAbstractClass(ResultInterface::class);
        $result->method('isValid')
            ->willReturn(true);
        $validator->method('validate')
            ->willReturn($result);

        return $validator;
    }

    /**
     * Retrieves quote by provided order ID.
     *
     * @param string $reservedOrderId
     * @return CartInterface
     */
    private function getQuote(string $reservedOrderId): CartInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }

    /**
     * Creates Cybersource payment method.
     *
     * @return PaymentInterface
     */
    private function getPayment(): PaymentInterface
    {
        /** @var PaymentInterface $payment */
        $payment = $this->objectManager->create(PaymentInterface::class);
        $payment->setMethod('cybersource');

        return $payment;
    }

    /**
     * Get list of order transactions.
     *
     * @param int $orderId
     * @return TransactionInterface[]
     */
    private function getPaymentTransactionList(int $orderId): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('order_id', $orderId)
            ->create();

        /** @var TransactionRepositoryInterface $transactionRepository */
        $transactionRepository = $this->objectManager->get(TransactionRepositoryInterface::class);
        return $transactionRepository->getList($searchCriteria)
            ->getItems();
    }
}
