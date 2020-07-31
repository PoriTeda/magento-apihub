<?php
namespace Riki\Sales\Block\Adminhtml\Order\View;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Riki\DeliveryType\Model\Delitype;
use Riki\CvsPayment\Api\ConstantInterface;

class DeliveryInfo extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var AddressConfig
     */
    protected $addressConfig;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_addressHelper;

    /**
     * @var
     */
    protected $addressDeliveryData;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $rikiSalesHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * @var array
     */
    protected $addressIdToObject = [];

    /**
     * @var null
     */
    protected $quote;

    /**
     * @var \Riki\DeliveryType\Helper\Admin
     */
    protected $deliveryTypeAdminHelper;

    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $salesAdminHelper;

    /**
     * @var null
     */
    protected $orderItemIdToAddressId;

    /**
     * @var \Riki\ShippingProvider\Helper\Data
     */
    protected $shippingProviderHelper;

    /**
     * @var array
     */
    protected $addressList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * DeliveryInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Sales\Model\Order\AddressFactory $addressFactory
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Riki\Sales\Helper\Data $rikiSalesHelper
     * @param \Riki\Sales\Helper\Admin $rikiSalesAdminHelper
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory
     * @param \Riki\ShippingProvider\Helper\Data $shippingProviderHelper
     * @param AddressConfig $addressConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\Sales\Helper\Data $rikiSalesHelper,
        \Riki\Sales\Helper\Admin $rikiSalesAdminHelper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Riki\ShippingProvider\Helper\Data $shippingProviderHelper,
        AddressConfig $addressConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );

        $this->addressFactory = $addressFactory;
        $this->addressConfig = $addressConfig;
        $this->_addressHelper = $addressHelper;
        $this->deliveryDate = $deliveryDate;
        $this->_regionFactory = $regionFactory;
        $this->rikiSalesHelper = $rikiSalesHelper;
        $this->courseFactory = $courseFactory;
        $this->frequencyFactory = $frequencyFactory;
        $this->deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->salesAdminHelper = $rikiSalesAdminHelper;
        $this->shippingProviderHelper = $shippingProviderHelper;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressGroups()
    {
        if ($this->addressDeliveryData === null) {
            $itemIdsToAddressIds = $this->getAddressIdsForEdit();

            $addressGroups = [];
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($this->getOrder()->getAllVisibleItems() as $item) {
                $deliveryType = $item->getDeliveryType();

                $addressId = isset($itemIdsToAddressIds[$item->getId()]) ? $itemIdsToAddressIds[$item->getId()] : 0;

                if (!isset($addressGroups[$addressId])) {
                    $addressGroups[$addressId] = [
                        'address_html' => $this->getFormattedAddress($addressId),
                        'delivery'  => []
                    ];
                }

                if (!isset($addressGroups[$addressId]['delivery'][$deliveryType])) {
                    $addressGroups[$addressId]['delivery'][$deliveryType] = [
                        'delivery_date' => $item->getDeliveryDate(),
                        'next_delivery_date' => $item->getNextDeliveryDate(),
                        'delivery_time' => $item->getDeliveryTime(),
                        'time_slot_id' => $item->getDeliveryTimeslotId(),
                        'delivery_type' => $deliveryType,
                        'delivery_type_name' => $deliveryType,
                        'delivery_type_list'    => [],
                        'items' => [],
                        'item_ids'  => []
                    ];
                }

                $itemDeliveryType = $item->getDeliveryType();
                $addressGroups[$addressId]['delivery'][$deliveryType]['item_ids'][] = $item->getId();
                $addressGroups[$addressId]['delivery'][$deliveryType]['item_ids_object'][] = $item;
                $addressGroups[$addressId]['delivery'][$deliveryType]['delivery_type_list'][] = $itemDeliveryType;

                $sku = $item->getSku();

                if (!isset($addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku])) {
                    $addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku] = [
                        'sku'   => $item->getSku(),
                        'product_id'    => $item->getProductId(),
                        'name'  => $item->getName(),
                        'qty'   => 0 + $item->getQtyOrdered()
                    ];
                } else {
                    $qtyOrdered = $item->getQtyOrdered();
                    $addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku]['qty'] += $qtyOrdered;
                }
            }

            $this->addressDeliveryData = $addressGroups;
            $this->addressDeliveryData = $this->_getLimitDeliveryDate();
            $this->addressDeliveryData = $this->prepareGroupDeliveryAddressData($this->addressDeliveryData);
        }

        return $this->addressDeliveryData;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getLimitDeliveryDate()
    {
        $storeId = $this->getOrder()->getStoreId();

        $addressGroups = $this->getAddressGroups();

        foreach ($addressGroups as $addressId => $addressGroup) {
            $addressObj = $this->_getAddressObjById($addressId);

            if ($addressObj instanceof \Magento\Sales\Model\Order\Address && $addressObj->getId()) {
                $region = $this->_regionFactory->create()->load($addressObj->getRegionId());

                $destination = [
                    'country_code' => $addressObj->getCountryId(),
                    'region_code'  => $region instanceof \Magento\Directory\Model\Region ? $region->getCode() : '',
                    'postcode'     => $addressObj->getPostcode(),
                ];
            } else {
                $destination = [
                    'country_code' => '',
                    'region_code'  => '',
                    'postcode'     => '',
                ];
            }

            foreach ($addressGroup['delivery'] as $deliveryType => $deliveryTypeData) {
                $result = $this->getLimitDeliveryDateDataByOrderItems(
                    $destination,
                    $storeId,
                    $deliveryTypeData['item_ids'],
                    $deliveryType
                );
                $addressGroups[$addressId]['delivery'][$deliveryType]['date_info'] = $result;
            }
        }

        return $addressGroups;
    }

    /**
     * modify data before use
     *
     * @param mixed $result
     * @return mixed
     */
    public function prepareGroupDeliveryAddressData($result)
    {
        foreach ($result as $addressId => $addressGroup) {
            foreach ($addressGroup['delivery'] as $deliveryType => $deliveryTypeData) {
                if ($deliveryType == Delitype::COOL_NORMAL_DM) {
                    $result = $this->deliveryDate->getNameGroup($deliveryTypeData['delivery_type_list']);
                    $result[$addressId]['delivery'][$deliveryType]['delivery_type_name'] = $result;
                }
            }
        }

        return $result;
    }

    /**
     * @param mixed $destination
     * @param int $storeId
     * @param mixed $orderItemIds
     * @param mixed $deliveryType
     * @return array|bool
     */
    protected function getLimitDeliveryDateDataByOrderItems($destination, $storeId, $orderItemIds, $deliveryType)
    {
        $collectionData = $this->deliveryDate->getAssignationByOrderItem($orderItemIds);

        //get assignation warehouse for some item same delivery type
        $assignationGroupByDeliveryType = $this->deliveryDate->calculateWarehouseGroupByCollection(
            $destination,
            $collectionData,
            $storeId
        );

        if (is_array($assignationGroupByDeliveryType)) {
            $calendarData = $this->deliveryTypeAdminHelper->getCalendarInfoByDeliveryTypeData(
                $deliveryType,
                $assignationGroupByDeliveryType,
                $destination['region_code'],
                true
            );
            $result = isset($assignationGroupByDeliveryType['items']) ? $assignationGroupByDeliveryType['items'] : [];
            $calendarData['assignation'] = $result;

            return $calendarData;
        }

        return false;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressIdsForEdit()
    {
        if ($this->orderItemIdToAddressId === null) {
            $itemIds = [];
            foreach ($this->getOrder()->getAllItems() as $item) {
                $itemIds[] = $item->getId();
            }

            $this->orderItemIdToAddressId = $this->_addressHelper->getAddressIdsByOrderItemIdsForEdit($itemIds);
        }

        return $this->orderItemIdToAddressId;
    }

    /**
     * @param int $addressId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getAddressObjById($addressId)
    {
        if (!isset($this->addressIdToObject[$addressId])) {
            if ($addressId) { // multiple address
                $this->addressIdToObject[$addressId] = $this->addressFactory->create()->load($addressId);
            } else { //single address
                $this->addressIdToObject[$addressId] = $this->getOrder()->getShippingAddress();
            }
        }

        return $this->addressIdToObject[$addressId];
    }

    /**
     * @param int $addressId
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormattedAddress($addressId)
    {
        $address = $this->_getAddressObjById($addressId);
        if ($address && $this->isOrderCvsCreateByCommand($this->getOrder())) {
            return null;
        }
        return $this->_getFormattedAddressByObject($address);
    }

    /**
     * @param mixed $address
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getFormattedAddressByObject($address)
    {
        if ($address instanceof \Magento\Sales\Model\Order\Address && $address->getId()) {
            $formatType = $this->addressConfig->getFormatByCode('html');
            if (!$formatType || !$formatType->getRenderer()) {
                return '';
            }
            return $formatType->getRenderer()->renderArray($address->getData());
        }

        return '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSaveDeliveryInfoUrl()
    {
        return $this->getUrl('riki_sales/order_edit/saveDelivery', ['order_id'   => $this->getOrder()->getId()]);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChangeShippingAddressUrl()
    {
        return $this->getUrl('riki_sales/order_edit/changeAddress', ['order_id'   => $this->getOrder()->getId()]);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function allowedToEditDeliveryInfo()
    {
        return $this->salesAdminHelper->isAllowToChangeDeliveryInfo($this->getOrder());
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isSubscriptionCourseOrder()
    {
        return $this->getSubscriptionCourseId();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuote()
    {
        if ($this->quote === null) {
            $this->quote = $this->rikiSalesHelper->getQuoteByOrder($this->getOrder());
        }

        return $this->quote;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptionCourseId()
    {
        return $this->getQuote()->getRikiCourseId();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFrequencyId()
    {
        return $this->getQuote()->getRikiFrequencyId();
    }

    /**
     * Get course info
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCourseInfo()
    {
        $objCourseInfo = new \Magento\Framework\DataObject();
        $objCourseInfo->setData([
            'intervalFrequency' => '',
            'unitFrequency' => '',
            'isAllowChangeNextDD' => '',
        ]);

        if (!$this->getSubscriptionCourseId()) {
            return $objCourseInfo;
        }

        $courseId = $this->getSubscriptionCourseId();
        $objCourse = $this->courseFactory->create()->load($courseId);

        $frequencyId = $this->getFrequencyId();

        $objFrequency = $this->frequencyFactory->create()->load($frequencyId);

        $objCourseInfo->setData('intervalFrequency', $objFrequency->getData('frequency_interval'));
        $objCourseInfo->setData('unitFrequency', $objFrequency->getData('frequency_unit'));
        $objCourseInfo->setData('isAllowChangeNextDD', $objCourse->isAllowChangeNextDeliveryDate());

        return $objCourseInfo;
    }

    /**
     * Get current date server
     *
     * @return string
     */
    public function getCurrentDateServer()
    {
        return $this->rikiSalesHelper->getCurrentDateServer();
    }

    /**
     * Check this order used CSV payment method
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isCsvPayment()
    {
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        if (\Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE == $paymentMethod) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function canEditAddress()
    {
        return $this->_addressHelper->canEditAddress($this->getOrder()) &&
            count($this->getAddressListOfCurrentCustomer());
    }

    /**
     * @return array|\Magento\Customer\Api\Data\AddressInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressListOfCurrentCustomer()
    {
        if ($this->addressList === null) {
            $this->addressList = [];

            if ($this->getOrder()->getCustomerId()) {
                $this->addressList = $this->salesAdminHelper->getAddressListByCustomerId(
                    $this->getOrder()->getCustomerId()
                );
            }
        }

        return $this->addressList;
    }

    /**
     * Represent customer address in 'online' format.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    public function getAddressAsString(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        return $this->escapeHtml($this->_addressHelper->getAddressAsOneLineString($address));
    }

    /**
     * get customer address id (for single address case)
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerAddressIdFromOrder()
    {
        $shippingAddress = $this->getOrder()->getShippingAddress();

        if ($shippingAddress) {
            return $shippingAddress->getCustomerAddressId();
        }

        return null;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function getDetailShippingFee()
    {
        $result = $this->shippingProviderHelper->parseShippingFee($this->getQuote());

        if (!$this->getOrder()->getIsMultipleShipping()) {
            foreach ($this->getAddressGroups() as $addressId => $addressData) {
                $result[$addressId] = array_shift($result);
            }
        } else {
            $orderAddressToCustomerAddress = $this->_addressHelper->getOrderAddressToCustomerAddressesByOrder(
                $this->getOrder()
            );

            $newResult = [];

            foreach ($orderAddressToCustomerAddress as $orderAddressId => $customerAddressId) {
                foreach ($result as $shippingFeeAddressId => $shippingFeeAddressData) {
                    if ($shippingFeeAddressId == 0) {
                        $shippingFeeAddressId = $this->_addressHelper->getDefaultShippingAddress(
                            $this->getQuote()->getCustomer()
                        );
                    }

                    if ($shippingFeeAddressId == $customerAddressId && !isset($newResult[$orderAddressId])) {
                        $newResult[$orderAddressId] = $shippingFeeAddressData;
                    }
                }
            }

            $result = $newResult;
        }

        return $result;
    }

    /**
     * @param mixed $deliveryType
     * @return null|string
     */
    public function getDeliveryTypeForShippingFeeData($deliveryType)
    {
        return $this->deliveryTypeAdminHelper->prepareDeliveryType($deliveryType);
    }

    /**
     * @return float|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultShippingFee()
    {
        return $this->shippingProviderHelper->getShippingPrice(
            0,
            $this->getOrder()->getShippingAddress(),
            $this->getOrder()->getStore(),
            true
        );
    }

    /**
     * @param \Riki\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderCvsCreateByCommand($order)
    {
        if ($order->getPayment()->getMethod() == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            $valueConfig = $this->scopeConfig->getValue(
                ConstantInterface::CONFIG_PATH_COMMAND_CREATE_ORDER_CVS_PAYMENT_SKU
            );
            $productSkus = ($valueConfig) ? array_map('trim', explode(';', strtolower($valueConfig))) : null;
            if (!empty($productSkus)) {
                foreach ($order->getItems() as $item) {
                    if (in_array(strtolower($item->getSKu()), $productSkus)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
