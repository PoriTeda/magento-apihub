<?php

namespace Riki\ProductActive\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Recipient module enabled config path
     */
    const CONFIG_CRON_ACTIVE = 'productactive/setting/enable';

    /**
     * Recipient cron expression config path
     */
    const CONFIG_CRON_EXPRESSION = 'productactive/setting/expression';


    /**
     * Get Cron expression value config
     *
     * @return mixed
     */
    public function getCronExpression()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cronExpression = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPRESSION, $storeScope);

        return $cronExpression;
    }

    /**
     * Check whether or not the module output is enabled in Configuration
     *
     * @return bool
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_CRON_ACTIVE, $storeScope);
        return $isEnabled;
    }

}