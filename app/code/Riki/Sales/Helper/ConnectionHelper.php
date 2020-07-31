<?php

namespace Riki\Sales\Helper;

class ConnectionHelper
{
    const defaultAdapter = 'default';
    const checkoutAdapter = 'checkout';
    const salesAdapter = 'sales';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * ConnectionHelper constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getSalesConnection(){
        return $this->_resourceConnection->getConnection(self::salesAdapter);
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getCheckoutConnection(){
        return $this->_resourceConnection->getConnection(self::checkoutAdapter);
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getDefaultConnection(){
        return $this->_resourceConnection->getConnection(self::defaultAdapter);
    }
}
