<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Shipping;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class FreeFlag extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{

    /** @var \Riki\Sales\Helper\Admin  */
    protected $helper;

    /**
     * FreeFlag constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Riki\Sales\Helper\Admin $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Riki\Sales\Helper\Admin $helper,
        array $data = []
    )
    {
        $this->helper = $helper;

        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function isSelectedFree(){
        return $this->_sessionQuote->getFreeShippingFlag();
    }

    /**
     * @return bool
     */
    public function isRequiredWbs(){
        if ($this->isSelectedFree()) {
            if (!$this->helper->isFreeOfChargeOrder()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getCurrentWbs()
    {
        return $this->getQuote()->getData('free_shipping_fee_wbs');
    }
}
