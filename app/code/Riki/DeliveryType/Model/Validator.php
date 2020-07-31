<?php

namespace Riki\DeliveryType\Model;

class Validator
{
    /**
     * @var \Riki\DeliveryType\Helper\Admin
     */
    protected $deliveryTypeAdminHelper;

    /**
     * @var QuoteItemAddressDdateProcessor
     */
    protected $quoteItemAddressDdateProcessor;

    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    protected $orderAddressRepository;

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSalesFactory;

    /**
     * @var array
     */
    protected $orderAddressIdToRegionCode = [];

    /**
     * @var array
     */
    protected $warehouseIdToCode = [];

    /**
     * Validator constructor.
     * @param \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     */
    public function __construct(
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
    )
    {
        $this->deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->quoteItemAddressDdateProcessor = $deliveryTypeAdminHelper->getQuoteItemAddressDdateProcessor();
        $this->orderAddressRepository = $orderAddressRepository;
        $this->pointOfSalesFactory = $pointOfSaleFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function validateOrderDeliveryDateData(\Magento\Sales\Model\Order $order)
    {

        $groups = $this->groupOrderItems($order);

        foreach ($groups as $addressId  =>  $deliveryTypeGroups) {
            foreach ($deliveryTypeGroups    as  $deliveryTypeGroup  =>  $deliveryTypeAssignedData) {

                $regionCode = $this->getRegionCodeByOrderAddressId($addressId);

                list($minDate, $maxDate) = $this->getDeliveryDateLimit($regionCode, $deliveryTypeAssignedData['warehouses_code'], $deliveryTypeAssignedData['delivery_types']);

                foreach ($deliveryTypeAssignedData['item_ids'] as $itemId) {
                    $item = $order->getItemsCollection()->getItemById($itemId);

                    $itemDeliveryDate = $item->getData('delivery_date');

                    if (!empty($itemDeliveryDate)) {
                        if (
                            strtotime($itemDeliveryDate) < strtotime($minDate) ||
                            strtotime($itemDeliveryDate) > strtotime($maxDate)
                        ) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function groupOrderItems(\Magento\Sales\Model\Order $order)
    {
        $assignation = $order->getData('assignation');

        try {
            $assignation = \Zend_Json::decode($assignation);
        } catch (\Exception $e) {
            return [];
        }

        $orderItemCollection = $order->getItemsCollection();

        $groups = [];

        foreach ($assignation['items'] as $itemId   =>  $assignedData) {

            /** @var \Magento\Sales\Model\Order\Item $item */
            $item = $orderItemCollection->getItemById($itemId);

            if ($item) {
                $addressId = $item->getData('address_id');

                if (!$addressId) {
                    $addressId = $order->getShippingAddress()->getId();
                }

                $deliveryType = $this->deliveryTypeAdminHelper->prepareDeliveryType($item->getData('delivery_type'));

                if (!isset($groups[$addressId])) {
                    $groups[$addressId] = [];
                }

                if (!isset($groups[$addressId][$deliveryType])) {
                    $groups[$addressId][$deliveryType] = [
                        'warehouses_code' =>  [],
                        'item_ids'  =>  [],
                        'delivery_types' => []
                    ];
                }

                $groups[$addressId][$deliveryType]['item_ids'][] = $item->getId();
                $groups[$addressId][$deliveryType]['delivery_types'] = array_merge($groups[$addressId][$deliveryType]['delivery_types'], [$item->getData('delivery_type')]);
                $groups[$addressId][$deliveryType]['warehouses_code'] = array_merge($groups[$addressId][$deliveryType]['warehouses_code'] + array_map(function ($warehouseId) {
                        return $this->getWarehouseCodeById($warehouseId);
                    }, array_keys($assignedData['pos'])));
            }
        }

        return $groups;
    }

    /**
     * @param $regionCode
     * @param $warehouseCodes
     * @param $deliveryTypes
     * @return array
     */
    protected function getDeliveryDateLimit($regionCode, $warehouseCodes, $deliveryTypes)
    {
        $limitDateInfo = $this->quoteItemAddressDdateProcessor->getDeliveryCalendar($warehouseCodes, $deliveryTypes, $regionCode);

        if (count($limitDateInfo['deliverydate'])) {
            $minDate = date('Y-m-d', strtotime(end($limitDateInfo['deliverydate']) . ' +1 day'));
        } else {
            $minDate = date('Y-m-d');
        }

        $maxDate = date('Y-m-d', strtotime($minDate . ' +' . intval($limitDateInfo['period']) . ' days'));

        return [$minDate, $maxDate];
    }

    /**
     * @param $orderAddressId
     * @return null|string
     */
    protected function getRegionCodeByOrderAddressId($orderAddressId)
    {
        if (!isset($this->orderAddressIdToRegionCode[$orderAddressId])) {
            try {
                $orderAddress = $this->orderAddressRepository->get($orderAddressId);
            } catch (\Exception $e) {
                $this->orderAddressIdToRegionCode[$orderAddressId] = null;
                return null;
            }

            $orderAddress->setRegion(null); // pass getRegionCode function

            $this->orderAddressIdToRegionCode[$orderAddressId] =  $orderAddress->getRegionCode();
        }

        return $this->orderAddressIdToRegionCode[$orderAddressId];
    }

    /**
     * @param $warehouseId
     * @return mixed
     */
    protected function getWarehouseCodeById($warehouseId)
    {
        if (!isset($this->warehouseIdToCode[$warehouseId])) {
            $warehouse = $this->pointOfSalesFactory->create()->load($warehouseId);

            $this->warehouseIdToCode[$warehouseId] = $warehouse->getStoreCode();
        }

        return $this->warehouseIdToCode[$warehouseId];
    }
}