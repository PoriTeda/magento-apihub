<?php
/**
 * *
 *  ImportExport
 *
 *  PHP version 7
 *
 * @category RIKI
 * @package  Riki\ImportExport
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Tax\Helper;

use Magento\Framework\App\Helper\Context;
use Riki\Customer\Model\Config\Source\ShoshaCode;
use Riki\Sales\Model\Order\PaymentMethod;
use Riki\Sales\Helper\Order as OrderHelper;

/**
 * *
 *  Tax
 *
 * @category RIKI
 * @package  Riki\Tax\Helper
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_CONFIG_TAX_CHANGE_DATE = 'tax/tax_change/change_date';
    const XML_CONFIG_COMPARE_TAX = 'tax/tax_change/compare_tax';

    /**
     * Property
     *
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * ShoshaFactory
     *
     * @var \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     */
    protected $shoshaFactory;

    /**
     * @var \Riki\Sales\Model\ResourceModel\Order\Shipment
     */
    protected $shipmentResource;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Riki\Sales\Model\ResourceModel\Order\Shipment $shipmentResource
     */
    public function __construct(
        Context $context,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Riki\Sales\Model\ResourceModel\Order\Shipment $shipmentResource
    ) {
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->customerRepository = $customerRepository;
        $this->shoshaFactory = $shoshaFactory;
        $this->shipmentResource = $shipmentResource;
        parent::__construct($context);
    }

    /**
     * Get riki payment fee tax.
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return number
     */
    public function getPaymentFeeTax($quote)
    {
        // is free payment fee
        if ($quote->getFee() == 0) {
            return 0;
        }
        $paymentMethod = $quote->getPayment()->getMethod();
        $paymentFee = $this->paymentFeeHelper->getPaymentCharge($paymentMethod);
        $paymentRate = $this->paymentFeeHelper->getPaymentTaxRate();
        if ($paymentRate) {
            $paymentRate = $paymentRate / 100;
            $paymentExclTax = ceil($paymentFee / (1 + $paymentRate));
            $tax = $paymentFee - $paymentExclTax;
        } else {
            $tax = 0;
        }

        return $tax;
    }

    /**
     * Get riki shipping fee tax.
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return number
     */
    public function getShippingFeeTax($quote)
    {
        $shippingIncTax = $quote->getShippingAddress()->getShippingInclTax();
        $shippingExcTax = $quote->getShippingAddress()->getShippingAmount();
        $tax = $shippingIncTax - $shippingExcTax;

        return $tax;
    }

    /**
     * Get riki taxes applied to order.
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return number
     */
    public function getTaxRiki($quote)
    {
        $items = $quote->getAllVisibleItems();
        $totalTax = 0;
        foreach ($items as $item) {
            $totalTax += $item->getData('tax_riki');
        }
        return $totalTax;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return integer
     */
    public function getCustomerCommissionPercent($quote)
    {
        $customerRepository = null;

        try {
            $customerRepository = $this->customerRepository->getById($quote->getCustomerId());
        } catch (\Exception $e) {
            $customerRepository = null;
        }

        $commissionPercent = 0;

        if ($customerRepository && $customerRepository->getCustomAttribute('shosha_business_code')) {
            $shoshaBusinessCode = $customerRepository->getCustomAttribute('shosha_business_code')->getValue();

            /**
             * @var \Riki\Customer\Model\ResourceModel\Shosha\Collection $oShoshaCollection
             */
            $oShoshaCollection = $this->shoshaFactory->create()->getCollection()
                ->addFieldToFilter('shosha_business_code', [
                    'in' => [$shoshaBusinessCode]
                    ])
                ->setPageSize(1);

            if ($oShoshaCollection->getSize()) {
                $shoshaCustomer = $oShoshaCollection->getFirstItem();
                if ($shoshaCustomer->hasData('shosha_commission')
                    && ($shoshaCustomer->getData('shosha_code') == ShoshaCode::ITOCHU)
                    && $quote->getPayment()
                    && $quote->getPayment()->getMethod() == PaymentMethod::PAYMENT_METHOD_INVOICED
                ) {
                    $commissionPercent = $shoshaCustomer->getData('shosha_commission');
                }
            }
        }

        return $commissionPercent;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param int $commissionPercent
     *
     */
    public function renderTaxRiki(
        \Magento\Quote\Model\Quote\Item $quoteItem,
        $commissionPercent = 0
    ) {
        $discountAmount = $quoteItem->getData('discount_amount');
        $taxPercent = $quoteItem->getData('tax_percent');
        $rowTotalInclTax = $quoteItem->getData('row_total_incl_tax');

        if ($taxPercent) {
            $taxPercent = $taxPercent / 100;
        }
        $rowTotal = $quoteItem->getData('row_total');

        $discountAmountExclTax = ceil($discountAmount / (1 + $taxPercent));
        $commissionAmount = round(($rowTotal - $discountAmountExclTax) * ($commissionPercent / 100));

        /**
         * We have 2 formulas to calculate tax: RIKI-9303
         */
        if ($commissionAmount) {
            $taxRiki = floor(($rowTotal - $discountAmountExclTax - $commissionAmount) * $taxPercent);
        } else {
            $taxRiki = ($rowTotalInclTax - $discountAmount) - ($rowTotal - $discountAmountExclTax);
        }

        if ($taxRiki < 0) {
            $taxRiki = 0;
        }

        $quoteItem->setData('discount_amount_excl_tax', $discountAmountExclTax);
        $quoteItem->setData('commission_amount', $commissionAmount);
        $quoteItem->setData('tax_riki', $taxRiki);
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function canApplyTaxChangeFromDate($orderId)
    {
        $applyDate = $this->scopeConfig->getValue(
            self::XML_CONFIG_TAX_CHANGE_DATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $shippedOutDate = $this->shipmentResource->getShippedOutDateByOrderId($orderId);
        return strtotime($shippedOutDate) >= strtotime($applyDate);
    }

    /**
     * @return int
     */
    public function getCompareTaxPercent()
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_CONFIG_COMPARE_TAX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param float $itemTaxRatePercent
     * @return int
     */
    public function compareTaxRateChange($itemTaxRatePercent)
    {
        if ((int)$itemTaxRatePercent == OrderHelper::TAX_8_PERCENT) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param float $itemTaxRate
     * @param string $shippedOutDate
     * @return int
     */
    public function getTaxExceptionalFlag($itemTaxRate, $shippedOutDate)
    {
        $applyTaxChangeDate = $this->scopeConfig->getValue(
            self::XML_CONFIG_TAX_CHANGE_DATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (((int)$itemTaxRate == OrderHelper::TAX_8_PERCENT
                && (strtotime($shippedOutDate) < strtotime($applyTaxChangeDate)))
            || (int)$itemTaxRate == OrderHelper::TAX_10_PERCENT ) {
            return 0;
        } else {
            return 1;
        }
    }
}
