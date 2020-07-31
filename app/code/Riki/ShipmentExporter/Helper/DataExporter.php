<?php
namespace Riki\ShipmentExporter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory
    as DeliveryCollectionFactory;

class DataExporter extends AbstractHelper
{
    const ORDER_SPOT = 'SPOT';
    const ORDER_HANPUKAI = 'HANPUKAI';
    const ORDER_SUBSCRIPTION = 'SUBSCRIPTION';
    const WH_BIZEX = 'BIZEX';
    const WH_ASKUL = 'ASKUL';
    const WH_TOYO = 'TOYO';
    const WH_HITACHI = 'HITACHI';
    const ORDER_CREAETED_BY_FO = 'web order';
    const ORDER_CHANNEL_ONLINE = 'online';
    const PAYMENT_METHOD_FREE = 'free';
    const IS_EXPORTED_WMS = 1;
    const CONFIG_CUTTING_BYTE = '8bit';
    const CONFIG_MAX_CUSTOMER_LENGTH = 102;
    const PAYMENT_CODE_CASHONDELIVERY = '02';
    const PAYMENT_CODE_PAYGENT = '04';
    const PAYMENT_CODE_CVS = '07';
    const PAYMENT_CODE_INVOICEDBASEDPAYMENT = '09';
    const PAYMENT_CODE_NP_ATOBARAI = '10';
    const PAYMENT_CODE_NO_PAYMENT_REQUIRED = '00';
    const PAYMENT_CODE_NO_PAYMENT_REQUIRED_USE_POINT = '01';

    /**
     * @var \Riki\ShipmentExporter\Helper\Subscription
     */
    protected $subscriptionHelper;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\Customer\Api\ShoshaRepositoryInterface
     */
    protected $shoshaRepository;
    /**
     * @var DeliveryCollectionFactory
     */
    protected $deliveryRepository;
    /**
     * @var CollectionFactory
     */
    protected $whCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileObject;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;
    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $sftp;
    /**
     * @var \Riki\ShipmentExporter\Logger\LoggerShip
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\Order\ItemRepository
     */
    protected $orderItemRepository;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $shoshaHelper;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    protected $orderAddressRepository;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory
     */
    protected $timeslotCollection;
    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;
    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $salesConnection;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $prefectureRepository;
    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $customerHelper;
    /**
     * @var
     */
    protected $stockPointRepository;
    /**
     * @var \Riki\Customer\Helper\ConverKana
     */
    protected $convertKana;
    /**
     * @var
     */
    protected $currentBucket;

    protected $itemExported;
    /**
     * DataExporter constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $whCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileObject
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Magento\Framework\App\AreaList $areaList
     * @param \Magento\Framework\App\State $state
     * @param Data $dataHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Riki\ShipmentExporter\Logger\LoggerShip $logger
     * @param \Magento\Sales\Model\Order\ItemRepository $itemRepository
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $timeSlotCollection
     * @param DeliveryCollectionFactory $deliveryCollectionFactory
     * @param \Riki\ShipmentExporter\Helper\Subscription $subscriptionHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $whCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileObject,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\State $state,
        \Riki\ShipmentExporter\Helper\Data $dataHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ShipmentExporter\Logger\LoggerShip $logger,
        \Magento\Sales\Model\Order\ItemRepository $itemRepository,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $timeSlotCollection,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\Customer\Helper\Data $customerHelper,
        DeliveryCollectionFactory $deliveryCollectionFactory,
        Subscription $subscriptionHelper
    ) {
        $this->_scopeConfig = $context;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shoshaRepository = $shoshaRepository;
        $this->deliveryRepository = $deliveryCollectionFactory;
        $this->whCollectionFactory = $whCollectionFactory;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->directoryList = $directoryList;
        $this->fileObject = $fileObject;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->areaList = $areaList;
        $this->state = $state;
        $this->dataHelper = $dataHelper;
        $this->fileSystem = $filesystem;
        $this->sftp = $sftp;
        $this->logger = $logger;
        $this->orderItemRepository = $itemRepository;
        $this->shoshaHelper = $shoshaHelper;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->productRepository = $productRepository;
        $this->timeslotCollection = $timeSlotCollection;
        $this->wrappingRepository = $wrappingRepository;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->salesConnection = $connectionHelper->getSalesConnection();
        $this->prefectureRepository = $regionFactory;
        $this->customerHelper = $customerHelper;
        $this->currentBucket = '';
        $this->itemExported = [];
        parent::__construct($context);
    }
    /**
     * @param $char
     * @param $length
     * @param string $string
     * @param bool $space
     * @param bool $needConvert
     * @return bool|mixed|string
     */
    public function addFullLengthChar($char, $length, $string = '', $space = true, $needConvert = true)
    {
        $replacementChar = ' ';
        if ($string) {
            $string = $this->removeCLRF($string, $replacementChar);
        }
        if ($needConvert) {
            $encode=mb_detect_encoding($string);
            $encode = $encode ? $encode : 'UTF-8';
            $string = mb_convert_encoding($string, "sjis-win", $encode);
        }
        $stringLength = mb_strlen($string, self::CONFIG_CUTTING_BYTE);
        if ($length <= 0) {
            return '';
        }
        if ($length == $stringLength) {
            return $string;
        }
        $numberChar = $length - $stringLength;
        if ($numberChar < 0) {
            $string = $this->substringByByte($string, $numberChar);
            return $string;
        }
        if ($space) {
            for ($i = 0; $i < $numberChar; $i++) {
                $string = $char . $string;
            }
        } else {
            for ($i = 0; $i < $numberChar; $i++) {
                $string = $string . $char;
            }
        }
        return $string;
    }
    /**
     * @param $string
     * @param $length
     * @param string $encoding
     * @param string $padCharacter
     * @return bool|string
     */
    public function substringByByte($string, $length, $encoding = 'SHIFT-JIS', $padCharacter = ' ')
    {
        if ($length < 0) {
            $length = mb_strlen($string, self::CONFIG_CUTTING_BYTE) + $length;
        }

        $result = '';
        $nextCharacter = '';
        $offset = 0;
        while (mb_strlen($result . $nextCharacter, self::CONFIG_CUTTING_BYTE) <= $length) {
            $result .= mb_substr($string, $offset++, 1, $encoding);
            $nextCharacter = mb_substr($string, $offset, 1, $encoding);
        }
        if (mb_strlen($result, self::CONFIG_CUTTING_BYTE) > $length) {
            return false;
        } elseif (mb_strlen($result, self::CONFIG_CUTTING_BYTE) < $length) {
            $padStringLength = $length - mb_strlen($result, self::CONFIG_CUTTING_BYTE);

            for ($i = 0; $i < $padStringLength; $i++) {
                $result .= $padCharacter;
            }
        }
        return $result;
    }

    /**
     * @param $postCode
     * @return mixed
     */
    public function formatPostCode($postCode)
    {
        return str_replace('-', '', $postCode);
    }
    /**
     * @param $orderType
     * @return int
     */
    public function getOrderType($orderType)
    {
        switch (strtoupper($orderType)) {
            case self::ORDER_SUBSCRIPTION:
                $orderTypeValue =1;
                break;
            case \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT:
                $orderTypeValue =1;
                break;
            case self::ORDER_HANPUKAI:
                $orderTypeValue = 2;
                break;
            default:
                $orderTypeValue =0;
                break;
        }
        return $orderTypeValue;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $currentWh
     * @param $billingAddressType
     * @param $isAmbassador
     * @param $paymentCode
     * @param $shoshaCmpName
     * @return array
     */
    public function getAddressData(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $currentWh,
        $billingAddressType,
        $isAmbassador,
        $shoshaCmpName,
        $isGiftOrder = null
    ) {
    
        $order = $shipment->getOrder();
        $billingAddress = $shipment->getBillingAddress();
        $billingAddressValue = $shipment->getData('billing_address');
        $shippingAddressValue = $shipment->getData('shipping_address');
        $space = ' ';
        if ($isGiftOrder) {
            $customerName = sprintf(
                __("Billing name 1"),
                $billingAddress->getLastname(),
                $billingAddress->getFirstname()
            );
            $customerNameKana = $billingAddress->getLastnamekana().' '.$billingAddress->getFirstnamekana();
            $customerAddress1 = $billingAddress->getRegion(); // post code
            $customerAddress2 = preg_replace('/\n/', ' ', $this->getStreetFull($billingAddress, $space));
            $customerAddress3 = "";
            $customerPostcode = $billingAddress->getPostcode();
            $customerPhone = $this->formatPhone($billingAddress->getTelephone());
            //re-calculate Customer Name
            if ($isAmbassador && $billingAddressType =='company') {
                $customerName = $this->formatCustomerName(
                    $billingAddress->getLastname(),
                    $billingAddress->getFirstname()
                );
            } elseif (!$isAmbassador && !empty($shoshaCmpName)) {
                $customerNameRaw = sprintf(
                    __('Billing name 3'),
                    $billingAddress->getLastname(),
                    $billingAddress->getFirstname()
                );
                $customerName= $this->convertEncode($customerNameRaw);
            } else {
                $customerName = $this->convertEncode(
                    $billingAddress->getLastname()
                    .' '
                    .$billingAddress->getFirstname()
                    .__('Billing name 4')
                );
            }
            // re-define customer kana name
            if (!$isAmbassador && !empty($shoshaCmpName)) {
                $customerNameKana = '';
            }
            if ($shoshaCmpName) {
                $customerPhone = $order->getData('customer_key_work_ph_num');
                $customerPhone = $this->formatPhone($customerPhone);
            }
        } else {
            $customerName = $this->convertEncode(__("Billing name 2"));
            $customerNameKana = __("Billing name kana 2");
            $customerPhone =    $currentWh->getMainPhone();
            $customerAddress1 = $this->subscriptionHelper->getRegionNameByCode($currentWh->getState());
            $customerAddress2 = $currentWh->getAddressLine1();
            $customerAddress3 = $currentWh->getAddressLine2();
            $customerPostcode = $currentWh->getPostalCode();
        }

        $shippingAddressType = $isGiftOrder ? 1 : 0;

        return [
            'customerName' => $customerName,
            'customerNameKana' => $customerNameKana,
            'customerAddress1' => $customerAddress1,
            'customerAddress2' => $customerAddress2,
            'customerAddress3' => $customerAddress3,
            'customerPostcode' => $customerPostcode,
            'customerPhone' => $customerPhone,
            'shippingAddressType' => $shippingAddressType
        ];
    }
    /**
     * @param $lastname
     * @param $firstname
     * @return string
     */
    public function formatCustomerName($lastname, $firstname)
    {
        $lastname = $this->convertEncode($lastname);
        $firstname = $this->convertEncode($firstname);
        $newCustomerName = $lastname.$firstname;
        if (mb_strlen($newCustomerName, self::CONFIG_CUTTING_BYTE) > self::CONFIG_MAX_CUSTOMER_LENGTH) {
            // cut lastname
            $cutNumber = mb_strlen($newCustomerName, self::CONFIG_CUTTING_BYTE) - self::CONFIG_MAX_CUSTOMER_LENGTH ;
            if ($cutNumber < mb_strlen($lastname, self::CONFIG_CUTTING_BYTE)) {
                $newLastname = $this->substringByByte(
                    $lastname,
                    mb_strlen($lastname, self::CONFIG_CUTTING_BYTE) - $cutNumber
                );
                return $newLastname.$firstname;
            } else {
                // cut firstname
                $newFirstName = $this->substringByByte(
                    $firstname,
                    mb_strlen($firstname, self::CONFIG_CUTTING_BYTE) - $cutNumber
                );
                return $lastname.$newFirstName;
            }
        }
        return $newCustomerName;
    }
    /**
     * @param $string
     * @return mixed|string
     */
    public function convertEncode($string)
    {
        $encode=mb_detect_encoding($string);
        $encode = $encode ? $encode : 'UTF-8';
        return mb_convert_encoding($string, "sjis-win", $encode);
    }
    /**
     * @param $phone
     * @return mixed
     */
    public function formatPhone($phone)
    {
        return str_replace('-', '', $phone);
    }
    /**
     * @param $paymentMethod
     * @param $usedPoints
     * @return string
     */
    public function getPaymentType($paymentMethod, $usedPoints)
    {
        $paymentName = '';
        if ($paymentMethod) {
            $paymentName = $paymentMethod->getMethod();
        }
        switch (strtolower($paymentName)) {
            case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                $paymentCode = self::PAYMENT_CODE_CASHONDELIVERY;
                break;
            case \Bluecom\Paygent\Model\Paygent::CODE:
                $paymentCode = self::PAYMENT_CODE_PAYGENT;
                break;
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                $paymentCode = self::PAYMENT_CODE_CVS;
                break;
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $paymentCode = self::PAYMENT_CODE_INVOICEDBASEDPAYMENT;
                break;
            case \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                $paymentCode = self::PAYMENT_CODE_NP_ATOBARAI;
                break;
            default:
                $paymentCode = self::PAYMENT_CODE_NO_PAYMENT_REQUIRED;
                if ((int)($usedPoints) > 0) {
                    $paymentCode = self::PAYMENT_CODE_NO_PAYMENT_REQUIRED_USE_POINT;
                }
                break;
        }
        return $paymentCode;
    }
    /**
     * @param $shipment
     * @return mixed
     */
    public function getFinalTotal($shipment)
    {
        return $shipment->getAmountTotal()
            + $shipment->getShipmentFee()
            + $shipment->getPaymentFee()
            + $shipment->getGwPrice()
            + $shipment->getGwTaxAmount()
            - $shipment->getShoppingPointAmount()
            - $shipment->getDiscountAmount();
    }
    /**
     * @param $shipment
     * @return mixed
     */
    public function getTotalAmountVoucher($shipment)
    {
        return $shipment->getAmountTotal()
            + $shipment->getGwPrice()
            + $shipment->getGwTaxAmount()
            - $shipment->getDiscountAmount();
    }
    /**
     * @param $order
     * @return array
     */
    public function getSubscriptionInformation($order)
    {
        $profileId = $order->getData('subscription_profile_id');
        try {
            $subProfile = $this->subscriptionHelper->getSubscriptionProfile($profileId);
            // Hanpukai or Subscription
            if ($order->getRikiType()!=self::ORDER_SPOT && $profileId && $subProfile) {
                $courseId = $subProfile->getData('course_id');
                $courseModel = $this->subscriptionHelper->getCourse($courseId);
                $courseName = $courseModel->getCourseName();
                $subscriptionOrderTime = $subProfile->getOrderTimes();
                $nextDeliveryDate = $subProfile->getNextDeliveryDate();
                if ($nextDeliveryDate) {
                    $nextDeliveryDate = $this->formatDeliveryDate($nextDeliveryDate);
                }
                $courseCode = $courseModel->getData('course_code');
                if ($order->getRikiType()== self::ORDER_HANPUKAI) {
                    $maxOrderTime = (int)($courseModel->getData('hanpukai_maximum_order_times'));
                    if ($maxOrderTime > 0 && $maxOrderTime == $subscriptionOrderTime) { // last delivery of hanpukai
                        $subscriptionOrderTime = 0;
                    }
                }
            } else {
                $courseName = '';
                $subscriptionOrderTime = 0;
                $nextDeliveryDate= '';
                $courseId = 0;
                $courseCode = '';
            }
        } catch (\Exception $e) {
            $courseName = '';
            $subscriptionOrderTime = 0;
            $nextDeliveryDate= '';
            $courseId = 0;
            $courseCode = '';
        }
        return [
            $courseName,
            $courseCode,
            $courseId,
            $nextDeliveryDate,
            $subscriptionOrderTime
        ];
    }
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return float
     */
    public function getWrappingFeeShipment(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {

        $shipmentItems = $shipment->getItemsCollection();
        $totalGW = 0;
        if (!empty($shipmentItems)) {
            foreach ($shipmentItems as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getData('unit_case') ==
                    \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE
                ) {
                    $qty = (int)($orderItem->getData('qty_ordered')/$orderItem->getData('unit_qty'));
                } else {
                    $qty = $orderItem->getData('qty_ordered');
                }
                $totalGW += ($orderItem->getGwBasePrice() + $orderItem->getGwTaxAmount()) * $qty;
            }
        }
        return round($totalGW, 0);
    }
    /**
     * @param $orderChannel
     * @return int
     */
    public function getOrderChannel($orderChannel)
    {
        switch (strtolower($orderChannel)) {
            case "fax":
                $value = 3;
                break;
            case "call":
                $value = 2;
                break;
            case "machine_maintenance":
                $value = 2;
                break;
            case "email":
                $value = 4;
                break;
            case "postcard":
                $value = 1;
                break;
            case "online":
                $value = 5;
                break;
            default:
                $value = 0;
                break;
        }
        return $value;
    }
    /**
     * @param $order
     * @return int
     */
    public function getShipmentSystemType($order)
    {
        $orderChannel = $order->getData('order_channel');
        if ($orderChannel=="machine_maintenance") {
            if ($order->getSubstitution()==1 && $order->getData('mm_broken_sku') !='') {
                return 1;
            } else {
                return 2;
            }
        } else {
            return 0;
        }
    }
    /**
     * @param $order
     * @param $shoshaCode
     * @return int|string
     */
    public function getPaymentDetailNumber($order, $shoshaCode)
    {
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod==\Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
            if ((int)($shoshaCode)==3) {
                return 3;
            } else {
                return 0;
            }
        } else {
            return "";
        }
    }

    /**
     * @param $shipment
     * @return string
     */
    public function getShipmentWarehouse($shipment)
    {
        return $shipment->getWarehouse() ? $shipment->getWarehouse() : self::WH_TOYO;
    }

    /**
     * @param $address
     * @param $doubleByteSpace
     * @return string
     */
    public function getStreetFull($address, $doubleByteSpace)
    {
        $street = $address->getStreet();
        if (is_array($street)) {
            return  implode($doubleByteSpace, $street);
        }
        return '';
    }
    /**
     * @param $text
     * @param $replaceStr
     * @return mixed
     */
    public function removeCLRF($text, $replaceStr)
    {
        $clrf = [
            "\r\n",
            "\n",
            "\r"
        ];
        return str_replace($clrf, $replaceStr, $text);
    }
    /**
     * @return array
     */
    public function loadAllShoshaCodes()
    {
        $shoshasNames = [];
        $shoshasCodes = [];
        $search = $this->searchCriteriaBuilder->create();
        $collection = $this->shoshaRepository->getList($search);
        foreach ($collection->getItems() as $item) {
            $shoshasNames[$item->getData('shosha_business_code')] = $item->getData('shosha_cmp');
            $shoshasCodes[$item->getData('shosha_business_code')] = $item->getData('shosha_code');
        }
        return [$shoshasNames,$shoshasCodes];
    }

    /**
     * @return array
     */
    public function getDeliveryCodes()
    {
        $collection = $this->deliveryRepository->create();
        $deliveryCodes = [];
        if ($collection->getSize()) {
            foreach ($collection as $item) {
                $deliveryCodes[strtolower($item->getData('code'))] = $item->getData('sync_code');
            }
        }
        return $deliveryCodes;
    }
    /**
     * get warehouse main phone
     */
    public function getWarehouses()
    {
        $collection = $this->whCollectionFactory->create();
        $whs = [];
        if ($collection->getSize()) {
            foreach ($collection as $wh) {
                $whs[$wh->getStoreCode()] = $wh;
                if ($wh->getStoreCode() == self::WH_BIZEX) {
                    $whs[self::WH_ASKUL] = $wh;
                }
            }
        }
        return $whs;
    }
    /**
     * @return array
     */
    public function getNeededDates()
    {
        $originDate = $this->timezone->formatDateTime(
            $this->dateTime->gmtDate(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
        $needDate = $this->dateTime->gmtDate('YmdHis', $originDate);
        $compareDate = $this->dateTime->gmtDate('Y-m-d', $originDate);
        $currenExportDate = $this->dateTime->gmtDate('Ymd', $originDate);
        return [$needDate,$compareDate,$currenExportDate];
    }
    /**
     * @param $needDate
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function backupLog($needDate)
    {
        $varDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $backupFolder = '/log/ShipmentExportBackup';
        $localPath = $varDir.$backupFolder;
        if (!$this->fileObject->isDirectory($localPath)) {
            if (!$this->fileObject->createDirectory($localPath, 0777)) {
                $this->writeToLog(__('Can not create dir file').$localPath);
                return;
            }
        }
        $fileLog = $varDir.'/log/shipment_exporter.log';
        $newLog = $varDir.'/'.$backupFolder.'/'.'shipment_exporter_'.$needDate.'.log';
        if ($this->fileObject->isWritable($localPath) && $this->fileObject->isExists($fileLog)) {
            $this->fileObject->rename($fileLog, $newLog);
        }
    }

    /**
     * @param $compareDate
     * @param $limitation
     * @return mixed
     */
    public function getShipmentDataCollection($compareDate, $limitation)
    {
        $exportWarehouses= [
            self::WH_TOYO,
            self::WH_BIZEX,
            self::WH_HITACHI
        ];
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter(
            ['main_table.export_date', 'main_table.export_date'],
            [['lteq' => $compareDate], ['null' => true]]
        );
        $shipmentCollection->addFieldToFilter(
            ['main_table.shipment_status', 'main_table.shipment_status'],
            [['eq' => ShipmentStatus::SHIPMENT_STATUS_CREATED], ['null' => true]]
        );
        $shipmentCollection->addFieldToFilter(
            'is_exported',
            ['neq' => 1]
        );
        $shipmentCollection->addFieldToFilter(
            'ship_zsim',
            ['neq' => 1]
        );
        $shipmentCollection->addFieldToFilter(
            'is_chirashi',
            ['neq' => 1]
        );
        $shipmentCollection->join(
            'sales_order',
            'main_table.order_id = sales_order.entity_id',
            ''
        );
        /*join table to get is_preorder flag for this order (this is not sales_order column)*/
        $shipmentCollection->join(
            'riki_preorder_order_preorder',
            'sales_order.entity_id = riki_preorder_order_preorder.order_id',
            ''
        );
        /*filter order - do not apply for preorder*/
        $shipmentCollection->addFieldToFilter(
            'is_preorder',
            ['in' => [0,2]]
        );
        // RIM-4016 - improve performance
        $shipmentCollection->addFieldToFilter(
            'sales_order.status',
            ['in' => [
                OrderStatus::STATUS_ORDER_IN_PROCESSING,
                OrderStatus::STATUS_ORDER_NOT_SHIPPED,
                OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED
            ]]
        );
        $bucketIds = $this->getValidStockPointExport($compareDate);
        if ($bucketIds) {
            $shipmentCollection->addFieldToFilter(
                ['main_table.stock_point_delivery_bucket_id', 'main_table.stock_point_delivery_bucket_id'],
                [['null' => true], ['in' => $bucketIds]]
            );
        }
        $shipmentCollection->addFieldToFilter(
            'warehouse',
            ['in' => $exportWarehouses]
        );
        $shipmentCollection->setOrder('stock_point_delivery_bucket_id', 'DESC');
        $shipmentCollection->setPageSize($limitation)->setCurPage(1);
        return $shipmentCollection;
    }
    /**
     * @return \Magento\Framework\App\Area
     */
    public function getAreaList()
    {
        return $this->areaList->getArea($this->state->getAreaCode());
    }
    /**
     * @param $warehouseId
     * @param bool $copy
     * @return string
     */
    public function getWarehouseLocationLocal($warehouseId, $copy = false)
    {
        $pathLocal = $this->dataHelper->getExportLocationFolder($warehouseId, $copy);
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if (!$this->fileObject->isDirectory($baseDir.'/'.$pathLocal)) {
            $this->fileObject->createDirectory($baseDir.'/'.$pathLocal);
        }
        return $pathLocal;
    }

    /**
     * @param $needDate
     * @return array
     */
    public function initialFilesExport($needDate)
    {
        // Toyo path
        $pathToyo = $this->getWarehouseLocationLocal(self::WH_TOYO, false);
        $pathToyoCopy = $this->getWarehouseLocationLocal(self::WH_TOYO, true);
        // Bizex path
        $pathBizex = $this->getWarehouseLocationLocal(self::WH_BIZEX, false);
        $pathBizexCopy = $this->getWarehouseLocationLocal(self::WH_BIZEX, true);
        //Hitachi Path
        $pathHitachi = $this->getWarehouseLocationLocal(self::WH_HITACHI, false);
        $pathHitachiCopy = $this->getWarehouseLocationLocal(self::WH_HITACHI, true);
        //begin exporting...
        $filesystem = $this->fileSystem;
        // Toyo file
        $filenameHeaderToyo = "XRXT1003_H_" . $needDate . '.txt';
        $filenameDetailToyo = "XRXT1003_D_" . $needDate . '.txt';
        //Bizex file
        $filenameHeaderBizex = "XRXB1003_H_" . $needDate . '.txt';
        $filenameDetailBizex = "XRXB1003_D_" . $needDate . '.txt';
        //Hitachi file
        $filenameHeaderHitachi = "XRXH1003_H_" . $needDate . '.txt';
        $filenameDetailHitachi = "XRXH1003_D_" . $needDate . '.txt';

        $writer = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        // File process for Toyo
        $fileHeaderToyo = $writer->openFile($pathToyo . $filenameHeaderToyo, 'w+');
        $fileHeaderToyo->lock();
        $fileDetailToyo = $writer->openFile($pathToyo . $filenameDetailToyo, 'w+');
        $fileDetailToyo->lock();
        // File process for Bizex
        $fileHeaderBizex = $writer->openFile($pathBizex . $filenameHeaderBizex, 'w+');
        $fileHeaderBizex->lock();
        $fileDetailBizex = $writer->openFile($pathBizex . $filenameDetailBizex, 'w+');
        $fileDetailBizex->lock();
        // File process for Hitachi
        $fileHeaderHitachi = $writer->openFile($pathHitachi . $filenameHeaderHitachi, 'w+');
        $fileHeaderHitachi->lock();
        $fileDetailHitachi = $writer->openFile($pathHitachi . $filenameDetailHitachi, 'w+');
        $fileDetailHitachi->lock();
        return [
            'pathToyo' => $pathToyo,
            'pathToyoCopy' => $pathToyoCopy,
            'pathBizex' => $pathBizex,
            'pathBizexCopy' => $pathBizexCopy,
            'pathHitachi' => $pathHitachi,
            'pathHitachiCopy' => $pathHitachiCopy,
            'filenameHeaderToyo' => $filenameHeaderToyo,
            'filenameDetailToyo' => $filenameDetailToyo,
            'filenameHeaderBizex' => $filenameHeaderBizex,
            'filenameDetailBizex' => $filenameDetailBizex,
            'filenameHeaderHitachi' => $filenameHeaderHitachi,
            'filenameDetailHitachi' => $filenameDetailHitachi,
            'fileHeaderToyo' => $fileHeaderToyo,
            'fileDetailToyo' => $fileDetailToyo,
            'fileHeaderBizex' => $fileHeaderBizex,
            'fileDetailBizex' => $fileDetailBizex,
            'fileHeaderHitachi' => $fileHeaderHitachi,
            'fileDetailHitachi' => $fileDetailHitachi
        ];
    }
    /**
     * @param $files
     */
    public function exportSftpBatch($files)
    {
        $host = $this->dataHelper->getSftpHost();
        $port = $this->dataHelper->getSftpPort();
        $username = $this->dataHelper->getSftpUser();
        $password = $this->dataHelper->getSftpPass();
        // try to connect sftp
        try {
            $this->sftp->open(
                [
                    'host' => $host.':'.$port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                ]
            );
        } catch (\Exception $e) {
            $this->writeToLog($e->getMessage());
            return;
        }

        $rootDir = $this->sftp->pwd();
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        foreach ($files as $sfile) {
            $filename = $sfile['filename'];
            $path = $sfile['path'];
            $filetype = $sfile['filetype'];
            $copy = $sfile['copy'];
            if ($copy) {
                $locationTarget = $this->dataHelper->getSftpLocationCopy();
            } else {
                $locationTarget = $this->dataHelper->getSftpLocation();
            }
            $locationTarget.= DIRECTORY_SEPARATOR.$filetype.'1003/remote/';
            $locationTarget = preg_replace('#/+#', '/', $locationTarget);
            $pathLocal  = $baseDir. '/'.$path;
            $dirList = explode('/', $locationTarget);
            $this->sftp->cd($rootDir); // back to first dir
            $this->checkSftpDir($dirList);
            try {
                $sftpPath = $rootDir.'/'.$locationTarget;
                if ($this->sftp->write($sftpPath. $filename, $pathLocal.$filename)) {
                    $this->writeToLog("Upload ".$pathLocal.$filename." to FTP:".$sftpPath. $filename." successfully");
                } else {
                    $this->writeToLog('Upload file failed');
                    $this->writeToLog($locationTarget. ' have not permission to write file');
                }
            } catch (\Exception $e) {
                $this->writeToLog($e->getMessage());
            }
        }//end foreach
    }

    /**
     * @param $filesExport
     */
    public function copyAndExportData($filesExport)
    {
        $this->writeToLog("Ready to push file to SFTP");
        $writer = $this->fileSystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::ROOT
        );
        $pathToyo = $filesExport['pathToyo'];
        $pathToyoCopy = $filesExport['pathToyoCopy'];
        $pathBizex = $filesExport['pathBizex'];
        $pathBizexCopy = $filesExport['pathBizexCopy'];
        $pathHitachi = $filesExport['pathHitachi'];
        $pathHitachiCopy = $filesExport['pathHitachiCopy'];
        $filenameHeaderToyo = $filesExport['filenameHeaderToyo'];
        $filenameDetailToyo = $filesExport['filenameDetailToyo'];
        $filenameHeaderBizex = $filesExport['filenameHeaderBizex'];
        $filenameDetailBizex = $filesExport['filenameDetailBizex'];
        $filenameHeaderHitachi = $filesExport['filenameHeaderHitachi'];
        $filenameDetailHitachi = $filesExport['filenameDetailHitachi'];
        $fileHeaderToyo = $filesExport['fileHeaderToyo'];
        $fileDetailToyo = $filesExport['fileDetailToyo'];
        $fileHeaderBizex = $filesExport['fileHeaderBizex'];
        $fileDetailBizex = $filesExport['fileDetailBizex'];
        $fileHeaderHitachi = $filesExport['fileHeaderHitachi'];
        $fileDetailHitachi = $filesExport['fileDetailHitachi'];
        $uploadFiles = [];
        //close file header Toyo
        $fileHeaderToyo->close();
        $writer->copyFile($pathToyo . $filenameHeaderToyo, $pathToyoCopy . $filenameHeaderToyo);
        $uploadFiles[] = [
            'filename'=>$filenameHeaderToyo,
            'path'=> $pathToyo,
            'copy'=>false,
            'filetype' => 'XRXT'
        ];
        //close file detail Toyo
        $fileDetailToyo->close();
        $writer->copyFile($pathToyo . $filenameDetailToyo, $pathToyoCopy . $filenameDetailToyo);
        $uploadFiles[] = [
            'filename'=>$filenameDetailToyo,
            'path'=> $pathToyo,
            'copy'=>false,
            'filetype' => 'XRXT'
        ];
        //close file header Bizex
        $fileHeaderBizex->close();
        $writer->copyFile($pathBizex . $filenameHeaderBizex, $pathBizexCopy . $filenameHeaderBizex);
        $uploadFiles[] = [
            'filename'=>$filenameHeaderBizex,
            'path'=> $pathBizex,
            'copy'=>false,
            'filetype' => 'XRXB'
        ];
        //close file detail Bizex
        $fileDetailBizex->close();
        $writer->copyFile($pathBizex . $filenameDetailBizex, $pathBizexCopy . $filenameDetailBizex);
        $uploadFiles[] = [
            'filename'=>$filenameDetailBizex,
            'path'=> $pathBizex,
            'copy'=>false,
            'filetype' => 'XRXB'
        ];
        //close file Header Hitachi
        $fileHeaderHitachi->close();
        $writer->copyFile($pathHitachi . $filenameHeaderHitachi, $pathHitachiCopy . $filenameHeaderHitachi);
        $uploadFiles[] = [
            'filename'=>$filenameHeaderHitachi,
            'path'=> $pathHitachi,
            'copy'=>false,
            'filetype' => 'XRXH'];
        //close file Detail Hitachi
        $fileDetailHitachi->close();
        $writer->copyFile($pathHitachi . $filenameDetailHitachi, $pathHitachiCopy . $filenameDetailHitachi);
        $uploadFiles[] = [
            'filename'=>$filenameDetailHitachi,
            'path'=> $pathHitachi,
            'copy'=>false,
            'filetype' => 'XRXH'
        ];
        //backup to ftp folder
        $resftpcopy = $this->dataHelper->checkSftpConnection($this->sftp, true);
        if (!$resftpcopy[0]) {
            $this->writeToLog($resftpcopy[1]);
            $this->writeToLog(__('Could not upload shipment files to sftp copy folder'));
        } else {
            $this->writeToLog(__('Copy shipment exporting files to copy sftp folder'));
            $uploadFiles[] = [
                'filename'=>$filenameHeaderToyo,
                'path'=> $pathToyo,
                'copy'=>true,
                'filetype' => 'XRXT'
            ];
            $uploadFiles[] = [
                'filename'=>$filenameDetailToyo,
                'path'=> $pathToyo,
                'copy'=>true,
                'filetype' => 'XRXT'
            ];
            $uploadFiles[] = [
                'filename'=>$filenameHeaderBizex,
                'path'=> $pathBizex,
                'copy'=>true,
                'filetype' => 'XRXB'
            ];
            $uploadFiles[] = [
                'filename'=>$filenameDetailBizex,
                'path'=> $pathBizex,
                'copy'=>true,
                'filetype' => 'XRXB'
            ];
            $uploadFiles[] = [
                'filename'=>$filenameHeaderHitachi,
                'path'=> $pathHitachi,
                'copy'=>true,
                'filetype' => 'XRXH'
            ];
            $uploadFiles[] = [
                'filename'=>$filenameDetailHitachi,
                'path'=> $pathHitachi,
                'copy'=>true,
                'filetype' => 'XRXH'
            ];
        }
        $this->exportSftpBatch($uploadFiles);
        $this->writeToLog("End of exporting shipment");
    }
    /**
     * @param $dirList
     */
    public function checkSftpDir($dirList)
    {
        foreach ($dirList as $dir) {
            if ($dir != '') {
                if (!$this->sftp->cd($dir)) {
                    $this->sftp->mkdir('/'. $dir);
                }
                $this->sftp->cd($dir);
            }
        }
    }
    /**
     * @param $message
     */
    public function writeToLog($message)
    {
        if ($this->dataHelper->isEnableLogger()) {
            $this->logger->info($message);
        }
    }
    /**
     * Get order item data by item id
     *
     * @param $itemId
     * @return bool|\Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getOrderItemDataByItemId($itemId)
    {
        try {
            return $this->orderItemRepository->get($itemId);
        } catch (\Exception $e) {
            $this->writeToLog('Can not get order item data for id:'.$itemId);
            $this->writeToLog($e->getMessage());
            return false;
        }
    }
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function isChirashiShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $itemsTotal = count($shipment->getAllItems());
        $itemsChirashi = 0;
        $result = false;
        if ($itemsTotal) {
            foreach ($shipment->getAllItems() as $shipItem) {
                $orderItem = $this->getOrderItemDataByItemId($shipItem->getOrderItemId());
                if ($orderItem) {
                    if ($orderItem->getData('chirashi') && !$orderItem->getParentItemId()) {
                        $itemsChirashi++;
                    }
                }
            }
            if ($itemsTotal == $itemsChirashi) {
                $result=true;
            }
        }
        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function isChirashiShipmentProductCase(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $itemsTotal = count($shipment->getAllItems());
        if ($itemsTotal) {
            $countChirashi = 0;
            $countProductCase = 0;
            $productPiece = 0;
            foreach ($shipment->getAllItems() as $shipItem) {
                $orderItem = $this->getOrderItemDataByItemId($shipItem->getOrderItemId());
                if ($orderItem->getData('chirashi')) {
                    $countChirashi++;
                } else {
                    //dont count bundle product
                    if ((!$orderItem->getParentItemId() && $orderItem->getProductType()=="simple")
                        || $orderItem->getParentItemId()) {
                        //count product case
                        if ($orderItem->getData('unit_case') ==
                            \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE
                        ) {
                            $countProductCase++;
                        }
                        // count product piece
                        if ($orderItem->getData('unit_case') ==
                            \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE
                        ) {
                            $productPiece++;
                        }
                    }
                }
            }
            //return result
            if ($countChirashi && $countProductCase && !$productPiece) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function canExportShipment(
        \Magento\Sales\Model\Order\Shipment & $shipment
    ) {
        $orderDetail = $shipment->getOrder();
        $paymentMethod = '';
        if ($orderDetail->getPayment()) {
            $paymentMethod = $orderDetail->getPayment()->getMethod();
        }
        //order has been locked by business
        if ($this->shoshaHelper->isBlockInvoiceOrder($orderDetail, $paymentMethod)) {
            $this->writeToLog(
                sprintf(
                    __('The customer in this order: %s has been blocked by business'),
                    $orderDetail->getIncrementId()
                )
            );
            return false;
        }
        /*verify chirashi shipment */
        if ($this->isChirashiShipment($shipment)) {
            $this->writeToLog(sprintf(__("Chirashi shipment: %s"), $shipment->getIncrementId()));
            $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED);
            $shipment->setPaymentStatus(
                PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
            );
            $shipment->setIsChirashi(1);
            return false;
        }
        if ($orderDetail->getStatus() == OrderStatus::STATUS_ORDER_CANCELED) {
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function isBucketShipment(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        if ($shipment->getData('stock_point_delivery_bucket_id')) {
            return true;
        }
        return false;
    }
    /**
     * @param $addressId
     * @return bool|\Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function getOrderAddressById($addressId)
    {
        try {
            return $this->orderAddressRepository->get($addressId);
        } catch (\Exception $e) {
            $this->writeToLog('Can not get order address for id:'.$addressId);
            $this->writeToLog($e->getMessage());
            return false;
        }
    }
    /**
     * Get product data by id
     *
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductDataById($productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            $this->writeToLog('Can not get product data for id:'.$productId);
            $this->writeToLog($e->getMessage());
            return false;
        }
    }
    /**
     * @param $array
     * @return string
     */
    public function writeFileTxtSAP($array)
    {
        $string = implode("", $array);
        return $string;
    }
    /**
     * item is allowed to exported to WH or not
     *
     * @param $orderItem
     * @return bool
     */
    public function isAllowedToExportedItem(
        \Magento\Sales\Model\Order\Item $orderItem
    ) {
    
        $rs = false;
        if ($orderItem) {
            /*bundle item ->get allowed exported flag from parent item*/
            if (!empty($orderItem->getParentItemId())) {
                $parentItem = $this->getOrderItemDataByItemId($orderItem->getParentItemId());
                return $this->isAllowedToExportedItem($parentItem);
            } else {
                /** @var \Magento\Catalog\Api\Data\ProductInterface $itemProductData */
                $itemProductData = $this->getProductDataById($orderItem->getProductId());
                if ($itemProductData) {
                    //check zsim product
                    $material = $itemProductData->getMaterialType();
                    $phcode = $itemProductData->getPhCode();
                    $isZsim = $this->dataHelper->compareZSIM($material, $phcode);
                    if ($isZsim) {
                        return false;
                    }
                    if ($itemProductData && $itemProductData->getCustomAttribute('shipment_exporting_flg')) {
                        $exportingFlag = $itemProductData->getCustomAttribute('shipment_exporting_flg');
                        if ($exportingFlag->getValue()) {
                            $rs = true;
                        }
                    }
                } else {
                    return false;
                }
            }
        }
        return $rs;
    }
    /**
     * @param $timeslot
     * @return int
     */
    public function getDeliveryTimeSlot($timeslot)
    {
        $timeSlotObject = $this->timeslotCollection->create();
        $timeSlotObject->setPageSize(1);
        $timeSlotObject->addFieldToFilter('slot_name', $timeslot)->load();
        if ($timeSlotObject->getSize()) {
            foreach ($timeSlotObject->getItems() as $item) {
                return $item->getData('appointed_time_slot');
            }
        } else {
            return 0;
        }
    }
    /**
     * @param $productName
     * @return mixed
     */
    public function cleanProductName($productName)
    {
        $newName = preg_replace('~[\r\n]+~', '', trim($productName));
        $newName = preg_replace("/♡/", "LOVE", $newName);
        return $newName;
    }
    /**
     * @param $wrappingId
     * @return \Magento\GiftWrapping\Api\Data\WrappingInterface
     */
    public function getWrappingDetail($wrappingId)
    {
        $wrappingName = '';
        $wrappingCode = '';
        if ($wrappingId) {
            try {
                $giftWrapDetail = $this->wrappingRepository->get($wrappingId);
                $wrappingName = $giftWrapDetail->getGiftCode();
                $wrappingCode = $giftWrapDetail->getGiftName();
            } catch (\Exception $e) {
                $this->writeToLog($e->getMessage());
            }
        }

        return [$wrappingCode, $wrappingName];
    }
    /**
     * @param $orderId
     * @return int
     */
    public function getOrderPartial($orderId)
    {
        $critical = $this->searchCriteriaBuilder->addFilter('original_order_id', $orderId)->create();
        $collection = $this->outOfStockRepository->getList($critical);
        if ($collection->getTotalCount()) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param $date
     * @param $dateType
     * @param $timeType
     * @return string
     */
    public function formatDateTime($date, $dateType, $timeType)
    {
        return $this->timezone->formatDateTime($date, $dateType, $timeType);
    }
    /**
     * @param $deliveryDate
     * @return \Magento\Framework\Phrase
     */
    public function formatDeliveryDate($deliveryDate)
    {
        $year = (string)date('Y', strtotime($deliveryDate));
        $month = (string)date('m', strtotime($deliveryDate));
        $day = (int)date('j', strtotime($deliveryDate));
        if ($day < 10) {
            return sprintf(__('Year%sMonth%sDay1'), $year, $month);
        } elseif ($day < 20) {
            return sprintf(__('Year%sMonth%sDay2'), $year, $month);
        } else {
            return sprintf(__('Year%sMonth%sDay3'), $year, $month);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    public function updateShipment(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
    
        try {
            $shipment->save();
        } catch (\Exception $e) {
            $this->writeToLog('Can not save shipment when export to WMS');
            $this->writeToLog($e->getMessage());
        }
    }

    /**
     * @param $shipmentItem
     * @param $chirashiCase
     * @return bool
     */
    public function canExportShipmentItem($shipmentItem, $chirashiCase)
    {
        $orderItemSingle = $this->getOrderItemDataByItemId($shipmentItem->getOrderItemId());
        //Chirashi item, does not export
        if ($chirashiCase && $orderItemSingle->getData('chirashi')) {
            return false;
        }
        $isAllowedToExportedToWh = $this->isAllowedToExportedItem($orderItemSingle);
        /*product is not allowed to exported to WH*/
        if (!$isAllowedToExportedToWh) {
            return false;
        }
        return true;
    }
    /**
     * Count quantity of shipment item for a bucket shipment
     *
     * @param $bucketId
     * @return mixed
     */
    public function getQtyBucketShipment($bucketId, $sku)
    {
        $shipmentTable = $this->salesConnection->getTableName('sales_shipment');
        $shipmentItemTable = $this->salesConnection->getTableName('sales_shipment_item');
        $query = "select sum(si.qty/si.unit_qty) as `totalqty` from $shipmentItemTable si
        INNER JOIN sales_order_item AS soi 
        ON si.order_item_id=soi.item_id
        INNER JOIN $shipmentTable s 
        ON si.parent_id = s.entity_id
        where s.stock_point_delivery_bucket_id = $bucketId and si.sku='$sku'";
        $data = $this->salesConnection->fetchRow($query);
        return (int)$data['totalqty'];
    }

    /**
     * Get Region name by Region Code
     *
     * @param $preId
     * @return string
     */
    public function getPrefectureNameByCode($preId)
    {
        return $this->subscriptionHelper->getRegionNameByCode($preId);
    }

    /**
     * Get Region name by region ID
     *
     * @param $preId
     * @return string
     */
    public function getPrefectureNameById($preId)
    {
        try {
            $region =  $this->prefectureRepository->create()->load($preId);
            return $region->getName();
        } catch (\Exception $e) {
            $this->_logger->info('Could not find region:'. $preId);
            $this->_logger->info($e->getMessage());
            return '';
        }
    }

    /**
     * Get b2b flag for bucket shipment
     *
     * @param $data
     * @return mixed
     */
    public function getB2bFlagBucket($data)
    {
        return $this->customerHelper->getB2bFlagValue($data);
    }

    /**
     * Check warehouse which allow to export
     *
     * @param $warehouseCode
     * @return bool
     */
    public function checkWarehousesExport($warehouseCode)
    {
        $exportWarehouses = [self::WH_BIZEX, self::WH_HITACHI, self::WH_TOYO];
        if (in_array($warehouseCode, $exportWarehouses)) {
            return true;
        }
        return false;
    }
    /**
     * Check stock point bucket Id which can be exported.
     * All buckeet orders must to have shipments
     * @param $compareDate
     * @return array|bool
     */
    public function getValidStockPointExport($compareDate)
    {
        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $orderNotShip =OrderStatus::STATUS_ORDER_NOT_SHIPPED;
        $orderInProcessing = OrderStatus::STATUS_ORDER_IN_PROCESSING;
        $bucketIds = [];
        $query = "SELECT DISTINCT
        stock_point_delivery_bucket_id,
        SUM(CASE
            WHEN status = '$orderInProcessing' THEN 1
            ELSE 0
        END) AS t1,
        SUM(CASE
            WHEN
                status = '$orderNotShip'
            THEN
                1
            ELSE 0
        END) AS t2
    FROM
        sales_order
    WHERE
        stock_point_delivery_bucket_id !=0
        AND `state` = '$orderState'
        AND `status` in('$orderNotShip','$orderInProcessing') 
        AND (min_export_date is NUll OR min_export_date <='$compareDate')
    GROUP BY stock_point_delivery_bucket_id";
        $this->writeToLog(__('Query to get stock point shipments'));
        $this->writeToLog($query);
        $rows = $this->salesConnection->fetchAll($query);
        if ($rows) {
            foreach ($rows as $row) {
                if ($row['t1'] > 0 && $row['t2'] ==0) {
                    $bucketIds[] = $row['stock_point_delivery_bucket_id'];
                }
            }
            if (!empty($bucketIds)) {
                return $bucketIds;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
