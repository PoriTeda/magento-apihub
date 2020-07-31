<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Model\Provider;

use Amasty\Smtp\Model\Provider\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\Data;

class Config extends Data
{
    /**
     * @param \Amasty\Smtp\Model\Provider\Config\Reader $reader
     * @param CacheInterface $cache
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache
    ) {
        parent::__construct($reader, $cache, 'amsmtp_providers');
    }
}
