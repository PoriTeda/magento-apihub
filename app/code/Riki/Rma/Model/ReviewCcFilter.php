<?php
namespace Riki\Rma\Model;

use Riki\Rma\Model\Config\Source\Rma\ReturnStatus;
use Magento\Payment\Model\Method\Free;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment;

class ReviewCcFilter
{
    /** @var \Magento\Rma\Model\Rma  */
    protected $rma;

    /** @var ReviewCc  */
    protected $reviewCcItem;

    /** @var \Riki\Rma\Helper\Data  */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $coreResource;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * ReviewCcFilter constructor.
     * @param \Magento\Rma\Model\Rma $rma
     * @param ReviewCc\Item $reviewCcItem
     * @param \Riki\Rma\Helper\Data $helper
     * @param \Magento\Framework\App\ResourceConnection $coreResource
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Rma\Model\Rma $rma,
        \Riki\Rma\Model\ReviewCc\Item $reviewCcItem,
        \Riki\Rma\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $coreResource,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->rma = $rma;
        $this->reviewCcItem = $reviewCcItem;
        $this->helper = $helper;
        $this->coreResource = $coreResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param array $ids
     * @return \Magento\Framework\DataObject[]
     */
    public function load($ids)
    {
        $result = [];
        $orderIds = [];

        // Get $rmaCollection
        $rmaCollection = $this->rma->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('order_id')
            ->addFieldToFilter('return_status', ReturnStatus::CREATED)
            ->addFieldToFilter('entity_id', ['in' => $ids]);

        $rmaCollection->getSelect()
            ->joinLeft(
                ['review_cc_item' => $this->coreResource->getTableName('riki_rma_review_cc_item')],
                '`main_table`.`entity_id` = `review_cc_item`.`rma_id`',
                null
            )
            ->where('`review_cc_item`.`rma_id` IS NULL');

        /** @var \Magento\Rma\Model\Rma $rma */
        foreach ($rmaCollection->getItems() as $rma) {
            $orderIds[$rma->getOrderId()][] = $rma;
        }

        if ($orderIds) {
            // Get $orderCollection
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('main_table.entity_id', ['in' => array_keys($orderIds)]);

            $orderCollection->getSelect()
                ->joinLeft(
                    ['sales_order_payment' => $this->coreResource->getTableName('sales_order_payment')],
                    '`main_table`.`entity_id` = `sales_order_payment`.`parent_id`',
                    'sales_order_payment.method as payment_method'
                );
            $orderCollection->addFieldToFilter(
                ['sales_order_payment.method', 'sales_order_payment.method'],
                [
                    ['eq' => Free::PAYMENT_METHOD_FREE_CODE],
                    ['eq' => Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE]
                ]
            );

            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection->getItems() as $order) {
                $paymentMethod = $order->getPaymentMethod();
                if ($paymentMethod == Free::PAYMENT_METHOD_FREE_CODE) {
                    foreach ($orderIds[$order->getEntityId()] as $rmaItem) {
                        $result[] = $rmaItem;
                    }
                } elseif ($paymentMethod == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                    $flag = true;
                    $shipments = $order->getShipmentsCollection();
                    /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                    foreach ($shipments as $shipment) {
                        if ($shipment->getData('shipment_status') != Shipment::SHIPMENT_STATUS_REJECTED) {
                            $flag = false;
                            break;
                        }
                    }

                    if ($flag) {
                        foreach ($orderIds[$order->getEntityId()] as $rmaItem) {
                            $result[] = $rmaItem;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function doMatchCondition(\Magento\Rma\Model\Rma $rma)
    {
        $order = $this->helper->getRmaOrder($rma);

        if ($order->getPayment() &&
            $paymentMethod = $order->getPayment()->getMethod()
        ) {
            if ($paymentMethod == \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE) {
                return true;
            } elseif ($paymentMethod == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                $shipments = $order->getShipmentsCollection()
                    ->addFieldToFilter('shipment_status', ['neq' => \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED]);

                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                foreach ($shipments as $shipment) {
                    if ($shipment->getData('shipment_status') != \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }
}
