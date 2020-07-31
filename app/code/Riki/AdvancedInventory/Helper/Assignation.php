<?php
namespace Riki\AdvancedInventory\Helper;

use Magento\Framework\App\Helper\Context;
use Riki\AdvancedInventory\Api\StockRepositoryInterface;
use Riki\Sales\Helper\Address;

class Assignation extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_DEFAULT_FO_POS = 'advancedinventory_riki_inventory/stock_fo/default_fo_pos';
    const CONFIG_DEFAULT_STOCK_POINT_POS = 'advancedinventory_riki_inventory/stock_fo/default_stock_point_pos';
    const DEFAULT_STOCK_POINT_POS = 3;
    const DEFAULT_FO_POS = 2;
    const ENV_FO = 'FO';

    protected $placesByOrder = [];
    protected $placesByQuote = [];

    /** @var \Magento\Directory\Model\RegionFactory  */
    protected $regionFactory;

    /** @var \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface  */
    protected $pointOfSaleRepository;

    /**
     * @var StockRepositoryInterface
     */
    protected $stockRepositoryInterface;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /** @var Address  */
    protected $salesAddressHelper;

    /** @var \Riki\PointOfSale\Helper\Data  */
    protected $pointOfSaleHelper;

    /**
     * Assignation constructor.
     * @param Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param StockRepositoryInterface $stockRepositoryInterface
     * @param Address $salesAddressHelper
     * @param \Riki\PointOfSale\Helper\Data $pointOfSaleHelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\AdvancedInventory\Api\StockRepositoryInterface $stockRepositoryInterface,
        \Riki\Sales\Helper\Address $salesAddressHelper,
        \Riki\PointOfSale\Helper\Data $pointOfSaleHelper
    ) {

        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pointOfSaleRepository = $pointOfSaleHelper->getPointOfSaleRepository();
        $this->regionFactory = $regionFactory;
        $this->stockRepositoryInterface = $stockRepositoryInterface;
        $this->salesAddressHelper = $salesAddressHelper;
        $this->pointOfSaleHelper = $pointOfSaleHelper;

        parent::__construct($context);
    }

    /**
     * @return \Riki\PointOfSale\Helper\Data
     */
    public function getPointOfSaleHelper()
    {
        return $this->pointOfSaleHelper;
    }

    /**
     * @return Address
     */
    public function getSaleAddressHelper()
    {
        return $this->salesAddressHelper;
    }

    /**
     * @return StockRepositoryInterface
     */
    public function getStockRepositoryInterface()
    {
        return $this->stockRepositoryInterface;
    }

    /**
     * @return \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface
     */
    public function getPlaceRepository()
    {
        return $this->pointOfSaleRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getAvailablePlacesByOrder(\Magento\Sales\Model\Order $order)
    {

        if (!isset($this->placesByOrder[$order->getId()])) {
            $this->placesByOrder[$order->getId()] = [];

            $places = $this->pointOfSaleHelper->getPlacesByOrder($order);

            foreach ($places as $pos) {
                $this->placesByOrder[$order->getId()][$pos->getId()] = $pos;
            }
        }

        return $this->placesByOrder[$order->getId()];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return mixed
     */
    public function getAvailablePlacesByQuote(\Magento\Quote\Model\Quote $quote)
    {

        $quoteId = $quote->getId();

        if (!isset($this->placesByQuote[$quoteId])) {
            $this->placesByQuote[$quoteId] = [];

            $places = $this->pointOfSaleHelper->getPlacesByQuote($quote);

            foreach ($places as $pos) {
                $this->placesByQuote[$quoteId][$pos->getId()] = $pos;
            }
        }

        return $this->placesByQuote[$quoteId];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function isAllowMultipleAssignation(\Magento\Sales\Model\Order $order)
    {
        $allowMultipleAssign = $this->scopeConfig->getValue(
            "advancedinventory/settings/multiple_assignation_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return $allowMultipleAssign;
    }

    /**
     * Get default pos( warehouse) for assignation
     *
     * @return int|mixed
     */
    public function getDefaultPosForFo()
    {
        $placeId = $this->scopeConfig->getValue(
            self::CONFIG_DEFAULT_FO_POS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        if (!$placeId) {
            $placeId = self::DEFAULT_FO_POS;
        }

        return explode(',', $placeId);
    }

    /**
     * Get default pos( warehouse) for stock point
     *
     * @return int|mixed
     */
    public function getDefaultPosForStockPoint()
    {
        $placeId = $this->scopeConfig->getValue(
            self::CONFIG_DEFAULT_STOCK_POINT_POS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        if (!$placeId) {
            $placeId = self::DEFAULT_STOCK_POINT_POS;
        }

        return $placeId;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function canAssignOrder(\Magento\Sales\Model\Order $order)
    {
        return !$order->getData(\Riki\AdvancedInventory\Model\Assignation::SKIP_ORDER_ASSIGN_FLAG);
    }
}
