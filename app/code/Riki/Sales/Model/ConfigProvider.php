<?php

namespace Riki\Sales\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Riki\Tax\Helper\Data $taxHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->taxHelper = $taxHelper;
    }
    /**
     * Get tax_riki
     * Get discount_amount_excl_tax
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();

        $items = $quote->getAllVisibleItems();
        $totalRikiTax = 0;
        $totalDiscountAmountExclTax = 0;
        foreach ($items as $item) {
            //$output['tax_riki'] = $item->getId();return $output;
            $totalRikiTax += $item->getData('tax_riki');
            $totalDiscountAmountExclTax += $item->getData('discount_amount_excl_tax');
        }

        $paymentFeeTax = $this->taxHelper->getPaymentFeeTax($quote);

        $output['tax_riki'] = $totalRikiTax + $paymentFeeTax;
        $output['discount_amount_excl_tax'] = $totalDiscountAmountExclTax;
        return $output;
    }

}

