<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Edit;

class Template extends \Magento\Framework\View\Element\Template
{

    private $_helper = null;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Wyomind\PointOfSale\Helper\Data $_helper,
        array $data = []
    ) {
    

        $this->_helper = $_helper;
        parent::__construct($context, $data);
    }

    public function getGoogleApiKey()
    {

        return $this->_helper->getGoogleApiKey();
    }
}
