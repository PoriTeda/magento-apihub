<?php
/**
 * *
 *  Tax
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\Tax
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Tax\Model\Quote\Total;
/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Model\Quote\Total
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class TaxRiki extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Helper data
     *
     * @var \Riki\Tax\Helper\Data
     */
    protected $helperData;


    /**
     * Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Payment interface
     *
     * @var \Magento\Quote\Api\Data\PaymentInterface
     */
    protected $payment;
    /**
     * Collect grand total address amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return $this
     */
    protected $quoteValidator = null;
    /**
     * Stage
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * Quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $quoteBackendSession;
    /**
     * Request
     *
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    /**
     * TaxRiki
     *
     * @var
     */
    protected $taxRiki;


    /**
     * Fee constructor.
     *
     * @param \Magento\Quote\Model\QuoteValidator      $quoteValidator      quote validator
     * @param \Magento\Checkout\Model\Session          $checkoutSession     checkout session
     * @param \Magento\Backend\Model\Session\Quote     $quoteBackendSession quote backend
     * @param \Magento\Quote\Api\Data\PaymentInterface $payment             payment
     * @param \Magento\Framework\App\State             $appState            app state
     * @param \Riki\Tax\Helper\Data                    $helperData          helper
     * @param \Magento\Framework\Webapi\Rest\Request   $request             request
     */
    public function __construct(
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $quoteBackendSession,
        \Magento\Quote\Api\Data\PaymentInterface $payment,
        \Magento\Framework\App\State $appState,
        \Riki\Tax\Helper\Data $helperData,
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->setCode('tax_riki');
        $this->quoteValidator = $quoteValidator;
        $this->helperData = $helperData;
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->appState = $appState;
        $this->quoteBackendSession = $quoteBackendSession;
        $this->request = $request;
    }

    /**
     * Collect totals process.
     *
     * @param \Magento\Quote\Model\Quote                          $quote              quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment shipping assignment
     * @param \Magento\Quote\Model\Quote\Address\Total            $total              total
     *
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        return $this;
    }

    /**
     * Assign subtotal amount and label to address object
     *
     * @param \Magento\Quote\Model\Quote               $quote quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total total
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $taxRiki = $this->helperData->getTaxRiki($quote);
        if (!$taxRiki) {
            $taxRiki = 0;
        }

        $result = [
            'code' => 'tax_riki',
            'title' => __('Tax'),
            'value' => $taxRiki
        ];
        return $result;
    }

    /**
     * Get Subtotal label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Tax Riki');
    }

    /**
     * Check request from web api
     *
     * @return bool
     */
    public function checkRequestWebApi()
    {
        $pathInfo =  $this->request->getPathInfo();
        $patternStep5 ='#V1/mm/carts/order/payment-information#';
        if (preg_match($patternStep5, $pathInfo, $match)) {
            return true;
        }

        $pattern ='#/V1/mm/carts/#';
        if (preg_match($pattern, $pathInfo, $match)) {
            return true;
        }
        return false;

    }
}