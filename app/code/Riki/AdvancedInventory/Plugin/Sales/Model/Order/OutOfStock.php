<?php
namespace Riki\AdvancedInventory\Plugin\Sales\Model\Order;

class OutOfStock
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock\Order
     */
    protected $outOfStockOrderHelper;

    /**
     * @var \Riki\AdvancedInventory\Observer\OosSubmitAfter
     */
    protected $oosSubmitAfterObserver;

    /**
     * OutOfStock constructor.
     *
     * @param \Riki\AdvancedInventory\Observer\OosSubmitAfter $oosSubmitAfterObserver
     * @param \Riki\AdvancedInventory\Helper\OutOfStock\Order $outOfStockOrderHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\OosSubmitAfter $oosSubmitAfterObserver,
        \Riki\AdvancedInventory\Helper\OutOfStock\Order $outOfStockOrderHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Riki\Framework\Helper\Search $searchHelper
    ) {
        $this->oosSubmitAfterObserver = $oosSubmitAfterObserver;
        $this->outOfStockOrderHelper = $outOfStockOrderHelper;
        $this->searchHelper = $searchHelper;
        $this->outOfStockRepository = $outOfStockRepository;
    }


    /**
     * Process payment
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return \Magento\Sales\Model\Order
     */
    public function afterAfterSave(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        if (!$result->dataHasChangedFor('status')) {
            return $result;
        }

        // no apply for order which just checkout submit
        if ($this->oosSubmitAfterObserver->getIsSubmittedOrder($result->getId())) {
            return $result;
        }

        $outOfStocks = $this->searchHelper
            ->getByOriginalOrderId($result->getId())
            ->getAll()
            ->execute($this->outOfStockRepository);
        foreach ($outOfStocks as $outOfStock) {
            $this->outOfStockOrderHelper->processCvsPayment($outOfStock);
            $this->outOfStockOrderHelper->processPaygentPayment($outOfStock);
        }

        return $result;
    }

    /**
     * Restrict Free Machine out of stock not able ship
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Closure $proceed
     * @return bool|mixed
     */
    public function aroundCanShip(
        \Magento\Sales\Model\Order $subject,
        \Closure $proceed
    ) {
        $machineOOS = $this->searchHelper
            ->getByOriginalOrderId($subject->getId())
            ->getByGeneratedOrderId(new \Zend_Db_Expr('NULL'), 'is')
            ->getByMachineSkuId(new \Zend_Db_Expr('NOT NULL'), 'is')
            ->getOne()
            ->execute($this->outOfStockRepository);
        if ($machineOOS) {
            return false;
        }

        return $proceed();
    }
}

