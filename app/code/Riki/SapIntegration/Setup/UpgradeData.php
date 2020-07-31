<?php
namespace Riki\SapIntegration\Setup;

class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Rma\Setup\RmaSetupFactory
     */
    protected $rmaSetupFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $config;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentItemRepositoryInterface
     */
    protected $shipmentItemRepository;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * UpgradeData constructor.
     *
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Magento\Sales\Api\ShipmentItemRepositoryInterface $shipmentItemRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Rma\Setup\RmaSetupFactory $rmaSetupFactory
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Magento\Sales\Api\ShipmentItemRepositoryInterface $shipmentItemRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Rma\Setup\RmaSetupFactory $rmaSetupFactory,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->appState = $appState;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->shipmentItemRepository = $shipmentItemRepository;
        $this->productRepository = $productRepository;
        $this->searchHelper = $searchHelper;
        $this->config = $config;
        $this->rmaSetupFactory = $rmaSetupFactory;
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
    }

    public function version102()
    {
        $this->config->saveConfig('sap_integration_config/sap_customer_id/nicos', '5299652', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/jcb', '5299653', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/yamato', '5299642', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/askul', '5299651', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/wellnet', '5299646', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/fukujuen', '5110776', 'default', 0);
        $this->config->saveConfig('sap_integration_config/sap_customer_id/point_purchase', '5299644', 'default', 0);
    }

    public function version103()
    {
        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'gps_price_ec',
            [
                'type' => 'static',
                'label' => 'Unit Case',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'material_type',
            [
                'type' => 'static',
                'label' => 'Unit Case',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'sales_organization',
            [
                'type' => 'static',
                'label' => 'Unit Case',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'sap_interface_excluded',
            [
                'type' => 'static',
                'label' => 'Unit Case',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
    }

    public function version104()
    {
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'migrate104']);
    }

    public function getProduct($sku)
    {
        if ($this->functionCache->has($sku)) {
            return $this->functionCache->load($sku);
        }

        $result = $this->searchHelper
            ->getBySku($sku)
            ->getOne()
            ->execute($this->productRepository);

        $this->functionCache->store($result, $sku);

        return $result;
    }

    public function migrate104()
    {
        $shipmentItemId = 0;
        do {
            $shipmentItems = $this->searchHelper
                ->getByEntityId($shipmentItemId, 'gt')
                ->limit(100)
                ->execute($this->shipmentItemRepository);
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
            foreach ($shipmentItems as $shipmentItem) {
                $product = $this->getProduct($shipmentItem->getSku());
                if ($product instanceof \Magento\Catalog\Model\Product) {
                    $shipmentItem->setData('gps_price_ec', $product->getData('gps_price_ec'));
                    $shipmentItem->setData('material_type',
                        is_array($product->getAttributeText('material_type'))
                            ? implode(',', $product->getAttributeText('material_type'))
                            : $product->getAttributeText('material_type')
                    );
                    $shipmentItem->setData('sales_organization',
                        is_array($product->getAttributeText('sales_organization'))
                            ? implode(',', $product->getAttributeText('sales_organization'))
                            : $product->getAttributeText('sales_organization')
                    );
                    $shipmentItem->setData('sap_interface_excluded', $product->getData('sap_interface_excluded'));
                    $this->shipmentItemRepository->save($shipmentItem);
                }
                $shipmentItemId = $shipmentItem->getId();
            }
        } while(count($shipmentItems));

        $rmaItemId = 0;
        do {
            $rmaItems = $this->searchHelper
                ->getByEntityId($rmaItemId, 'gt')
                ->limit(100)
                ->execute($this->rmaItemRepository);
            /** @var \Magento\Rma\Model\Item $rmaItem */
            foreach ($rmaItems as $rmaItem) {
                $product = $this->getProduct($rmaItem->getData('product_sku'));
                if ($product instanceof \Magento\Catalog\Model\Product) {
                    $rmaItem->setData('gps_price_ec', $product->getData('gps_price_ec'));
                    $rmaItem->setData('material_type',
                        is_array($product->getAttributeText('material_type'))
                            ? implode(',', $product->getAttributeText('material_type'))
                            : $product->getAttributeText('material_type')
                    );
                    $rmaItem->setData('sales_organization',
                        is_array($product->getAttributeText('sales_organization'))
                            ? implode(',', $product->getAttributeText('sales_organization'))
                            : $product->getAttributeText('sales_organization')
                    );
                    $rmaItem->setData('sap_interface_excluded', $product->getData('sap_interface_excluded'));
                    $this->rmaItemRepository->save($rmaItem);
                }
                $rmaItemId = $rmaItem->getId();
            }
        } while(count($rmaItems));
    }

    public function version105()
    {
        $salesConnection = $this->resourceConnection->getConnection('sales');

        $select = $salesConnection->select()->from(
            $salesConnection->getTableName('sales_shipment'),
            ['shipment_entity_id' => 'entity_id', 'shipment_increment_id' => 'increment_id', 'order_id', 'is_exported_sap', 'export_sap_date']
        );
        /*sync export sap data from sales_shipment to riki_shipment_sap_exported*/
        $salesConnection->query(
            $select->insertFromSelect(
                $salesConnection->getTableName('riki_shipment_sap_exported'),
                ['shipment_entity_id', 'shipment_increment_id', 'order_id', 'is_exported_sap', 'export_sap_date'],
                true
            )
        );
    }
}
