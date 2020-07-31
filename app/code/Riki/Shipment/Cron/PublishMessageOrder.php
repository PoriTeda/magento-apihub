<?php

/**
 * Shipment Cron
 *
 * PHP version 7
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Cron;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Shipment\Api\ShipmentBuilder\ProfileBuilderInterface;
use Riki\Shipment\Model\Order\ShipmentBuilder\ProfileOrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class PublishMessageOrder
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class PublishMessageOrder
{
    /**
     * @var PublisherInterface
     */
    protected $publisher;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var CollectionFactory
     */
    protected $orderRepository;
    /**
     * @var ProfileBuilderInterface
     */
    protected $profileBuilder;
    /**
     * @var ProfileOrderFactory
     */
    protected $profileOrderFactory;

    const  ASSIGNATION_EMPTY = '{"place_ids":""}';

    /**
     * PublishMessageOrder constructor.
     * @param PublisherInterface $publisher
     * @param \Riki\Shipment\Logger\LoggerPublishMessage $logger
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $orderRepository
     * @param ProfileBuilderInterface $profileBuilder
     * @param ProfileOrderFactory $profileOrderFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        \Riki\Shipment\Logger\LoggerPublishMessage $logger,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        CollectionFactory $orderRepository,
        ProfileBuilderInterface $profileBuilder,
        ProfileOrderFactory $profileOrderFactory
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;
        $this->orderRepository = $orderRepository;
        $this->profileBuilder = $profileBuilder;
        $this->profileOrderFactory = $profileOrderFactory;
    }
    /**
     * @return $this
     */
    public function execute()
    {
        $originDate =  $this->timeZone->formatDateTime(
            $this->dateTime->gmtDate(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
        $today = $this->dateTime->gmtDate('Y-m-d', $originDate);
        $publishedDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderRepository->create();
        $orderCollection->addFieldToFilter(
            [
                'min_export_date',
                'min_export_date',
                'order_channel'
            ],
            [
                ['lteq' => $today],
                ['null' => true],
                ['eq' => \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API]
            ]
        );
        $orderCollection->addFieldToFilter(
            'assignation',
            ['neq'=> self::ASSIGNATION_EMPTY]
        );
        /* only create shipment for order with assignation not null */
        $orderCollection->addFieldToFilter(
            'assignation',
            ['neq'=> null]
        );
        /*only create shipment for order with status is not shipped*/
        $orderCollection->addFieldToFilter(
            'status',
            ['in'=> [
                OrderStatus::STATUS_ORDER_NOT_SHIPPED
            ]
            ]
        );
        $orderCollection->join(
            'sales_order_item',
            'main_table.entity_id = sales_order_item.order_id',
            ''
        );
        $orderCollection->addFieldToFilter(
            'sales_order_item.qty_shipped',
            ['eq'=> 0]
        );
        /*filter order - do not send to queue*/
        $orderCollection->addFieldToFilter(
            'published_message',
            ['eq' => 0]
        );

        $orderCollection->addFieldToFilter(
            'is_preorder',
            ['neq' => 1]
        );

        $orderCollection->addFieldToFilter(
            \Riki\Subscription\Helper\Order\Data::IS_INCOMPLETE_GENERATE_PROFILE_ORDER,
            ['neq'  =>  1]
        );

        $orderCollection->setOrder('entity_id', 'DESC');
        /*join table to get is_preorder flag for this order (this is not sales_order column)*/
        $orderCollection->join(
            'riki_preorder_order_preorder',
            'main_table.entity_id = riki_preorder_order_preorder.order_id',
            ''
        );
        $orderCollection->getSelect()->joinLeft(
            ['sales_shipment'=> 'sales_shipment'],
            'main_table.entity_id = sales_shipment.order_id',
            []
        );
        $orderCollection->getSelect()->distinct('true');
        $this->logger->info(__('Query to get shipments to publish into queue:'));
        $this->logger->info($orderCollection->getSelect());
        if ($orderCollection->getSize()) {
            foreach ($orderCollection as $_order) {
                if ($_order->canShip()) {
                    try {
                        $dateCompare = $this->dateDiff($_order->getData('published_date'), $publishedDate);
                        if (!$_order->getData('published_date') ||
                            ($_order->getData('published_date') && $dateCompare >1)
                        ) {
                            $profileCreateOrder =  $this->profileOrderFactory->create();
                            $profileCreateOrder->setOrderId($_order->getId());
                            $profileItemBuilder = $this->profileBuilder->setItems([$profileCreateOrder]);
                            $this->publisher->publish('shipment.creator', $profileItemBuilder);
                            $this->updateOrderQueueStatus(
                                $_order,
                                \Riki\Shipment\Model\Order\ShipmentBuilder\Creator::SHIPMENT_CREATOR_WAITING_HANDLED,
                                $publishedDate
                            );
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws \Exception
     */
    private function updateOrderQueueStatus(
        \Magento\Sales\Model\Order $order,
        $messageStatus,
        $originDate
    ) {
        try {
            $order->setData('published_message', $messageStatus);
            $order->setData('published_date', $originDate);
            $order->getResource()->saveAttribute($order, ['published_message','published_date']);
            $this->logger->info("Push order ".$order->getId()." into shipment creator queue");
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $start
     * @param $end
     * @return float
     */
    public function dateDiff($start, $end)
    {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        $diff = $end_ts - $start_ts;
        return round($diff / 3600);
    }
}
