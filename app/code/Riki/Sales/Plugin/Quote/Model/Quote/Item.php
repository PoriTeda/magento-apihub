<?php
namespace Riki\Sales\Plugin\Quote\Model\Quote;

class Item
{
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $helper;

    /**
     * Item constructor.
     * @param \Riki\Sales\Helper\Data $helper
     */
    public function __construct(
        \Riki\Sales\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param $value
     * @return array
     */
    public function beforeSetPrice(\Magento\Quote\Model\Quote\Item $subject, $value)
    {
        $quote = $subject->getQuote();
        if($quote!=null)
        {
            if ($this->helper->isFreeOfChargeOrder($subject->getQuote())) {
                $value = 0;
            }
        }

        return [$value];
    }
}
