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

namespace Riki\Tax\Block\Adminhtml\Order\Create\Totals;

use Magento\Framework\Pricing\PriceCurrencyInterface;
/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Block\Adminhtml\Order\Create\Totals
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Tax extends \Magento\Sales\Block\Adminhtml\Order\Create\Totals\Tax
{
    /**
     * Helper Data
     *
     * @var \Riki\Tax\Helper\Data $rikiTaxHelper Data
     */
    protected $rikiTaxHelper;

    /**
     * Tax constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context       $context
     * @param \Magento\Backend\Model\Session\Quote    $sessionQuote  $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create  $orderCreate   $orderCreate
     * @param PriceCurrencyInterface                  $priceCurrency $priceCurrency
     * @param \Magento\Sales\Helper\Data              $salesData     $salesData
     * @param \Magento\Sales\Model\Config             $salesConfig   $salesConfig
     * @param \Riki\Tax\Helper\Data                   $rikiTaxHelper $rikiTaxHelper
     * @param array                                   $data          $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Helper\Data $salesData,
        \Magento\Sales\Model\Config $salesConfig,
        \Riki\Tax\Helper\Data $rikiTaxHelper,
        array $data = []
    ) {
        $this->rikiTaxHelper = $rikiTaxHelper;

        parent::__construct(
            $context, $sessionQuote, $orderCreate,
            $priceCurrency, $salesData, $salesConfig, $data
        );
    }

    /**
     * Get Riki Tax when create Order in admin
     *
     * @return number
     */
    public function getRikiTax()
    {
        $quote = $this->getQuote();
        $totalTax = $this->rikiTaxHelper->getTaxRiki($quote);
        return $totalTax;
    }
}