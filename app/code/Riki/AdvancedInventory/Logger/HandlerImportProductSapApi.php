<?php
namespace Riki\AdvancedInventory\Logger;

class HandlerImportProductSapApi extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/import_product_sap_api.log';
}
