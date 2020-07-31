<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\Indexer\Handler;

use Magento\Framework\Indexer\HandlerInterface;
use Magento\Framework\App\ResourceConnection\SourceProviderInterface;


class ShoshaAttributeNoJoin implements HandlerInterface
{
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->eavConfig = $eavConfig;
        $this->_resource = $resource;
    }

    /**
     * @param SourceProviderInterface $source
     * @param string $alias
     * @param array $fieldInfo
     */
    public function prepareSql(SourceProviderInterface $source, $alias, $fieldInfo)
    {
       return;
    }
}
