<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

/** @var Quote $quote */
$quote = $objectManager->create(Quote::class);

require __DIR__ . '/../../../Magento/Multishipping/Fixtures/shipping_address_list.php';
require __DIR__ . '/../../../Magento/Multishipping/Fixtures/billing_address.php';
require __DIR__ . '/payment_method.php';
require __DIR__ . '/../../../Magento/Multishipping/Fixtures/items.php';

$store = $storeManager->getStore();
$quote->setReservedOrderId('multishipping_quote_id')
    ->setStoreId($store->getId())
    ->setCustomerEmail('customer001@test.com');

/** @var CartRepositoryInterface $quoteRepository */
$quoteRepository = $objectManager->get(CartRepositoryInterface::class);
$quote->collectTotals();
$quoteRepository->save($quote);

$items = $quote->getAllItems();
$addressList = $quote->getAllShippingAddresses();

foreach ($addressList as $key => $address) {
    $item = $items[$key];
    // set correct quantity per shipping address
    $item->setQty(1);
    $address->setTotalQty(1);
    $address->addItem($item);
}

// assign virtual product to the billing address
$billingAddress = $quote->getBillingAddress();
$virtualItem = $items[sizeof($items) - 1];
$billingAddress->setTotalQty(1);
$billingAddress->addItem($virtualItem);

// need to recollect totals
$quote->setTotalsCollectedFlag(false);
$quote->collectTotals();
$quoteRepository->save($quote);
