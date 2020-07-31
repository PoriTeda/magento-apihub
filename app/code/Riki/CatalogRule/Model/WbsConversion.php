<?php

namespace Riki\CatalogRule\Model;

class WbsConversion extends \Magento\Framework\Model\AbstractModel
{
    const DEFAULT_TIME = '00:00:00';
    const CACHE_TAG = 'riki_wbs_conversion';
    const STATUS_ACTIVE = 1;

    protected function _construct()
    {
        $this->_init('Riki\CatalogRule\Model\ResourceModel\WbsConversion');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
