<?php
namespace Riki\Prize\Model;

use \Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class Prize extends \Magento\Framework\Model\AbstractModel implements \Riki\Prize\Api\Data\PrizeInterface
{
    const STATUS_WAITING = 0;
    const STATUS_DONE = 1;
    const STATUS_DONE_BY_MANUAL = 2;
    const STATUS_STOCK_SHORTAGE_ERROR = 3;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Riki\ShipLeadTime\Api\StockStateInterface
     */
    protected $shipLeadTimeStockState;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * Prize constructor.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->functionCache = $functionCache;
        $this->customerRepository    = $customerRepository;
        $this->quoteItemFactory      = $quoteItemFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipLeadTimeStockState = $shipLeadTimeStockState;
        $this->validateStockPointProduct = $validateStockPointProduct;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Prize\Model\ResourceModel\Prize::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getConsumerDbId()
    {
        return $this->getData(self::CONSUMER_DB_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $consumerDbId
     *
     * @return $this
     */
    public function setConsumerDbId($consumerDbId)
    {
        return $this->setData(self::CONSUMER_DB_ID, $consumerDbId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * {@inheritdoc}
     *
     * @param $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getWbs()
    {
        return $this->getData(self::WBS);
    }

    /**
     * {@inheritdoc}
     *
     * @param $wbs
     *
     * @return $this
     */
    public function setWbs($wbs)
    {
        return $this->setData(self::WBS, $wbs);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     *
     * @param $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getWinningDate()
    {
        return $this->getData(self::WINNING_DATE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $winningDate
     *
     * @return $this
     */
    public function setWinningDate($winningDate)
    {
        return $this->setData(self::WINNING_DATE, $winningDate);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getOrderNo()
    {
        return $this->getData(self::ORDER_NO);
    }

    /**
     * {@inheritdoc}
     *
     * @param $orderNo
     *
     * @return $this
     */
    public function setOrderNo($orderNo)
    {
        return $this->setData(self::ORDER_NO, $orderNo);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getCampaignCode()
    {
        return $this->getData(self::CAMPAIGN_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $campaignCode
     *
     * @return $this
     */
    public function setCampaignCode($campaignCode)
    {
        return $this->setData(self::CAMPAIGN_CODE, $campaignCode);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getMailSendDate()
    {
        return $this->getData(self::MAIL_SEND_DATE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $mailSendDate
     *
     * @return $this
     */
    public function setMailSendDate($mailSendDate)
    {
        return $this->setData(self::MAIL_SEND_DATE, $mailSendDate);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getOrmId()
    {
        return $this->getData(self::ORM_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $ormId
     *
     * @return $this
     */
    public function setOrmId($ormId)
    {
        return $this->setData(self::ORM_ID);
    }

    /**
     * Get status [value => label]
     *
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_WAITING => __('Waiting'),
            self::STATUS_DONE => __('Done'),
            self::STATUS_DONE_BY_MANUAL => __('Done by manual'),
            self::STATUS_STOCK_SHORTAGE_ERROR => __('Stock shortage error')
        ];
    }

    /**
     * Get product relation by sku
     *
     * @param bool $isForce
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct($isForce = false)
    {
        if (!$this->getSku()) {
            return null;
        }

        try {
            /** @var \Magento\Catalog\Model\Product $result */
            $result = $this->productRepository->get($this->getSku(), false, null, $isForce);
            return $result;
        } catch (\Exception $e) {
            $this->_logger->error(__('Winner Prize Product #%1: ' . $e->getMessage(), $this->getSku()));
            return null;
        }
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        if (!$this->getConsumerDbId()) {
            return null;
        }

        $cacheKey = [$this->getConsumerDbId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $query = $this->searchCriteriaBuilder
            ->addFilter('consumer_db_id', $this->getConsumerDbId())
            ->setPageSize(1)
            ->create();
        $result = $this->customerRepository->getList($query)->getItems() ?: null;
        if ($result) {
            $result = current($result);
        }

        $this->functionCache->store($result, $cacheKey);
        return $result;
    }

    /**
     * Check prize item can attach to order via stock quantity
     *
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function canAttach(\Magento\Quote\Model\Quote $quote = null)
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return false;
        }

        if ($product->getStatus() == ProductStatus::STATUS_DISABLED) {
            return false;
        }

        /**
         * additional logic for stock point order (do not need to validate for simulate flow)
         *      if free gift is not allowed for stock point, process it like out of stock item
         */
        if ($quote
            && !$quote instanceof \Riki\Subscription\Model\Emulator\Cart
            && $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)
        ) {
            /*prize product is not allowed for stock point order*/
            if (!$this->validateStockPointProduct->isProductAllowedStockPoint($product)) {
                return false;
            }
        }

        $unitQty = 1;
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $unitQty = (int)$product->getUnitQty()? $product->getUnitQty() : 1;
        }

        $availableQty = $this->shipLeadTimeStockState->checkAvailableQty($quote, $product->getSku(), intval($this->getData('qty')) * $unitQty);

        if ($availableQty >= $this->getData('qty')) {
            return true;
        }

        return false;
    }

    /**
     * Check if we can delete this winner prize
     *
     * @return bool
     */
    public function canDelete()
    {
        if (!$this->getId()) {
            return false;
        }
        /** @var \Magento\Quote\Model\Quote\Item $quote */
        $quote = $this->quoteItemFactory->create();
        $quote->load($this->getId(), 'prize_id');
        if ($quote->getId()) {
            return false;
        }
        return true;
    }

    /**
     * Validate winner prize
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        $errors = [];
        if (!$this->getProduct()) {
            $errors[] = __('Product %1 is not existed', $this->getData('sku'));
        }
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $filter */
        $filter = $this->searchCriteriaBuilder->addFilter('consumer_db_id', $this->getData('consumer_db_id'));
        $customers = $this->customerRepository->getList($filter->create());
        if (!$customers->getTotalCount()) {
            $errors[] = __('Customer %1 is not existed', $this->getData('consumer_db_id'));
        }
        if (!sizeof($errors)) {
            $total = $this->getResource()->prizeExisted($this);
            if ($total) {
                $errors[] = __('This prize is already existed');
            }
        }
        if (!sizeof($errors)) {
            return true;
        }
        return $errors;
    }
}
