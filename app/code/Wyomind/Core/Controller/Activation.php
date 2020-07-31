<?php

/**
 * Copyright © 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\Core\Controller;

/**
 * License front abstract action
 */
class Activation extends \Magento\Framework\App\Action\Action
{

    protected $_resultPageFactory = null;


    /**
     * @param \Magento\Framework\App\Action\Context                     $context
     * @param \Magento\Framework\App\Config\MutableScopeConfigInterface $scopeConfig
     * @param \Magento\Config\Model\ResourceModel\Config                     $config
     * @param \Magento\Framework\Model\Context                          $context_
     * @param \Magento\Framework\Session\SessionManagerInterface        $session
     * @param \Magento\Framework\View\Result\PageFactory                $resultPageFactory
     * @param \Wyomind\Core\Helper\Data                                 $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}
