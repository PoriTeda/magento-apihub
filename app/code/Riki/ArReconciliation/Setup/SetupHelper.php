<?php
// @codingStandardsIgnoreFile
namespace Riki\ArReconciliation\Setup;

/**
 * @codeCoverageIgnore
 */
class SetupHelper
{
    const defaultAdapter = 'default';
    const checkoutAdapter = 'checkout';
    const salesAdapter = 'sales';

    protected $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->_resourceConnection = $resourceConnection;
    }

    public function getSalesConnection(){
        return $this->_resourceConnection->getConnection(self::salesAdapter);
    }

    public function getCheckoutConnection(){
        return $this->_resourceConnection->getConnection(self::checkoutAdapter);
    }

    public function getDefaultConnection(){
        return $this->_resourceConnection->getConnection(self::defaultAdapter);
    }
}
