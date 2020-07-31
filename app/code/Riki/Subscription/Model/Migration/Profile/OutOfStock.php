<?php
namespace Riki\Subscription\Model\Migration\Profile;

class OutOfStock extends \Magento\Framework\DataObject
{
    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $oosRepository;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockManagementInterface
     */
    protected $oosManagement;

    /**
     * @var \Riki\Subscription\Model\Migration\Profile\OutOfStock\ItemFactory
     */
    protected $oosItemFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * OutOfStock constructor.
     *
     * @param \Riki\AdvancedInventory\Api\OutOfStockManagementInterface $oosManagement
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository
     * @param OutOfStock\ItemFactory $oosItemFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param array $data
     */
    public function __construct(
        \Riki\AdvancedInventory\Api\OutOfStockManagementInterface $oosManagement,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository,
        \Riki\Subscription\Model\Migration\Profile\OutOfStock\ItemFactory $oosItemFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        array $data = []
    ) {
        $this->oosManagement = $oosManagement;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteFactory = $quoteFactory;
        $this->oosRepository = $oosRepository;
        $this->oosItemFactory = $oosItemFactory;
        $this->productRepository = $productRepository;
        $this->functionCache = $functionCache;
        $this->profileRepository = $profileRepository;
        parent::__construct( $data);
    }

    /**
     * Queue a item to waiting migrate
     *
     * @param OutOfStock\Item $item
     *
     * @return bool
     */
    public function queue(\Riki\Subscription\Model\Migration\Profile\OutOfStock\Item $item)
    {
        $this->queue[$item->getProfile()->getProfileId()][$item->getData('order_times')][] = $item->getId();

        return true;
    }

    /**
     * Migrate data
     *
     * @return bool
     */
    public function migrate()
    {
        $oosQuote = $this->oosManagement->getOosQuote();

        foreach ($this->queue as $profileId => $orderTimes) {
            foreach ($orderTimes as $cartData) {
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = null;
                foreach ($cartData as $itemId) {
                    /** @var \Riki\Subscription\Model\Migration\Profile\OutOfStock\Item $item */
                    $item = $this->oosItemFactory->create()->load($itemId);
                    if (!$item->getId()) {
                        continue;
                    }

                    if (!$quote) {
                        $quote = $item->generateQuote();
                        if (!$quote instanceof \Magento\Quote\Model\Quote) {
                            continue;
                        }

                        if (!$quote->getId()) {
                            $quote->save();
                        }
                    }

                    $quoteItem = $item->generateQuoteItem();
                    if (!$quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                        continue;
                    }

                    if (!$quoteItem->getId()) {
                        $quoteItem->setQuote($oosQuote);
                        $quoteItem->save();
                    }

                    $oos = $this->oosRepository->createFromArray([
                        'quote_id' => $quote->getId(),
                        'original_order_id' => 0,
                        'product_id' => $quoteItem->getData('product_id'),
                        'qty' => $quoteItem->getQty(),
                        'product_sku' => $quoteItem->getSku(),
                        'subscription_profile_id' => $profileId,
                        'store_id' => $quoteItem->getStoreId(),
                        'quote_item_id' => $quoteItem->getId()
                    ]);

                    $this->oosRepository->save($oos);
                }
            }
        }

        return true;
    }
}