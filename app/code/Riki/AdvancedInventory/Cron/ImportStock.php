<?php

namespace Riki\AdvancedInventory\Cron;

class ImportStock
{
    /**
     * @var \Riki\AdvancedInventory\Helper\ImportStock\ImportStockHelper
     */
    protected $importStockHelper;
    /**
     * @var \Riki\AdvancedInventory\Logger\Logger
     */
    protected $logger;

    /*use to get warehouse config*/
    protected $warehouse;

    /**
     * ImportStock constructor.
     * @param \Riki\AdvancedInventory\Helper\ImportStock\ImportStockHelper $importStockHelper
     * @param \Riki\AdvancedInventory\Logger\BaseLogger $logger
     * @param string $warehouse
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\ImportStock\ImportStockHelper $importStockHelper,
        \Riki\AdvancedInventory\Logger\BaseLogger $logger,
        $warehouse = '1st'
    ) {
        $this->importStockHelper = $importStockHelper;
        $this->logger = $logger;
        $this->warehouse = $warehouse;
    }

    public function execute()
    {
        $this->importStockHelper->setLogger($this->logger);
        $this->importStockHelper->setWarehouseConfig($this->warehouse);
        $this->importStockHelper->importProcess();
    }
}