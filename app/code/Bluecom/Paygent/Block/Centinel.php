<?php

namespace Bluecom\Paygent\Block;

class Centinel extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygent;

    /**
     * Centinel constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context Context
     * @param \Bluecom\Paygent\Model\Paygent                   $paygent Paygent
     * @param array                                            $data    Data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bluecom\Paygent\Model\Paygent $paygent,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paygent = $paygent;
    }

    /**
     * Get centinel html
     * 
     * @return mixed
     */
    public function getCentinelHtml()
    {
        return $this->paygent->getCentinelHtml();
    }
}
