<?php

namespace Riki\DeliveryType\Helper;

use Riki\DeliveryType\Model\Delitype;

class Admin extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_quoteSession;
	protected $_quoteItemAddressDdateProcessor;
	protected $_deliveryDate;
	protected $_pointOfSaleFactory;

	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Backend\Model\Session\Quote $sessionQuote,
		\Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
		\Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
		\Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor $quoteItemAddressDdateProcessor
	){
		parent::__construct($context);
		$this->_quoteSession = $sessionQuote;
		$this->_quoteItemAddressDdateProcessor = $quoteItemAddressDdateProcessor;
		$this->_deliveryDate = $deliveryDate;
		$this->_pointOfSaleFactory = $pointOfSaleFactory;
	}

    /**
     * @return \Riki\DeliveryType\Model\DeliveryDate
     */
	public function getDeliveryTypeModel()
    {
        return $this->_deliveryDate;
    }

    /**
     * @return \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor
     */
    public function getQuoteItemAddressDdateProcessor()
    {
        return $this->_quoteItemAddressDdateProcessor;
    }

	/**
	 * @param \Magento\Quote\Model\Quote $quote
	 * @return array
	 */
	public function getDeliveryInfoForCurrentSingleAddressQuote(\Magento\Quote\Model\Quote $quote){

        /** var $destination */
        $destination = array(
            "country_code" => $quote->getShippingAddress()->getCountryId(),
            "region_code"  => $quote->getShippingAddress()->getRegionCode(),
            "postcode"     => $quote->getShippingAddress()->getPostcode(),
        );

	    return $this->getDeliveryInfoForQuoteWithSpecificAddress($quote, $destination);
	}

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $destination
     * @return array
     */
	public function getDeliveryInfoForQuoteWithSpecificAddress(\Magento\Quote\Model\Quote $quote, array $destination)
    {
        $result = [];
        $groupsDeliveryType = $this->_quoteItemAddressDdateProcessor->splitQuoteByDeliveryType($quote->getAllVisibleItems());

        foreach($groupsDeliveryType as $deliveryType	=>	$itemIds){
            //get assignation warehouse for some item same delivery type
            $assignationGroupByDeliveryType = $this->_deliveryDate->calculateWarehouseGroupByItem(
                $destination, $quote, $itemIds
            );

            if($assignationGroupByDeliveryType){
                $dataCalendar = $this->getCalendarInfoByDeliveryTypeData($deliveryType, $assignationGroupByDeliveryType, $destination['region_code']);
                $result[$deliveryType] = $dataCalendar;
                $result[$deliveryType]['assignation'] = isset($assignationGroupByDeliveryType['items'])? $assignationGroupByDeliveryType['items'] : [];
            }
        }

        return $result;
    }

	/**
	 * @param $deliveryType
	 * @param array $assignationGroupByDeliveryType
	 * @param $regionCode
	 * @param bool|false $orderMode
	 * @return array
	 */
	public function getCalendarInfoByDeliveryTypeData($deliveryType, array $assignationGroupByDeliveryType, $regionCode, $orderMode = false){
        $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                $pointOfSale = $this->_pointOfSaleFactory->create()->load($posId);
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        $dataCalendar = $this->_quoteItemAddressDdateProcessor->getDeliveryCalendar($listWh, [$deliveryType], $regionCode);

        if ($deliveryType == \Riki\DeliveryType\Model\Delitype::DM) {
            $dataCalendar['only_dm'] = 1;
            $dataCalendar['timeslot'] = false;
        } else {
            $dataCalendar['only_dm'] = 0;
            $dataCalendar['timeslot'] = $this->_deliveryDate->toOptions();
        }

		return $dataCalendar;
	}

	/**
	 * @param null $type
	 * @return null|string
	 */
	public function prepareDeliveryType($type = null){
		switch($type){
			case Delitype::CHILLED:
				break;
			case Delitype::COSMETIC:
				break;
			case Delitype::COLD:
				break;
			default:
				$type = 'CoolNormalDm';
		}

		return $type;
	}
}