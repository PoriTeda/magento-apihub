<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Model;

use Braintree\Result\Successful;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class for testing QuoteManagement model with SalesArchive.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Place order using payment action "Authorize and Capture" and check that this order
     * is not present in order archive grid.
     *
     * @magentoConfigFixture current_store sales/magento_salesarchive/active 0
     * @magentoConfigFixture current_store payment/braintree/active 1
     * @magentoConfigFixture current_store payment/braintree/payment_action authorize_capture
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
     *
     * @return void
     */
    public function testPlacedOrderIsNotInArchiveGrid(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $objectManager->addSharedInstance(
            $this->getHttpClientMock(),
            \Magento\Braintree\Gateway\Http\Client\TransactionSale::class
        );

        $quote = $this->getQuote('test01');
        $quote->getPayment()->setMethod('braintree');

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $objectManager->get(CartRepositoryInterface::class);
        $quote->collectTotals();
        $quoteRepository->save($quote);

        /** Execute SUT */
        /** @var \Magento\Quote\Api\CartManagementInterface $model */
        $cartManagement = $objectManager->create(\Magento\Quote\Api\CartManagementInterface::class);
        /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $orderId = $cartManagement->placeOrder($quote->getId());
        $order = $orderRepository->get($orderId);

        /** Check if SUT caused expected effects */
        $orderItems = $order->getItems();
        $this->assertCount(3, $orderItems);

        /** @var \Magento\SalesArchive\Model\ResourceModel\Archive $archive */
        $archive = $objectManager->create(\Magento\SalesArchive\Model\ResourceModel\Archive::class);

        $this->assertFalse(
            $archive->isOrderInArchive($order->getEntityId()),
            'Order must not be present in orders archive'
        );
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
        $searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }

    /**
     * Get HTTP Client for payment.
     *
     * @return MockObject
     */
    private function getHttpClientMock(): MockObject
    {
        $transaction = $this->getMockBuilder(\Braintree\Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transaction->creditCardDetails = new \StdClass();
        $transaction->creditCardDetails->token = null;
        $transaction->status = 'submitted_for_settlement';
        $transaction->id = 'AFDCVG';
        $transaction->creditCard = [
            'last4'           => '4444',
            'expirationMonth' => '12',
            'expirationYear'  => '2020',
            'cardType'        => 'visa',
        ];
        $successTrue = new Successful();
        $successTrue->success = true;
        $successTrue->transaction = $transaction;

        $response = [
            'object' => $successTrue,
        ];
        $client = $this->getMockBuilder(\Magento\Braintree\Gateway\Http\Client\TransactionSale::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('placeRequest')->willReturn($response);

        return $client;
    }
}
