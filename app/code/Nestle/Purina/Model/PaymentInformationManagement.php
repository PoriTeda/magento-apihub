<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Riki\Checkout\Model\MagentoPaymentInformationManagement;
use Nestle\Purina\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

/**
 * Class PaymentInformationManagement
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class PaymentInformationManagement extends MagentoPaymentInformationManagement
    implements PaymentInformationManagementInterface
{
    /**
     * Request
     *
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    /**
     * Resource collection
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Validate date
     *
     * @var \Zend_Validate_Date
     */
    protected $dateValidator;

    /**
     * Quote
     *
     * @var \Magento\Quote\Model\Quote
     */
    protected $quoteRepository;

    /**
     * Order Collection
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderCollectionFactory;

    /**
     * Order date
     *
     * @var string
     */
    protected $orderDate;

    /**
     * Delivery date period
     *
     * @var string
     */
    protected $deliveryDatePeriod;

    /**
     * Delivery Time
     *
     * @var array
     */
    protected $deliveryTime;

    /**
     * Spot data request
     *
     * @var array
     */
    protected $spotDataRequest = [];

    /**
     * Quote data
     *
     * @var mixed
     */
    public $quoteData;

    /**
     * Time zone interface
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * Message handler
     *
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
        \Magento\Sales\Model\OrderFactory $orderCollectionFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
    ) {
        parent::__construct(
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository,
            $eventManager
        );
        $this->request = $request;
        $this->dateValidator = new \Zend_Validate_Date();
        $this->resourceConnection = $resourceConnection;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->helperHandleMessage = $helperHandleMessage;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
    }

    /**
     * Save payment and place order
     *
     * @param int                                           $cartId         cart_id
     * @param \Magento\Quote\Api\Data\PaymentInterface      $paymentMethod  payment method
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress billing address
     *
     * @return array|mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function savePaymentInformationAndPlaceOrderLocal(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->request->setParam('from_purina_api', '1');
        $this->request->setParam('call_spot_order_api', 'call_spot_order_api');
        return $this->savePaymentInformationAndPlaceOrder(
            $cartId, $paymentMethod, $billingAddress
        );
    }

    /**
     * Place order
     *
     * @param int                                           $cartId         cart_id
     * @param \Magento\Quote\Api\Data\PaymentInterface      $paymentMethod  payment method
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress billing address
     *
     * @return array|mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->getQuoteRepository($cartId);
        $this->spotDataRequest = $this->validateDataInput();
        $this->processTimeSlotQuoteItem($cartId, $this->spotDataRequest);
        try {
            $orderId = parent::savePaymentInformationAndPlaceOrder(
                $cartId, $paymentMethod, $billingAddress
            );
            return $this->getOrderIncrementId($orderId);
        } catch (\Exception $e) {
            return $this->helperHandleMessage->handleMessage(
                $e->getMessage(), $e->getFile()
            );
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
        $requestData = $this->request->getRequestData();
        $data = [];
        if (isset($requestData['spot_data'])
            && is_array($requestData['spot_data'])
        ) {
            $data = $requestData['spot_data'];
        }
        if (isset($data['order_date']) && $data['order_date'] != null) {
            $this->checkFormatDate('order_date', $data['order_date']);
            $this->orderDate = $data['order_date'];
        } else {
            throw InputException::requiredField('order_date');
        }
        if (isset($data['delivery_date_period'])
            && $data['delivery_date_period'] != null
        ) {
            $this->checkFormatDate(
                'delivery_date_period', $data['delivery_date_period']
            );
            $this->deliveryDatePeriod = $data['delivery_date_period'];
            $now = $this->timezoneInterface->date()
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d ');
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

            if ($this->checkNumber($deliveryTime) != $this->checkNumber(
                intval($deliveryTime)
            )) {
                throw new AlreadyExistsException(
                    __(
                        "Format of delivery time is wrong. Please enter the number",
                        array(array("delivery_time" => $data['delivery_time']))
                    )
                );
            }
            $connection = $this->resourceConnection->getConnection();
            $sql = $connection->select()
                ->from([$connection->getTableName('riki_timeslots')])
                ->where("id = (?) ", $deliveryTime);

            $timeSlot = $connection->fetchRow($sql);
            if (is_array($timeSlot) && count($timeSlot) > 0) {
                $this->deliveryTime = $timeSlot;
            } else {
                throw new AlreadyExistsException(
                    __(
                        "Delivery time doesn't exit", array(
                        array("delivery_time" => $data['delivery_time']))
                    )
                );
            }
        }
        return $data;
    }

    /**
     * Check number format
     *
     * @param mixed $stringCompare string compare
     *
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
     * @param mixed $fieldName field name
     * @param mixed $dataDate  date data
     *
     * @return bool
     *
     * @throws InputException
     */
    public function checkFormatDate($fieldName, $dataDate)
    {
        if (!$this->dateValidator->isValid($dataDate)) {
            throw new InputException(
                new Phrase(
                    "Format of date is wrong. 
                    Please enter the date in the format yyyy-MM-dd",
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
     * Order increment id
     *
     * @param int $orderId order_id
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getOrderIncrementId($orderId)
    {
        $order = $this->orderCollectionFactory->create()->load($orderId);
        if ($order) {
            $order->setOrderDate($this->orderDate);
            $order->setDeliveryDatePeriod($this->deliveryDatePeriod);

            if (isset($this->spotDataRequest['order_comment'])
            && trim($this->spotDataRequest['order_comment']) != ''
            ) {
                $order->addStatusHistoryComment($this->spotDataRequest['order_comment'], false);
            }

            if (isset($this->spotDataRequest['delivery_time'])) {
                $order->setDeliveryTime($this->spotDataRequest['delivery_time']);
            }

            if ($order->save()) {
                $orderData['order_no'] = $order->getIncrementId();
                $orderData['order_status'] = $order->getStatus();
                return $orderData;
            }
        }
        return $orderId;
    }

    /**
     * Get quote item by cart id
     *
     * @param int $cartId cart_id
     *
     * @return \Magento\Quote\Model\Quote
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuoteRepository($cartId)
    {
        if (!$this->quoteData instanceof \Magento\Quote\Model\Quote) {
            $this->quoteData = $this->quoteRepository->get($cartId);
        }
        return $this->quoteData;
    }

    /**
     * Process time slot quote item
     *
     * @param int   $cartId cart_id
     * @param mixed $mmData mm data
     *
     * @return null
     *
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
            $deliveryDatePeriod = str_replace(
                '/', '-', trim($mmData['delivery_date_period'])
            );
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
            $timeSlotMachine = $this->deliveryTime;
            if ($timeSlotMachine != null) {
                $deliveryTime = isset($timeSlotMachine['slot_name'])
                    ? $timeSlotMachine['slot_name'] : null;
                $deliveryTimeFrom = isset($timeSlotMachine['from'])
                    ? $timeSlotMachine['from'] : null;
                $deliveryTimeTo = isset($timeSlotMachine['to'])
                    ? $timeSlotMachine['to'] : null;
                $deliveryTimeSlotId = isset($timeSlotMachine['id'])
                    ? $timeSlotMachine['id'] : null;
            }
        }
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
