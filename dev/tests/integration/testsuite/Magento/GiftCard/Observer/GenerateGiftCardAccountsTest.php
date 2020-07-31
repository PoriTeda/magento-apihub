<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Observer;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\GiftCard\Model\Giftcard;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductGiftCard;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenerateGiftCardAccountsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Tests the controller for declines
     *
     * @magentoDataFixture Magento/GiftCard/_files/giftcard_on_ordered_setting.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/order_with_gift_card.php
     */
    public function testGiftcardGeneratorOnOrderAfterSaveSetting()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_PENDING, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertEquals(
            ['fixture_code_2', 'fixture_code_3'],
            $productOptions['giftcard_created_codes']
        );
    }

    /**
     * Tests the controller for declines
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/giftcard_on_invoiced_setting.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/order_with_gift_card.php
     */
    public function testGiftcardGeneratorOnInvoiceAfterSaveSettingNoGenerate()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayNotHasKey('email_sent', $productOptions);
        $this->assertArrayNotHasKey('giftcard_created_codes', $productOptions);
    }

    /**
     * Tests that giftcard account codes are generated after invoice creation.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/invoice_with_gift_card.php
     */
    public function testGiftcardGeneratorOnInvoiceAfterSaveSettingGenerate()
    {
        $order = $this->getOrder();
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $giftcardSetting = $config->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $this->assertEquals(Item::STATUS_INVOICED, $giftcardSetting);
        /** @var Item $orderItem */
        $orderItem = $this->getGiftcardItem($order);
        $productOptions = $orderItem->getProductOptions();

        $this->assertArrayHasKey('email_sent', $productOptions);
        $this->assertArrayHasKey('giftcard_created_codes', $productOptions);
        $this->assertEquals('1', $productOptions['email_sent']);
        $this->assertEquals(2, count($productOptions['giftcard_created_codes']));
    }

    /**
     * Returns giftcard item from order.
     *
     * @param Order $order
     * @return OrderItemInterface|null
     */
    private function getGiftcardItem(Order $order)
    {
        foreach ($order->getItems() as $item) {
            if ($item->getProductType() === ProductGiftCard::TYPE_GIFTCARD) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get stored order
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->get(FilterBuilder::class);
        $filters = [
            $filterBuilder->setField(OrderInterface::INCREMENT_ID)
                ->setValue('100000001')
                ->create()
        ];

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilters($filters)
            ->create();

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $orders = $orderRepository->getList($searchCriteria)
            ->getItems();

        /** @var OrderInterface $order */
        return array_pop($orders);
    }
}
