<?php

namespace Riki\Sales\Block\Adminhtml\Order\Totals;

/**
 * Adminhtml order tax totals block.
 */
class Tax extends \Magento\Sales\Block\Adminhtml\Order\Totals\Tax
{
    /**
     * @var \Riki\Tax\Helper\Data Data
     */
    protected $_rikiTaxHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Tax\Model\Sales\Order\TaxFactory $taxOrderFactory,
        \Magento\Sales\Helper\Admin $salesAdminHelper,
        \Riki\Tax\Helper\Data $rikiTaxHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = []
    ) {
        $this->_rikiTaxHelper = $rikiTaxHelper;
        $this->_quoteRepository = $quoteRepository;
        $this->_quoteFactory = $quoteFactory;

        parent::__construct($context, $taxConfig, $taxHelper, $taxCalculation, $taxOrderFactory,
            $salesAdminHelper, $data);
    }

    /**
     * Get riki taxes applied to order.
     *
     * @return number
     */
    public function getTaxRiki()
    {
        $order = $this->getOrder();
        $items = $order->getAllVisibleItems();
        $totalTax = 0;
        foreach ($items as $item) {
            $totalTax += $item->getData('tax_riki');
        }

        return $totalTax;
    }

    /**
     * Get riki taxes total applied to order.
     *
     * @return number
     */
    public function getTaxRikiTotal()
    {
        $quoteId = $this->getOrder()->getQuoteId();
        $quote = $this->_quoteFactory->create()->load($quoteId);
        $totalTax = $this->_rikiTaxHelper->getTaxRiki($quote);

        return $totalTax;
    }

    public function getCommissionRiki()
    {
        $order = $this->getOrder();
        $items = $order->getAllVisibleItems();
        $totalCommission = 0;
        foreach ($items as $item) {
            $totalCommission += $item->getData('commission_amount');
        }

        return $totalCommission;
    }

    public function formatPrice($amount)
    {
        return $this->getOrder()->formatPrice($amount);
    }
}
