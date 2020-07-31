<?php

namespace Riki\Subscription\Model\Emulator;

use Riki\Subscription\Model\Emulator\Config;

class TableManager{

    /**
     * @var \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    protected $resourceConnection ;

    protected $connection;

    protected $_quoteConnection;


    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->resourceConnection = $resourceConnection;
        $this->connection = $resourceConnection->getConnection('subscription'); // get sales connection not
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getQuoteConnection(){
        if(is_null($this->_quoteConnection))
            $this->_quoteConnection = $this->resourceConnection->getConnection('subscription');
        return $this->_quoteConnection;
    }

    /**
     * @return void
     */
    public function createTemporaryTables(){
        /* do no thing */
        /* create cart - table */
        $temporaryCartTableName = Config::getCartTmpTableName();
        $temporaryCartItemTableName = Config::getCartItemTmpTableName();
        $temporaryCartItemOptionTableName = Config::getCartItemOptionTmpTableName();
        $temporaryAddressTableName = Config::getAddressTmpTableName();
        $temporaryAddressItemTableName = Config::getAddressItemTmpTableName();
        $temporaryPaymentTableName = Config::getCartPaymentTmpTableName();
        $temporaryCartShippingRateTableName = Config::getCartShippingRateTmpTableName();

        $quoteConnection = $this->getQuoteConnection();

        $this->createTmpTable(
            Config::CART_TABLE_NAME , $temporaryCartTableName, $quoteConnection
        );
        $this->createTmpTable(
            Config::CART_ITEM_TABLE_NAME , $temporaryCartItemTableName, $quoteConnection
        );
        $this->createTmpTable(
            Config::CART_ITEM_OPTION , $temporaryCartItemOptionTableName, $quoteConnection
        );
        $this->createTmpTable(
            Config::CART_ADDRESS_TABLE_NAME , $temporaryAddressTableName, $quoteConnection
        );
        $this->createTmpTable(
            Config::CART_ADDRESS_ITEM_TABLE_NAME , $temporaryAddressItemTableName, $quoteConnection
        );
        $this->createTmpTable(
            Config::CART_PAYMENT , $temporaryPaymentTableName, $quoteConnection
        );
        $this->createTmpTable(
          Config::CART_SHIPPING_RATE , $temporaryCartShippingRateTableName, $quoteConnection
        );
        /* order table group */
        $this->createTmpTable(
            Config::ORDER_TABLE_NAME , Config::getOrderTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_ITEM_TABLE_NAME , Config::getOrderItemTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_PAYMENT_TABLE_NAME , Config::getOrderPaymentTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_TAX_TABLE_NAME , Config::getOrderTaxTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_TAX_ITEM_TABLE_NAME , Config::getOrderTaxItemTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_ADDRESS_TABLE_NAME , Config::getOrderAddressTmpTableName()
        );
        /* end order table group */

        /* shipment table group */
        $this->createTmpTable(
            Config::SHIPMENT_TABLE_NAME , Config::getShipmentTmpTableName()
        );
        $this->createTmpTable(
            Config::SHIPMENT_ITEM_TABLE_NAME , Config::getShipmentItemTmpTableName()
        );
        $this->createTmpTable(
            Config::SHIPMENT_TRACK_TABLE_NAME , Config::getShipmentTrackTmpTableName()
        );
        $this->createTmpTable(
            Config::SHIPMENT_COMMENT_TABLE_NAME , Config::getShipmentCommentTmpTableName()
        );
        /* end shipment table group */

        /* invoice table group */
        $this->createTmpTable(
            Config::INVOICE_TABLE_NAME , Config::getInvoiceTmpTableName()
        );
        $this->createTmpTable(
            Config::INVOICE_ITEM_TABLE_NAME , Config::getInvoiceItemTmpTableName()
        );
        $this->createTmpTable(
            Config::INVOICE_COMMENT_TABLE_NAME , Config::getInvoiceCommentTmpTableName()
        );
        $this->createTmpTable(
            Config::ORDER_ADDRESS_ITEM_TABLE_NAME , Config::getOrderAddressItemTmpTableName()
        );
        /* end invoice table group */

        $this->createTmpTable(
            Config::ORDER_STATUS_HISTORY_TABLE_NAME , Config::getOrderStatusHistoryTmpTableName()
        );
        /*Riki reward quote*/
        $this->createTmpTable(
            Config::RIKI_REWARD_QUOTE , Config::getRikiRewardQuoteTmpTableName(), $quoteConnection
        );
    }

    /**
     * Create temporary table on connection
     *
     * @param $sourceTable
     * @param $tmpTableName
     * @param null $connection
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function createTmpTable($sourceTable , $tmpTableName, $connection = null)
    {
        $connection = is_null($connection)? $this->connection : $connection;

        if (\Zend_Validate::is($sourceTable,'NotEmpty')){
            $sourceTable = $connection->getTableName($sourceTable);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Table {$sourceTable} not found"));
        }


        if(\Zend_Validate::is($tmpTableName,'NotEmpty' )){
            $tmpTableName = $connection->getTableName($tmpTableName);
        }else{
            throw new \Magento\Framework\Exception\LocalizedException(__("Table {$tmpTableName} not found"));
        }

        $connection->createTemporaryTableLike($tmpTableName,$sourceTable,true);
    }

}