<?php

namespace Riki\SpotOrderApi\Model;

use Riki\Checkout\Model\MagentoPaymentInformationManagement;
use Riki\SpotOrderApi\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

class PaymentInformationManagement extends MagentoPaymentInformationManagement implements PaymentInformationManagementInterface
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var \Zend_Validate_Date
     */
    protected $_dateValidator;
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Quote\Api\Data\AddressInterface
     */
    protected $_addressInterface;
    /**
     * @var \Riki\MachineApi\Api\Data\OrderInterface
     */
    protected $_orderInterface;
    /**
     * @var \Riki\SpotOrderApi\Model\QuoteManagement
     */
    protected $_cartManagementSpotApi;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var string
     */
    protected $orderDate;
    /**
     * @var string
     */
    protected $deliveryDatePeriod;

    /**
     * @var array
     */
    protected $deliveryTime;

    protected $spotDataRequest = [];

    public $quoteData;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    protected $timezoneInterface;
    /**
     * @var \Riki\SpotOrderApi\Helper\HandleMessageApi
     */
    protected $helperHandleMessage;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelectionConfig;

    /**
     * PaymentInformationManagement constructor.
     * @param \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Quote\Api\CartRepositoryInterface $quote
     * @param \Magento\Quote\Api\Data\AddressInterface $addressInterface
     * @param \Magento\Sales\Model\OrderFactory $orderCollectionFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage
     * @param \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
     */
    public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Quote\Api\CartRepositoryInterface $quote,
        \Magento\Quote\Api\Data\AddressInterface $addressInterface,
        \Magento\Sales\Model\OrderFactory $orderCollectionFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
    )
    {
        parent::__construct($billingAddressManagement, $paymentMethodManagement, $cartManagement, $paymentDetailsFactory, $cartTotalsRepository, $eventManager);
        $this->_request = $request;
        $this->_dateValidator = new \Zend_Validate_Date();
        $this->_resourceConnection = $resourceConnection;
        $this->_addressInterface = $addressInterface;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->helperHandleMessage = $helperHandleMessage;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
    }

    /**
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return array|int|mixed
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    )
    {
        //set param for machine api
        $this->_request->setParam('call_spot_order_api', 'call_spot_order_api');

        /**
         * Load quote
         */
        $this->getQuoteRepository($cartId);

        /**
         *  Validate data input
         */
        $this->spotDataRequest = $this->validateDataInput();

        /**
         * Process time slot
         */
        $this->processTimeSlotQuoteItem($cartId, $this->spotDataRequest);

        try {
            $orderId = parent::savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
            $orderIncrementId = $this->getOrderIncrementId($orderId);
            return [
                'orderNo' => $orderIncrementId
            ];
        } catch (\Exception $e) {
            /**
             * Handel message
             */
            $arrMessage = $this->helperHandleMessage->handleMessage($e->getMessage(), $e->getFile());
            return $arrMessage;
        }
    }

    /**
     * Validate data request api
     *
     * @return array
     * @throws AlreadyExistsException
     * @throws InputException
     */
    public function validateDataInput()
    {
        $requestData = $this->_request->getRequestData();
        $data = [];
        if (isset($requestData['spot_data']) && is_array($requestData['spot_data'])) {
            $data = $requestData['spot_data'];
        }

        //check validate time order time
        if (isset($data['order_date']) && $data['order_date'] != null) {
            $this->checkFormatDate('order_date', $data['order_date']);
            $this->orderDate = $data['order_date'];
        } else {
            throw InputException::requiredField('order_date');
        }

        //check validate Delivery date
        if (isset($data['delivery_date_period']) && $data['delivery_date_period'] != null) {
            $this->checkFormatDate('delivery_date_period', $data['delivery_date_period']);
            $this->deliveryDatePeriod = $data['delivery_date_period'];

            $now = $this->timezoneInterface->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d ');
            if (strtotime($this->deliveryDatePeriod) < strtotime($now)) {
                throw new InputException(
                    new Phrase(
                        "The date is not valid. Please enter date large or equal current day.",
                        [
                            'fieldName' => 'delivery_date_period',
                            'value' => $this->deliveryDatePeriod
                        ]
                    )
                );
            }
        }

        if (isset($data['delivery_time']) && $data['delivery_time'] != null) {
            $deliveryTime = $data['delivery_time'];
            if ($this->checkNumber($deliveryTime) != $this->checkNumber(intval($deliveryTime))) {
                throw new AlreadyExistsException(__("Format of delivery time is wrong. Please enter the number", array(array("delivery_time" => $data['delivery_time']))));
            }

            //check time slot
            $connection = $this->_resourceConnection->getConnection();
            $sql = $connection->select()
                ->from([$connection->getTableName('riki_timeslots')])
                ->where("appointed_time_slot = (?) ", $deliveryTime);

            $timeSlot = $connection->fetchRow($sql);
            if (is_array($timeSlot) && count($timeSlot) > 0) {
                $this->deliveryTime = $timeSlot;
            } else {
                throw new AlreadyExistsException(__("Delivery time doesn't exit", array(array("delivery_time" => $data['delivery_time']))));
            }
        }

        return $data;
    }

    /**
     * @param $stringCompare
     * @return int
     */
    public function checkNumber($stringCompare)
    {
        $ordCal = 0;
        $totalStringCompare = strlen($stringCompare);
        for ($i = 0; $i < $totalStringCompare; $i++) {
            $ordCal += ord($stringCompare[$i]);
        }
        return $ordCal;
    }

    /**
     * Check format date
     *
     * @param $fieldName
     * @param $dataDate
     * @return bool
     * @throws InputException
     */
    public function checkFormatDate($fieldName, $dataDate)
    {
        //$dataDate = str_replace('/','-',trim($dataDate));
        if (!$this->_dateValidator->isValid($dataDate)) {
            throw new InputException(
                new Phrase(
                    "Format of date is wrong. Please enter the date in the format yyyy-MM-dd",
                    [
                        'fieldName' => $fieldName,
                        'value' => $dataDate
                    ]
                )
            );
        }

        return true;
    }

    /**
     * Get id increment of order
     *
     * @param $orderId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderIncrementId($orderId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderCollectionFactory->create()->load($orderId);
        if ($order) {
            $order->setOrderDate($this->orderDate);
            $order->setDeliveryDatePeriod($this->deliveryDatePeriod);

            if (isset($this->spotDataRequest['delivery_time'])) {
                $order->setDeliveryTime($this->spotDataRequest['delivery_time']);
            }

            if ($order->save()) {
                return $order->getIncrementId();
            }
        }
        return $orderId;
    }

    /**
     * Get quote item by cart id
     *
     * @param $cartId
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuoteRepository($cartId)
    {
        if (!$this->quoteData instanceof \Magento\Quote\Model\Quote) {
            $this->quoteData = $this->_quoteRepository->get($cartId);
        }
        return $this->quoteData;
    }

    /**
     * Process time slot quote item
     *
     * @param $cartId
     * @param $mmData
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processTimeSlotQuoteItem($cartId, $mmData)
    {
        $quote = $this->getQuoteRepository($cartId);

        $deliveryDatePeriod = null;
        if (isset($mmData['delivery_date_period'])
            && $mmData['delivery_date_period'] != null
            && !$this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()
        ) {
            $deliveryDatePeriod = str_replace('/', '-', trim($mmData['delivery_date_period']));
        }

        if ($this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()) {
            $quote->setData('allow_choose_delivery_date', 0);
        }
        $deliveryTime = null;
        $deliveryTimeFrom = null;
        $deliveryTimeTo = null;
        $deliveryTimeSlotId = null;

        if (isset($mmData['delivery_time'])
            && $mmData['delivery_time'] != null
            && !$this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()
        ) {
            /**
             * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $timeSlotMachine
             */
            $timeSlotMachine = $this->deliveryTime;
            if ($timeSlotMachine != null) {
                $deliveryTime = isset($timeSlotMachine['slot_name']) ? $timeSlotMachine['slot_name'] : null;
                $deliveryTimeFrom = isset($timeSlotMachine['from']) ? $timeSlotMachine['from'] : null;
                $deliveryTimeTo = isset($timeSlotMachine['to']) ? $timeSlotMachine['to'] : null;
                $deliveryTimeSlotId = isset($timeSlotMachine['id']) ? $timeSlotMachine['id'] : null;
            }
        }

        /* @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($deliveryDatePeriod != null && $item->getDeliveryDate() == null) {
                $item->setDeliveryDate($deliveryDatePeriod);
            }

            if ($deliveryTime != null) {
                $item->setDeliveryTime($deliveryTime);
            }

            if ($deliveryTimeFrom != null) {
                $item->setDeliveryTimeslotFrom($deliveryTimeFrom);
            }

            if ($deliveryTimeTo != null) {
                $item->setDeliveryTimeslotTo($deliveryTimeTo);
            }

            if ($deliveryTimeSlotId != null) {
                $item->setDeliveryTimeslotId($deliveryTimeSlotId);
            }

            $item->save();
        }

        return null;
    }
}
