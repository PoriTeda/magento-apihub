<?php
namespace Riki\SalesRule\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckoutSubmitAllAfter implements ObserverInterface
{
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_ruleCollectionFactory;

    /**
     * @var \Riki\SalesRule\Model\ResourceModel\OrderSalesRule
     */
    protected $_orderSalesRuleResource;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection
     * @param \Riki\SalesRule\Model\ResourceModel\OrderSalesRule $orderSalesRule
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection,
        \Riki\SalesRule\Model\ResourceModel\OrderSalesRule $orderSalesRule,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_ruleCollectionFactory = $ruleCollection;
        $this->_orderSalesRuleResource = $orderSalesRule;
        $this->_logger = $logger;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /*Simulate doesn't need to update Sale Rule */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        $rules = $this->getRuleIdsByOrder($order);

        if($rules){
            try{
                //Don't apply any promotion when create machine order
                if ($order->getOrderChannel() != 'machine_maintenance')
                {
                    $this->_orderSalesRuleResource->saveOrderSalesRule($order, $rules);
                }
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Framework\DataObject[]|null
     */
    protected function getRuleIdsByOrder(\Magento\Sales\Model\Order $order){
        $ruleIds = $order->getAppliedRuleIds();

        if($ruleIds){
            $ruleIds = explode(',', $ruleIds);
        }else{
            $ruleIds = [];
        }

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach($order->getAllItems() as $item){

            if($item->getAppliedRuleIds())
                $ruleIds += explode(',', $item->getAppliedRuleIds());
        }

        if(count($ruleIds)){
            /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection $collection */
            $collection = $this->_ruleCollectionFactory->create();

            $collection->addFieldToSelect(['rule_id', 'name', 'description'])
                ->addFieldToFilter('rule_id', ['in' =>  $ruleIds]);

            return $collection->getItems();
        }

        return null;
    }
}