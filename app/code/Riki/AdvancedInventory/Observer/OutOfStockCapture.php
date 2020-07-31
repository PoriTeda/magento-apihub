<?php
namespace Riki\AdvancedInventory\Observer;

class OutOfStockCapture implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var bool
     */
    protected $isEnabled;

    /**
     * @var array
     */
    protected $outOfStocks;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * OutOfStockCapture constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
    ) {
        $this->scopeHelper = $scopeHelper;
        $this->logger = $logger;
        $this->outOfStockRepository = $outOfStockRepository;

        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->outOfStocks = [];
        $this->setIsEnabled(true);
    }

    /**
     * Get isEnabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isEnabled
     *
     * @param $isEnabled
     *
     * @return bool
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
        return $this->isEnabled;
    }


    /**
     * Get captured out of stocks
     *
     * @param  $quoteId
     *
     * @return array
     */
    public function getOutOfStocks($quoteId = null)
    {
        if ($quoteId) {
            return isset($this->outOfStocks[$quoteId])
                ? $this->outOfStocks[$quoteId]
                : [];
        }

        return $this->outOfStocks;
    }

    /**
     * Clean captured out of stocks
     *
     * @param null $quoteId
     *
     * @return $this
     */
    public function cleanOutOfStocks($quoteId = null)
    {
        if ($quoteId && isset($this->outOfStocks[$quoteId])) {
            unset($this->outOfStocks[$quoteId]);
        }

        return $this;
    }


    /**
     * Capture out of stock situations
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->getIsEnabled()) {
            return;
        }

        $scope = \Riki\AdvancedInventory\Model\Queue\OosConsumer::class . '::execute';
        if ($this->scopeHelper->isInFunction($scope)) {
            return;
        }

        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'capture') !== 0) {
                continue;
            }

            $this->$method($observer);
        }
    }

    /**
     * Capture free machine
     *
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function captureFreeMachine(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }

        $productData = $observer->getData('product_data');
        if (!isset($productData['product_id']) || !isset($productData['sku']) || !isset($productData['machine_sku'])) {
            return;
        }

        if (isset($productData['type_id']) && $productData['type_id'] == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            return;
        }

        $machineSku = $productData['machine_sku'];
        if (!$machineSku instanceof \Riki\SubscriptionMachine\Model\MachineSkus) {
            return;
        }

        if ($quote->getData('profile_id')) { // generate after profile
            throw new \Magento\Framework\Exception\LocalizedException(__('Free attachment machine was out of stock'));
        }

        // capture on checkout process on first time
        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $productData['product_id'],
            'product_sku' => $productData['sku'],
            'qty' => 1, // machine only attach one
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
            'machine_sku_id' => $machineSku->getId()
        ];
        if ($data != array_filter($data, 'strlen')) {
            return;
        }

        $key = __METHOD__ . implode('_', $data);
        if (isset($this->outOfStocks[$data['quote_id']][$key])) {
            return;
        }

        $data['machine_wbs'] = $machineSku->getData('machine_wbs');

        $this->outOfStocks[$data['quote_id']][$key] = $this->outOfStockRepository->createFromArray($data);

    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function captureFreeGift(\Magento\Framework\Event\Observer $observer)
    {
        $data = [
            'quote_id' => $observer->getData('quote_id'),
            'product_id' => $observer->getData('product_id'),
            'product_sku' => $observer->getData('product_sku'),
            'qty' => $observer->getData('qty'),
            'salesrule_id' => $observer->getData('salesrule_id'),
            'customer_id' => $observer->getData('customer_id'),
            'store_id' => $observer->getData('store_id')
        ];
        if ($data != array_filter($data, 'strlen')) {
            return;
        }

        $key = __METHOD__ . implode('_', $data);
        if (isset($this->outOfStocks[$data['quote_id']][$key])) {
            return;
        }

        $this->outOfStocks[$data['quote_id']][$key] = $this->outOfStockRepository->createFromArray($data);
    }

    /**
     * Capture out of stock on subscription
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \Exception
     *
     * @return void
     */
    public function captureOnSubscription(\Magento\Framework\Event\Observer $observer)
    {
        $profileId = $observer->getData('profile_id');
        $product = $observer->getData('product');
        $productData = $observer->getData('product_data');
        $quote = $observer->getData('quote');

        if (!$profileId
            || !($product instanceof \Magento\Catalog\Model\Product)
            || !isset($productData['qty'])

        ) {
            return;
        }

        if (!$quote->getId()) {
            try {
                $quote->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
                throw $e;
            }
        }

        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $product->getId(),
            'product_sku' => $product->getSku(),
            'qty' => $productData['qty'],
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
        ];
        $key = __METHOD__ . implode('_', $data);
        if (isset($this->outOfStocks[$data['quote_id']][$key])) {
            return;
        }

        $data['product'] = $product;
        $this->outOfStocks[$data['quote_id']][$key] = $this->outOfStockRepository->createFromArray($data);
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function capturePrize(\Magento\Framework\Event\Observer $observer)
    {
        $data = [
            'prize_id' => $observer->getData('prize_id'),
            'qty' => $observer->getData('qty'),
            'product_id' => $observer->getData('product_id'),
            'product_sku' => $observer->getData('product_sku'),
            'quote_id' => $observer->getData('quote_id'),
            'customer_id' => $observer->getData('customer_id'),
            'store_id' => $observer->getData('store_id')
        ];
        if ($data != array_filter($data, 'strlen')) {
            return;
        }

        $key = __METHOD__ . implode('_', $data);
        if (isset($this->outOfStocks[$data['quote_id']][$key])) {
            return;
        }

        $data['prize_wbs'] = $observer->getData('prize_wbs');

        $this->outOfStocks[$data['quote_id']][$key] = $this->outOfStockRepository->createFromArray($data);
    }
}