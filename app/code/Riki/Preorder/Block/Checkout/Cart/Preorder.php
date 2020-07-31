<?php

namespace  Riki\Preorder\Block\Checkout\Cart;

use Magento\Framework\View\Element\Template;

class Preorder extends \Magento\Framework\View\Element\Template
{
    const ITEM = 'item';
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    /**
     * Note constructor.
     * @param Template\Context $context
     * @param \Riki\Preorder\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Riki\Preorder\Helper\Data $helper,
        array $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function canShowBlock()
    {
        return $this->helper->preordersEnabled() && $this->helper->getQuoteItemIsPreorder($this->getItem());
    }


    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem
     */
    public function setItem(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem)
    {
        $this->setData(static::ITEM, $quoteItem);
        return $this;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Item\AbstractItem
     */
    public function getItem()
    {
        return $this->getData(static::ITEM);
    }

    public function getPreorderNote()
    {
        return $this->helper->getQuoteItemPreorderNote($this->getItem());
    }

}
