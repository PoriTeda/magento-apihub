<?php


namespace Nestle\Migration\Preference\Setup\Patch\Data;


use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory as AddressCollectionFactory;
use Magento\Sales\Setup\SalesSetupFactory;

class FillQuoteAddressIdInSalesOrderAddress extends \Magento\Sales\Setup\Patch\Data\FillQuoteAddressIdInSalesOrderAddress
{

    /**
     * @var ResourceConnection
     */
    private $resouceConection;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SalesSetupFactory $salesSetupFactory,
        State $state, Config $eavConfig,
        AddressCollectionFactory $addressCollectionFactory,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        ResourceConnection $resourceConnection)
    {
        $this->resouceConection = $resourceConnection;
        parent::__construct($moduleDataSetup, $salesSetupFactory, $state, $eavConfig, $addressCollectionFactory, $orderFactory, $quoteFactory);
    }

    /**
     * Fill quote_address_id in table sales_order_address if it is empty.
     *
     * @param ModuleDataSetupInterface $setup
     */
    public function fillQuoteAddressIdInSalesOrderAddress(ModuleDataSetupInterface $setup)
    {
        return;
    }
    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [
            "Magento\Sales\Setup\Patch\Data\FillQuoteAddressIdInSalesOrderAddress"
        ];
    }
}
