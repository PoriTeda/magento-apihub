<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Logger;

use Amasty\Smtp\Model\Debug;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

class DebugLogger
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Core Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $coreDate;
    
    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * DebugLogger constructor.
     *
     * @param ObjectManagerInterface   $objectManager
     * @param ScopeConfigInterface     $scopeConfig
     * @param \Amasty\Smtp\Helper\Data $helper
     * @param DateTime                 $coreDate
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        \Amasty\Smtp\Helper\Data $helper,
        DateTime $coreDate
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->coreDate = $coreDate;
        $this->helper = $helper;
    }

    public function log($message)
    {
        $storeId = $this->helper->getCurrentStore();
        
        if (!$this->scopeConfig->isSetFlag(
            'amsmtp/general/debug', ScopeInterface::SCOPE_STORE, $storeId
        ))
            return;
        /** @var Debug $debugMessage */
        $debugMessage = $this->objectManager->create('Amasty\Smtp\Model\Debug');
        $debugMessage->setData([
            'created_at'        => $this->coreDate->gmtDate(),
            'message'           => $message
        ]);

        $debugMessage->save();
    }

    public function autoClear()
    {
        $days = $this->scopeConfig->getValue('amsmtp/clear/debug');
        if ($days) {
            $this->log(__('Starting to auto clear debug log (after %1 days)', $days));

            $logModel = $this->objectManager->get('Amasty\Smtp\Model\ResourceModel\Debug');
            $logModel->clear($days);
        }
    }
}
