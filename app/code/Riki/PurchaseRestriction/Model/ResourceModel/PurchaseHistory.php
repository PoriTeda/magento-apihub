<?php

namespace Riki\PurchaseRestriction\Model\ResourceModel;

class PurchaseHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected $connectionName = 'sales';

    protected $_logger;

    protected $_skuToPurchasedQty = [];

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setMainTable('riki_purchase_history');
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        $connectionName = null
    ){
        $this->_logger = $logger;

      parent::__construct(
          $context,
          $connectionName
      );

        $this->connectionName = 'sales';
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param null $fromDate
     * @return mixed
     */
    public function sumPurchasedQtyByQuoteItem(\Magento\Quote\Model\Quote\Item $item, $fromDate = null){

        $sku = $item->getSku();

        if(!isset($this->_skuToPurchasedQty[$sku])){
            $this->_skuToPurchasedQty[$sku] = 0;

            $purchasedQtyByQuote = $this->getPurchasedQtyInfoByQuote($item->getQuote(), $fromDate);

            if(isset($purchasedQtyByQuote[$sku])){
                $this->_skuToPurchasedQty[$sku] = $purchasedQtyByQuote[$sku];
            }
        }

        return $this->_skuToPurchasedQty[$sku];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param null $fromDate
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPurchasedQtyInfoByQuote(\Magento\Quote\Model\Quote $quote, $fromDate = null){
        $result = [];

        $customerId = $quote->getCustomerId();

        if($customerId){

            $skus = [];

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach($quote->getAllVisibleItems() as $item){
                $skus[] = $item->getSku();
                $this->_skuToPurchasedQty[$item->getSku()] = 0;
            }

            $conn = $this->getConnection();

            $select = $conn->select()->from(
                $this->getMainTable(),
                ['sku', 'SUM(qty)']
            )->where(
                'customer_id = ?',
                $customerId
            )->where(
                'sku IN(?)',
                $skus
            );

            if($fromDate){
                $select->where(
                    'ordered_date >= ?',
                    $fromDate
                );
            }

            $select->group('sku');

            $result = $conn->fetchPairs($select);

            foreach($result as $sku =>  $qty){
                $this->_skuToPurchasedQty[$sku] = $qty;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertMultiple(\Magento\Sales\Model\Order $order){

        $customerId = $order->getCustomerId();

        $orderedDate = $order->getCreatedAt();

        $orderId = $order->getId();

        $insertData = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach($order->getAllVisibleItems() as $item){

            if ($this->isPurchasedByCustomer($item)) {
                $insertData[] = [
                    'customer_id'   =>  $customerId,
                    'order_id'   =>  $orderId,
                    'sku'   =>  $item->getSku(),
                    'qty'   =>  $item->getQtyOrdered(),
                    'ordered_date'   =>  $orderedDate
                ];
            }
        }


        if(count($insertData)){
            $conn = $this->getConnection();

            try{
                $conn->insertMultiple($this->getMainTable(), $insertData);
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }

        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isPurchasedByCustomer(\Magento\Sales\Model\Order\Item $item)
    {
        $buyRequest = $item->getBuyRequest();

        if (
            isset($buyRequest['options']['ampromo_rule_id']) ||
            $item->getData('prize_id') ||
            isset($buyRequest['options']['free_machine_item'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * delete record by order
     *
     * @param \Magento\Sales\Model\Order|int $orderId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByOrder($orderId){

        if($orderId instanceof \Magento\Sales\Model\Order){
            $orderId = $orderId->getId();
        }

        if($orderId){
            $conn = $this->getConnection();

            try{
                $conn->delete($this->getMainTable(), ['order_id=?' => $orderId]);
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * deduct qty
     *
     * @param $orderId
     * @param $productSku
     * @param $deductedQty
     * @return $this
     */
    public function deductQtyByOrderProduct($orderId, $productSku, $deductedQty){
        $conn = $this->getConnection();

        $bind = ['qty' =>  'GREATEST(qty - ' . (int)$deductedQty . ', 0)'];
        try{
            $conn->update($this->getMainTable(), $bind, ['order_id=?' => $orderId, 'sku=?' => $productSku]);
        }catch (\Exception $e){
            $this->_logger->critical($e);
        }

        return $this;
    }
}
