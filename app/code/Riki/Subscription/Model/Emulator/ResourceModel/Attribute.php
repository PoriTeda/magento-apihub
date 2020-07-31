<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel;

class Attribute
 extends \Magento\Sales\Model\ResourceModel\Attribute
{
    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection('subscription');
        }
        return $this->connection;
    }
}