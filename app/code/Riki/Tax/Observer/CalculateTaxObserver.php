<?php
/**
 * *
 *  ImportExport
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\ImportExport
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Tax\Observer;

use Magento\Framework\Event\ObserverInterface;
/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Observer
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class CalculateTaxObserver implements ObserverInterface
{
    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * CalculateTaxObserver constructor.
     *
     * @param \Riki\Tax\Helper\Data $taxHelper
     */
    public function __construct(
        \Riki\Tax\Helper\Data $taxHelper
    ) {
        $this->taxHelper = $taxHelper;
    }

    /**
     * Set tax
     *
     * @param \Magento\Framework\Event\Observer $observer $observer
     *
     * @return this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * Get quote
         *
         * @var \Magento\Quote\Model\Quote $quote
        */
        $quote = $observer->getQuote();

        $commissionPercent = $this->taxHelper->getCustomerCommissionPercent($quote);

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $this->taxHelper->renderTaxRiki($quoteItem, $commissionPercent);
        }
    }
}
