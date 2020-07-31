<?php

namespace Riki\DeliveryType\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Riki\DeliveryType\Model\Delitype as Dtype;

class SaveDeliveryDate implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Api\Data\ShippingInformationInterface
     */
    protected $_addressInformation;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_managerInterface;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;

    /** @var \Magento\Framework\App\State  */
    protected $state;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate $deliveryDateModel
     */
    protected $deliveryDateModel;

    protected $_rikiSalesHelper;

    /**
     * @var boolean
     */
    protected $_skipDDate = false;

    /**
     * SaveDeliveryDate constructor.
     *
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $managerInterface
     * @param \Magento\Framework\App\RequestInterface $request
     * @param Logger $loggerInterface
     */
    public function __construct(
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\State $state,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDateModel,
        Logger $loggerInterface

    ) {
        $this->_addressInformation = $addressInformation;
        $this->_checkoutSession = $checkoutSession;
        $this->_managerInterface = $managerInterface;
        $this->_request = $request;
        $this->state = $state;
        $this->deliveryDateModel = $deliveryDateModel;
        $this->logger = $loggerInterface;
    }

    /**
     * Observer save delivery date to order item (single checkout)
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|bool
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if(!$order->getId()) {
            return false;
        }
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        $courseId = $quote->getData('riki_course_id');
        if (!$courseId && \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE == $order->getPayment()->getMethod()) {
            $this->_skipDDate = true;
        }

        if($this->state->getAreaCode() != \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {

            $this->_checkoutSession->unsDeliveryDateTmp();
        } else {
            //get multiple delivery date from params post when order created from back end
            $deliveryDate = $this->_request->getParams();

            $this->_saveDeliveryDateOrderBackend($order,$deliveryDate);
        }
        if ($this->_skipDDate) {
            $this->removeDDate($quote, $order);
        }
        return $this;
    }

    /**
     * Unset delivery date for CSV payment with SPOT order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\Order $order
     * @return $this;
     */
    private function removeDDate($quote, $order)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItem->setDeliveryDate(null);
        }
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            $orderItem->setDeliveryDate(null);
        }
        return $this;
    }
    /**
     * Save Delivery date for order created by back-end admin
     *
     * @param $order
     * @param $deliveryDate
     * @return bool
     */
    private function _saveDeliveryDateOrderFrontend($order,$deliveryDate)
    {
        $ddateChilled = $dtimeChilled = $ddateCosmetic = $dtimeCosmetic = $ddateCold = $dtimeCold = $ddateCoolNormalDm = $dtimeCoolNormalDm = null;

        $listDD = \Zend_Json::decode($deliveryDate);
        foreach ($listDD as $dd ) {
            if(isset($dd['deliveryName']) && $dd['deliveryName'] == Dtype::CHILLED) {
                if(isset($dd['deliveryDate'])) {
                    $ddateChilled = $dd['deliveryDate'];
                }
                if(isset($dd['deliveryTime'])) {
                    $dtimeChilled = $dd['deliveryTime'];
                }

            } else if(isset($dd['deliveryName']) && $dd['deliveryName'] == Dtype::COSMETIC) {
                if(isset($dd['deliveryDate'])) {
                    $ddateCosmetic = $dd['deliveryDate'];
                }
                if(isset($dd['deliveryTime'])) {
                    $dtimeCosmetic = $dd['deliveryTime'];
                }

            } else if(isset($dd['deliveryName']) && $dd['deliveryName']== Dtype::COLD) {
                if(isset($dd['deliveryDate'])) {
                    $ddateCold = $dd['deliveryDate'];
                }
                if(isset($dd['deliveryTime'])) {
                    $dtimeCold = $dd['deliveryTime'];
                }

            } else {
                if(isset($dd['deliveryDate'])) {
                    $ddateCoolNormalDm = $dd['deliveryDate'];
                }
                if(isset($dd['deliveryTime'])) {
                    $dtimeCoolNormalDm = $dd['deliveryTime'];
                }
            }
        }

        $orderItems = $order->getAllItems();
        // save delivery date for item
        $this->_saveDDToOrderItem($orderItems,
            $ddateChilled, $dtimeChilled,
            $ddateCosmetic, $dtimeCosmetic,
            $ddateCold, $dtimeCold,
            $ddateCoolNormalDm, $dtimeCoolNormalDm
        );

        return true;
    }

    /**
     * Save Delivery date for order created by front-end checkout
     *
     * @param $order
     * @param $deliveryDate
     * @return bool
     */
    private function _saveDeliveryDateOrderBackend($order,$deliveryDate)
    {
        $ddateChilled = $dtimeChilled = $ddateCosmetic = $dtimeCosmetic = $ddateCold = $dtimeCold = $ddateCoolNormalDm = $dtimeCoolNormalDm = null;

        //Set chilled
        if(isset($deliveryDate['delivery-date-chilled'])) {
            $ddateChilled = $deliveryDate['delivery-date-chilled'];
        }
        if(isset($deliveryDate['delivery-time-chilled'])) {
            $dtimeChilled = $deliveryDate['delivery-time-chilled'];
        }
        //Set cosmetic
        if(isset($deliveryDate['delivery-date-cosmetic'])) {
            $ddateCosmetic = $deliveryDate['delivery-date-cosmetic'];
        }
        if(isset($deliveryDate['delivery-time-cosmetic'])) {
            $dtimeCosmetic = $deliveryDate['delivery-time-cosmetic'];
        }
        //Set Cold
        if(isset($deliveryDate['delivery-date-cold'])) {
            $ddateCold = $deliveryDate['delivery-date-cold'];
        }
        if(isset($deliveryDate['delivery-time-cold'])) {
            $dtimeCold = $deliveryDate['delivery-time-cold'];
        }
        //Set cool - nomarl - direct mail
        if(isset($deliveryDate['delivery-date-CoolNormalDm'])) {
            $ddateCoolNormalDm = $deliveryDate['delivery-date-CoolNormalDm'];
        }
        if(isset($deliveryDate['delivery-time-CoolNormalDm'])) {
            $dtimeCoolNormalDm = $deliveryDate['delivery-time-CoolNormalDm'];
        }

        $orderItems = $order->getAllItems();
        // save delivery date for item
        $this->_saveDDToOrderItem($orderItems,
            $ddateChilled, $dtimeChilled,
            $ddateCosmetic, $dtimeCosmetic,
            $ddateCold, $dtimeCold,
            $ddateCoolNormalDm, $dtimeCoolNormalDm
        );

        return true;
    }

    /**
     * Save Delivery date to order item
     *
     * @param $orderItems
     * @param $ddateChilled
     * @param $dtimeChilled
     * @param $ddateCosmetic
     * @param $dtimeCosmetic
     * @param $ddateCold
     * @param $dtimeCold
     * @param $ddateCoolNormalDm
     * @param $dtimeCoolNormalDm
     *
     * @return $this
     */
    private function _saveDDToOrderItem($orderItems,
                               $ddateChilled, $dtimeChilled,
                               $ddateCosmetic, $dtimeCosmetic,
                               $ddateCold, $dtimeCold,
                               $ddateCoolNormalDm, $dtimeCoolNormalDm
    ) {
        foreach ($orderItems as $item) {
            if($item->getDeliveryType() == Dtype::CHILLED) {
                $this->_chilled($item, $ddateChilled, $dtimeChilled);

            } else if($item->getDeliveryType() == Dtype::COSMETIC) {
                $this->_cosmetic($item, $ddateCosmetic, $dtimeCosmetic);

            } else if($item->getDeliveryType() == Dtype::COLD) {
                $this->_cold($item, $ddateCold, $dtimeCold);

            } else {
                $this->_itemCoolNormalDm($item, $ddateCoolNormalDm, $dtimeCoolNormalDm);

            }
        }
    }

    /**
     * Save Delivery Data to Chilled
     *
     * @param $item
     * @param $ddateChilled
     * @param $dtimeChilled
     */
    private function _chilled($item, $ddateChilled, $dtimeChilled)
    {
        if ($ddateChilled || $dtimeChilled) {
            if ($ddateChilled && !$this->_skipDDate) {
                $item->setDeliveryDate($ddateChilled);
            }
            if ($dtimeChilled) {
                $dtimeChilled = $this->deliveryDateModel->getTimeSlotInfo($dtimeChilled); // return false when not found
                if($dtimeChilled){
                    $item->addData(array(
                        "delivery_time"          => $dtimeChilled->getData("slot_name"),
                        "delivery_timeslot_id"   => $dtimeChilled->getData("id"),
                        "delivery_timeslot_from" => $dtimeChilled->getData("from"),
                        "delivery_timeslot_to"   => $dtimeChilled->getData("to")
                    ));
                }
            }
            try {
                $item->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while save Delivery Date.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
            }
        }
    }

    /**
     * Save Delivery Data to Cosmetic
     *
     * @param $item
     * @param $ddateCosmetic
     * @param $dtimeCosmetic
     */
    private function _cosmetic($item, $ddateCosmetic, $dtimeCosmetic)
    {
        if ($ddateCosmetic || $dtimeCosmetic) {
            if ($ddateCosmetic && !$this->_skipDDate) {
                $item->setDeliveryDate($ddateCosmetic);
            }
            if ($dtimeCosmetic) {
                $dtimeCosmetic = $this->deliveryDateModel->getTimeSlotInfo($dtimeCosmetic); // return false when not found
                if($dtimeCosmetic){
                    $item->addData(array(
                        "delivery_time"          => $dtimeCosmetic->getData("slot_name"),
                        "delivery_timeslot_id"   => $dtimeCosmetic->getData("id"),
                        "delivery_timeslot_from" => $dtimeCosmetic->getData("from"),
                        "delivery_timeslot_to"   => $dtimeCosmetic->getData("to")
                    ));
                }
            }
            try {
                $item->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while save Delivery Date.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
            }
        }
    }

    /**
     * Save Delivery Data to Cold
     *
     * @param $item
     * @param $ddateCold
     * @param $dtimeCold
     */
    private function _cold($item, $ddateCold, $dtimeCold)
    {
        if ($ddateCold || $dtimeCold) {
            if ($ddateCold && !$this->_skipDDate) {
                $item->setDeliveryDate($ddateCold);
            }
            if ($dtimeCold) {
                $dtimeCold = $this->deliveryDateModel->getTimeSlotInfo($dtimeCold); // return false when not found
                if($dtimeCold){
                    $item->addData(array(
                        "delivery_time"          => $dtimeCold->getData("slot_name"),
                        "delivery_timeslot_id"   => $dtimeCold->getData("id"),
                        "delivery_timeslot_from" => $dtimeCold->getData("from"),
                        "delivery_timeslot_to"   => $dtimeCold->getData("to")
                    ));
                }
            }
            try {
                $item->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while save Delivery Date.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
            }
        }
    }

    /**
     * Save Delivery Data to Cool Normal Direct mail
     *
     * @param $item
     * @param $ddateCoolNormalDm
     * @param $dtimeCoolNormalDm
     */
    private function _itemCoolNormalDm($item, $ddateCoolNormalDm, $dtimeCoolNormalDm)
    {
        if($ddateCoolNormalDm || $dtimeCoolNormalDm) {
            if($ddateCoolNormalDm && !$this->_skipDDate) {
                $item->setDeliveryDate($ddateCoolNormalDm);
            }
            if($dtimeCoolNormalDm) {
                $dtimeCoolNormalDm = $this->deliveryDateModel->getTimeSlotInfo($dtimeCoolNormalDm); // return false when not found
                if($dtimeCoolNormalDm){
                    $item->addData(array(
                        "delivery_time"          => $dtimeCoolNormalDm->getData("slot_name"),
                        "delivery_timeslot_id"   => $dtimeCoolNormalDm->getData("id"),
                        "delivery_timeslot_from" => $dtimeCoolNormalDm->getData("from"),
                        "delivery_timeslot_to"   => $dtimeCoolNormalDm->getData("to")
                    ));
                }
            }
            try {
                $item->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while save Delivery Date.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
            }

        }
    }

}
