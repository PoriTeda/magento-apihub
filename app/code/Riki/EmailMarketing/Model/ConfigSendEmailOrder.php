<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\EmailMarketing\Model;


/**
 * Configuration entry point for client using
 */
class ConfigSendEmailOrder extends \Magento\Cron\Model\Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        \Magento\Cron\Model\Config\Data $configData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($configData);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Return cron full cron jobs
     *
     * @return array
     */

    public function getJobs()
    {
        $data = $this->_configData->getJobs();

        if(isset($data['default']) && $data['default']['sales_send_order_emails'] && isset($data['default']['sales_send_order_emails']['schedule']))
        {
            $schedule = $this->_scopeConfig->getValue('sales_email/general/send_order_schedule', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
            if ($schedule !=''){
                $data['default']['sales_send_order_emails']['schedule'] = trim($schedule);
            }
        }
        return $data;
    }
}