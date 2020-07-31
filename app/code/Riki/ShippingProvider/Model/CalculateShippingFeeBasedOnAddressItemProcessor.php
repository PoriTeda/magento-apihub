<?php
/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShippingProvider\Model;

use Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface;
use Riki\DeliveryType\Model\Delitype;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * CalculateShippingFeeBasedOnAddressItemProcessor
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CalculateShippingFeeBasedOnAddressItemProcessor
    implements CalculateShippingFeeBasedOnAddressItemProcessorInterface
{
    const XML_PATH_FREE_SHIPPING_CONDITION = 'delivery_free_shipping_amount';

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $methodCode = 'riki_shipping';

    /**
     * ScopeConfigInterface
     *
     * @var $scopeConfig ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * LoggerInterface
     *
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * CalculateShippingFeeBasedOnAddressItemProcessor constructor.
     *
     * @param ScopeConfigInterface $scopeConfig     ScopeConfigInterface
     * @param Logger               $loggerInterface Logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $loggerInterface
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $loggerInterface;
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @param string $field   field
     * @param string $storeID storeID
     *
     * @return void|false|string
     */
    public function getConfigData($field, $storeID)
    {
        if (empty($this->methodCode)) {
            return false;
        }
        $path = 'carriers/' . $this->methodCode . '/' . $field;

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeID
        );
    }

    /**
     * Calculate shipping fee for riki shipping method
     * base on customer address interface & cart item
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart      Cart Interface
     * @param array                                 $cartItems Cart Items
     *
     * @return float $finalFee
     */
    public function calculateShippingFeeBaseOnAddressItem(
        \Magento\Quote\Api\Data\CartInterface $cart,
        array $cartItems
    ) {
        $arrayFee = array();
        $freeShippingAmount = floatval(
            $this->getConfigData(
                self::XML_PATH_FREE_SHIPPING_CONDITION,
                $cart->getStoreId()
            )
        );

        /* @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            $deliveryType = "cool";//$item->getData('delivery_type');
            if ($deliveryType) {
                $qty = $item->getQty();
                if ($this->isCoolNormalDmType($deliveryType)) {
                    $qty = 1;
                }
                $arrayFee[$deliveryType] = $this->getShippingTypeFee(
                    $deliveryType,
                    $item,
                    $cart->getStoreId()
                ) * $qty;
                if ($arrayFee[$deliveryType] >= $freeShippingAmount) {
                    $arrayFee[$deliveryType] = 0;
                }
            }
        }
        if ($arrayFee) {
            $arrayFee = $this->calculateCoolNormalDmFee(
                $arrayFee,
                $cart->getStoreId()
            );
        }
        $totalFee = array_sum($arrayFee);

        if ($totalFee >= $freeShippingAmount) {
            $totalFee = 0;
        }

        return $totalFee;
    }

    /**
     * Check Cool Normal Dm delivery type
     *
     * @param array $arrayFee arrayFee
     * @param array $storeID  storeID
     *
     * @return array
     */
    protected function calculateCoolNormalDmFee($arrayFee, $storeID)
    {
        $isCoolExisted = array_key_exists(Delitype::COOL, $arrayFee);
        $isNormalExisted = array_key_exists(Delitype::NORMAl, $arrayFee);
        $isDmExisted = array_key_exists(Delitype::DM, $arrayFee);
        $coolFee = 0;
        $normalFee = 0;
        $dmFee = 0;

        if ($isCoolExisted) {
            $coolFee = $arrayFee[Delitype::COOL];
        }
        if ($isNormalExisted) {
            $normalFee = $arrayFee[Delitype::NORMAl];
        }
        if ($isDmExisted) {
            $dmFee = $arrayFee[Delitype::DM];
        }

        $freeShippingAmount = floatval(
            $this->getConfigData(
                self::XML_PATH_FREE_SHIPPING_CONDITION,
                $storeID
            )
        );
        if ($isCoolExisted) {
            $totalFee = $coolFee + $normalFee + $dmFee;

            if (floatval($totalFee) >= $freeShippingAmount) {
                $arrayFee[Delitype::COOL] = 0;
            }
            $arrayFee[Delitype::NORMAl] = 0;
            $arrayFee[Delitype::DM] = 0;
        } else {
            if ($isNormalExisted) {
                $totalFee = $normalFee + $dmFee;

                if ($totalFee >= $freeShippingAmount) {
                    $arrayFee[Delitype::NORMAl] = 0;
                }
                $arrayFee[Delitype::DM] = 0;
            }
        }

        return $arrayFee;
    }

    /**
     * Get shipping fee
     *
     * @param string                          $deliveryType deliveryType
     * @param \Magento\Quote\Model\Quote\Item $item         item
     * @param string                          $storeID      storeID
     *
     * @return int|float
     */
    protected function getShippingTypeFee($deliveryType, $item, $storeID)
    {
        $field = 'delivery_type_' . $deliveryType . '_rate';
        $fee = floatval($this->getConfigData($field, $storeID));
        $isFreeShipItem = $item->getFreeShipping();

        if ($isFreeShipItem) {
            $fee = 0;
            return $fee;
        }

        return $fee;
    }

    /**
     * Check is Cool Normal Dm
     *
     * @param string $deliveryType deliveryType
     *
     * @return bool
     */
    protected function isCoolNormalDmType($deliveryType)
    {
        if ($deliveryType !== Delitype::COOL
            && $deliveryType !== Delitype::NORMAl
            && $deliveryType !== Delitype::DM
        ) {
            return false;
        }
        return true;
    }
}

