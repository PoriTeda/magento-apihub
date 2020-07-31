<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Config extends Template
{
    protected $providersConfig;

    /**
     * @param Context                               $context
     * @param array                                 $data
     * @param \Amasty\Smtp\Model\Provider\Config    $providersConfig
     */
    public function __construct(
        Context $context, array $data = [],
        \Amasty\Smtp\Model\Provider\Config $providersConfig
    ) {
        $this->providersConfig = $providersConfig;
        parent::__construct($context, $data);
    }

    public function getProviders()
    {
        return $this->providersConfig->get();
    }

    protected function _toHtml()
    {
        if ($this->_request->getParam('section') == 'amsmtp')
            return parent::_toHtml();
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return [
            'store' => $this->getRequest()->getParam('store'),
            'website' => $this->getRequest()->getParam('website')
        ];
    }
}
