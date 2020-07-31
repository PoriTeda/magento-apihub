<?php

namespace Riki\AdvancedInventory\Observer;

use Magento\Sales\Model\Order;

class DisableOosGeneration implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->loggerHelper = $loggerHelper;
        $this->outOfStockRepository = $outOfStockRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getState() == Order::STATE_CLOSED
            || $order->getState() == Order::STATE_CANCELED) {
            $query = $this->searchCriteriaBuilder
                ->addFilter('original_order_id', $order->getId())
                ->addFilter('generated_order_id', new \Zend_Db_Expr('NULL'), 'is')
                ->create();
            $outOfStocks = $this->outOfStockRepository->getList($query)->getItems();
            //Find not generated OOS
            $this->outOfStockRepository->getList($query);

            if ($outOfStocks) {
                $this->loggerHelper->getOosLogger()
                    ->info("Main order #" . $order->getIncrementId() .
                        " is Canceled/Closed - process disable related oos entities.");

                /** @var \Riki\AdvancedInventory\Model\OutOfStock $oosEntity */
                foreach ($outOfStocks as $oosEntity) {
                    //Set queue_execute = 3 , generated_order_id = 0
                    try {
                        $oosEntity->setData('queue_execute', 3)
                            ->setData('generated_order_id', 0);
                        $this->outOfStockRepository->save($oosEntity);
                        //Log info
                        $this->loggerHelper->getOosLogger()
                            ->info("Disable Oos entity #" . $oosEntity->getId() . " due to main order is canceled/closed");
                    } catch (\Exception $e) {
                        $this->loggerHelper->getOosLogger()
                            ->critical("Cannot disable oos entity " . $oosEntity->getId() . " - message " . $e->getMessage());
                    }
                }
            }
        }

        return $this;

    }
}
