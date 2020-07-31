<?php
// @codingStandardsIgnoreFile
namespace Riki\Rma\Setup;

use Magento\OfflinePayments\Model\Banktransfer;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Model\Method\Free;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;
use Riki\Sales\Model\Order\PaymentMethod;
use Riki\Rma\Model\Config\Source\Rma\MassAction as MassActionOption;

class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Rma\Setup\RmaSetupFactory
     */
    protected $rmaSetupFactory;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterfaceFactory
     */
    protected $rmaRepositoryFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Riki\Rma\Helper\DataFactory
     */
    protected $dataHelperFactory;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterfaceFactory
     */
    protected $rmaItemRepositoryFactory;

    /**
     * @var \Magento\Config\Model\ConfigFactory
     */
    protected $configFactory;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**\
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory
     */
    protected $reasonCollectionFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * UpgradeData constructor.
     * @param \Riki\Rma\Api\ItemRepositoryInterfaceFactory $rmaItemRepositoryFactory
     * @param \Riki\Rma\Helper\DataFactory $dataHelperFactory
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterfaceFactory $rmaRepositoryInterfaceFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Rma\Setup\RmaSetupFactory $rmaSetupFactory
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Config\Model\ConfigFactory $configFactory
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\Rma\Api\ItemRepositoryInterfaceFactory $rmaItemRepositoryFactory,
        \Riki\Rma\Helper\DataFactory $dataHelperFactory,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\RmaRepositoryInterfaceFactory $rmaRepositoryInterfaceFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Rma\Setup\RmaSetupFactory $rmaSetupFactory,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Config\Model\ConfigFactory $configFactory,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Magento\Framework\Math\Random $mathRandom,
        \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->rmaItemRepositoryFactory = $rmaItemRepositoryFactory; // @use factory prevent load session on create object
        $this->dataHelperFactory = $dataHelperFactory; // @use factory prevent load session on create object
        $this->rmaRepositoryFactory = $rmaRepositoryInterfaceFactory; // @use factory prevent load session on create object
        $this->appState = $appState;
        $this->searchHelper = $searchHelper;
        $this->rmaSetupFactory = $rmaSetupFactory;
        $this->configFactory = $configFactory;
        $this->refundHelper = $refundHelper;
        $this->mathRandom = $mathRandom;
        $this->reasonCollectionFactory = $reasonCollectionFactory;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
    }

    public function version101()
    {
        $rows = [
            [
                11,
                'Suspended Shipment (due to customer)',
                'Consumer'
            ],
            [
                12,
                'Suspended Shipment (due to Nestle, quality issue)',
                'Nestle'
            ],
            [
                13,
                'Damaged Items during delivery(due to warehouse operation, distribution process)',
                'WH/Courier'
            ],
            [
                14,
                'Unknown Address',
                'Consumer'
            ],
            [
                15,
                'Unreachable for over 7 days',
                'Consumer'
            ],
            [
                16,
                'Unknown return during delivery',
                'Unclear'
            ],
            [
                21,
                'Rejection (Due to customer)',
                'Consumer'
            ],
            [
                22,
                'Rejection (Defected during delivery)',
                'WH/Courier'
            ],
            [
                23,
                'Rejection (due to Nestle, quality issue)',
                'Nestle'
            ],
            [
                24,
                'Rejection (Unknown reason)',
                'Unclear'
            ],
            [
                31,
                'Return from customer (unopened within 8days)',
                'Consumer'
            ],
            [
                32,
                'Return from customer (opened, or unopened over 8days)',
                'Consumer'
            ],
            [
                33,
                'Return from customer (found defect after opened)',
                'Unclear'
            ],
            [
                34,
                'Return from customer (due to Nestlé’s receive order process)',
                'Nestle'
            ],
            [
                35,
                'Return from customer (due to Warehouse, wrong packing)',
                'WH/Courier'
            ],
            [
                36,
                'Return from customer (due to Nestle, quality issue)',
                'Nestle'
            ],
            [
                37,
                'Return from customer (Unknown reason)',
                'Unclear'
            ],
            [
                38,
                'Return from customer (due to CHL, during receive order process)',
                'To be identified'
            ]
        ];
        $this->insertArray('riki_rma_reason', ['code', 'description', 'due_to'], $rows);
    }

    public function version140()
    {
        $rows = [
            [
                11,
                'Suspended Shipment (due to customer)',
                '出荷留め（お客様都合）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                12,
                'Suspended Shipment (due to Nestle, quality issue)',
                '出荷留め（ネスレからの依頼分_品質起因含む）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                13,
                'Damaged Items during delivery(due to warehouse operation, distribution process)',
                '配送中破損（倉庫破損・配送業者破損）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                14,
                'Unknown Address',
                '住所不明',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                15,
                'Unreachable for over 7 days　',
                '長期不在戻り（7日以上不在連絡つかず）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                16,
                'Unknown return during delivery',
                '配送途上での原因不明返送',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                21,
                'Rejection (Due to customer)',
                '受取拒否（顧客起因、しらない、いらない）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                22,
                'Rejection (Defected during delivery)',
                '受取拒否（配送中破損）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                23,
                'Rejection (due to Nestle, quality issue)',
                '受取拒否（ネスレからの依頼分_品質起因含む）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                24,
                'Rejection (Unknown reason)',
                '受取拒否　(原因不明）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                31,
                'Return from customer (unopened within 8days)',
                '消費者返品（8日以内未開封）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                32,
                'Return from customer (opened, or unopened over 8days)',
                '消費者返品（お客様都合_開封済み、もしくは8日以降経過）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            ],
            [
                33,
                'Return from customer (found defect after opened)',
                '消費者返品（受取後、ダメージ発見）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                34,
                'Return from customer (due to Nestlé’s receive order process)',
                '消費者返品（ネスレの受注ミス）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                35,
                'Return from customer (due to Warehouse, wrong packing)',
                '消費者返品（倉庫起因　　ピックミス・テレコ納品）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                36,
                'Return from customer (due to Nestle, quality issue)',
                '消費者返品（ネスレからの依頼分_品質起因含む）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                37,
                'Return from customer (Unknown reason)',
                '消費者返品　責任所在不明不明',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ],
            [
                38,
                'Return from customer (due to CHL, during receive order process)',
                '消費者返品（仮_原因CHL要変更）',
                \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            ]
        ];

        foreach ($rows as $row) {
            $this->delete('riki_rma_reason', 'code = ' . $row[0]);
        }

        $this->insertArray('riki_rma_reason', ['code', 'description_en', 'description_jp', 'due_to'], $rows);
    }

    public function version141()
    {
        // ignore join because may be have multiple database
        $connRma = $this->getConnection($this->getTable('magento_rma'));
        if (!$connRma->isTableExists('magento_rma')
            || !$connRma->isTableExists('magento_rma_grid')
        ) {
            return;
        }
        $connOrder = $this->getConnection($this->getTable('sales_order'));
        if (!$connOrder->isTableExists('sales_order')) {
            return;
        }
        $rmaRows = $connRma->select()
            ->from($this->getTable('magento_rma'), ['entity_id', 'reason_id', 'returned_date', 'return_status', 'refund_method', 'order_id'])
            ->query()
            ->fetchAll();
        foreach ($rmaRows as $rmaRow) {
            $data = [
                'reason_id' => $rmaRow['reason_id'],
                'returned_date' => $rmaRow['returned_date'],
                'payment_status' => null,
                'return_status' => $rmaRow['return_status'],
                'refund_method' => $rmaRow['refund_method']
            ];
            $paymentStatus = $connOrder->select()
                ->from($this->getTable('sales_order'), ['payment_status'])
                ->where('entity_id = ?', $rmaRow['order_id'])
                ->query()
                ->fetchColumn();
            if ($paymentStatus) {
                $data['payment_status'] = $paymentStatus;
            }
            $connRma->update($this->getTable('magento_rma_grid'), $data, sprintf('entity_id = %d', $rmaRow['entity_id']));
        }
    }


    public function version142()
    {

        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'unit_case',
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
            'return_amount',
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
            'return_amount_adj',
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
            'return_wrapping_fee',
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
            'return_wrapping_fee_adj',
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

    public function version154()
    {

        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'free_of_charge',
            [
                'type' => 'static',
                'label' => 'Free Of Charge',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'booking_wbs',
            [
                'type' => 'static',
                'label' => 'Booking Wbs',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'foc_wbs',
            [
                'type' => 'static',
                'label' => 'Free of Charge Wbs',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 10,
                'position' => 10,
            ]
        );

        /* migrate data may be hurt performance =.= god bless me */
        $connRmaItem = $this->getConnection($this->getTable('magento_rma_item_entity'));
        $orderItemIdsSelect = $connRmaItem->select()
            ->distinct(true)
            ->from($connRmaItem->getTableName('magento_rma_item_entity'), ['order_item_id']);
        $orderItemIds = $connRmaItem->fetchCol($orderItemIdsSelect);
        $connOrderItem = $this->getConnection($this->getTable('sales_order_item'));
        foreach ($orderItemIds as $orderItemId) {
            $orderItem = $connOrderItem->select()
                ->from($connOrderItem->getTableName('sales_order_item'), ['free_of_charge', 'booking_wbs', 'foc_wbs'])
                ->where('item_id = ?', $orderItemId)
                ->limit(1)
                ->query()
                ->fetchObject();
            if ($orderItem) {
                $connRmaItem->update($connRmaItem->getTableName('magento_rma_item_entity'), [
                    'free_of_charge' => $orderItem->free_of_charge,
                    'booking_wbs' => $orderItem->booking_wbs,
                    'foc_wbs' => $orderItem->foc_wbs
                ], "order_item_id = '{$orderItemId}'");
            }
        }
    }

    public function version156()
    {
        $connection = $this->getConnection('riki_rma_reason');
        $connection->update($connection->getTableName('riki_rma_reason'), [
            'sap_code' => 'CD'
        ]);
    }

    public function version158()
    {
        $connection = $this->getConnection('magento_rma');
        $connection->update($connection->getTableName('magento_rma'), [
            'return_shipping_fee_adjusted' => new \Zend_Db_Expr('return_shipping_fee + return_shipping_fee_adj'),
            'return_payment_fee_adjusted' => new \Zend_Db_Expr('return_payment_fee + return_payment_fee_adj'),
        ]);

        $connection = $this->getConnection('magento_rma_item_entity');
        $connection->update($connection->getTableName('magento_rma_item_entity'), [
            'qty_authorized' => new \Zend_Db_Expr('qty_requested'),
            'qty_approved' => new \Zend_Db_Expr('qty_requested'),
            'qty_returned' => new \Zend_Db_Expr('qty_requested'),
        ]);
    }

    public function version159()
    {
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'migrate159']);
    }

    public function migrate159()
    {
        /** @var \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository */
        $rmaRepository = $this->rmaRepositoryFactory->create();
        /** @var \Riki\Rma\Helper\Data $dataHelper */
        $dataHelper = $this->dataHelperFactory->create();
        $connection = $this->getConnection('magento_rma_grid');
        $rmas = $this->searchHelper
            ->getAll()
            ->execute($rmaRepository);
        /** @var \Magento\Rma\Model\Rma $rma */
        foreach ($rmas as $rma) {
            $updated = [
                'payment_agent' => new \Zend_Db_Expr('NULL'),
                'payment_date' => new \Zend_Db_Expr('NULL'),
                'consumer_db_id' => new \Zend_Db_Expr('NULL'),
                'payment_method' => new \Zend_Db_Expr('NULL'),
            ];

            $order = $dataHelper->getRmaOrder($rma);
            if ($order) {
                $updated['payment_agent'] = $order->getData('payment_agent');
            }
            $consumerDbId = $dataHelper->getRmaCustomerConsumerDbId($rma);
            if ($consumerDbId) {
                $updated['consumer_db_id'] = $consumerDbId;
            }
            $paymentMethod = $dataHelper->getRmaOrderPaymentMethodCode($rma);
            if ($paymentMethod) {
                $updated['payment_method'] = $paymentMethod;
            }
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            foreach ($dataHelper->getRmaOrderShipments($rma) as $shipment) {
                if (!$updated['payment_date']) {
                    $updated['payment_date'] = $shipment->getData('payment_date');
                    continue;
                }
                if ($updated['payment_date'] < $shipment->getData('payment_date')) {
                    $updated['payment_date'] = $shipment->getData('payment_date');
                }
            }

            $connection->update($connection->getTableName('magento_rma_grid'), $updated, sprintf("entity_id = %s", $rma->getEntityId()));
        }
    }

    public function version160()
    {
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'migrate160']);
    }

    public function migrate160()
    {
        /** @var \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository */
        $rmaRepository = $this->rmaRepositoryFactory->create();
        /** @var \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository */
        $rmaItemRepository = $this->rmaItemRepositoryFactory->create();
        /** @var \Riki\Rma\Helper\Data $dataHelper */
        $dataHelper = $this->dataHelperFactory->create();
        $rmas = $this->searchHelper
            ->getByReturnStatus(\Riki\Rma\Api\Data\Rma\ReturnStatusInterface::CLOSED)
            ->getAll()
            ->execute($rmaRepository);
        foreach ($rmas as $rma) {
            /** @var \Riki\Rma\Model\Item $item */
            foreach ($dataHelper->getRmaItems($rma) as $item) {
                if ($item->getStatus() != \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED) {
                    $item->setData('status', \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED);
                    $rmaItemRepository->save($item);
                }
            }
        }
    }

    public function version164()
    {
        $connection = $this->getConnection('magento_rma_item_entity');
        $connection->update($connection->getTableName('magento_rma_item_entity'), [
            'qty_authorized' => new \Zend_Db_Expr('qty_requested'),
            'qty_approved' => new \Zend_Db_Expr('qty_requested'),
            'qty_returned' => new \Zend_Db_Expr('qty_requested'),
        ], 'status = "approved"');
    }

    public function version165()
    {
        $connection = $this->getConnection('magento_rma_item_entity');
        $connection->update($connection->getTableName('magento_rma_item_entity'), [
            'return_amount' => new \Zend_Db_Expr('FLOOR(`return_amount`)'),
            'return_amount_adj' => new \Zend_Db_Expr('FLOOR(`return_amount_adj`)'),
            'return_wrapping_fee' => new \Zend_Db_Expr('FLOOR(`return_wrapping_fee`)'),
            'return_wrapping_fee_adj' => new \Zend_Db_Expr('FLOOR(`return_wrapping_fee_adj`)'),
        ]);
    }

    public function version166()
    {
        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'return_amount_excl_tax',
            [
                'type' => 'static',
                'label' => 'Return amount exclude tax',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 20,
                'position' => 20,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'return_tax_amount',
            [
                'type' => 'static',
                'label' => 'Return tax amount',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 21,
                'position' => 21,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'return_amount_adj_excl_tax',
            [
                'type' => 'static',
                'label' => 'Return amount adjustment exclude tax',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 22,
                'position' => 22,
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'return_tax_amount_adj',
            [
                'type' => 'static',
                'label' => 'Return tax amount adjustment',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 23,
                'position' => 23,
            ]
        );

        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'migrate166']);
    }

    public function migrate166()
    {
        /** @var \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository */
        $rmaRepository = $this->rmaRepositoryFactory->create();
        $lastId = 0;

        do {
            // limit 100 will keep memory not overflow
            $rmas = $this->searchHelper
                ->getByEntityId($lastId, 'gt')
                ->limit(100)
                ->execute($rmaRepository);

            if (!$rmas) {
                break;
            }

            /** @var \Riki\Rma\Model\Rma $rma */
            foreach ($rmas as $rma) {
                $items = $rma->getRmaItems();
                $rma->setItems($items);
                $rmaRepository->save($rma);
                $lastId = $rma->getId();
            }

        } while (true);
    }

    public function version167()
    {
        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'commission_amount',
            [
                'type' => 'static',
                'label' => 'Return item - commission amount',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 20,
                'position' => 20,
            ]
        );
    }

    public function version168()
    {
        $connection = $this->getConnection('magento_rma');
        $connection->update($connection->getTableName('magento_rma'), [
            'returned_date' => new \Zend_Db_Expr('date_requested'),
        ], 'returned_date IS NULL');

        $connection->update($connection->getTableName('magento_rma_grid'), [
            'returned_date' => new \Zend_Db_Expr('date_requested'),
        ], 'returned_date IS NULL');
    }

    public function version169()
    {
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'migrate169']);
    }

    public function migrate169()
    {
        /** @var \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository */
        $rmaRepository = $this->rmaRepositoryFactory->create();
        /** @var \Riki\Rma\Helper\Data $dataHelper */
        $dataHelper = $this->dataHelperFactory->create();
        $connection = $this->getConnection('magento_rma_grid');
        $rmas = $this->searchHelper
            ->getAll()
            ->execute($rmaRepository);
        /** @var \Magento\Rma\Model\Rma $rma */
        foreach ($rmas as $rma) {
            $updated = [
                'consumer_db_id' => new \Zend_Db_Expr('NULL')
            ];

            $consumerDbId = $dataHelper->getRmaCustomerConsumerDbId($rma);

            if ($consumerDbId) {
                $updated['consumer_db_id'] = $consumerDbId;
            }

            $connection->update($connection->getTableName('magento_rma_grid'), $updated, sprintf("entity_id = %s", $rma->getEntityId()));
        }
    }

    public function version170()
    {
        $connection = $this->getConnection('magento_rma');
        $connection->update($connection->getTableName('magento_rma_item_entity'), [
            'return_tax_amount' => 0,
            'return_amount_excl_tax' => 0
        ], 'return_amount = 0 OR return_amount IS NULL');
        $connection->update($connection->getTableName('magento_rma'), [
            'return_tax_amount' => new \Zend_Db_Expr('(SELECT sum(return_tax_amount) FROM magento_rma_item_entity WHERE rma_entity_id = `magento_rma`.entity_id)'),
            'return_amount_excl_tax' => new \Zend_Db_Expr('(SELECT sum(return_amount_excl_tax) FROM magento_rma_item_entity WHERE rma_entity_id = `magento_rma`.entity_id)')
        ], 'return_tax_amount < 0');
    }

    public function version174()
    {
        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'distribution_channel',
            [
                'type' => 'static',
                'label' => 'Return item - Distribution channel',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 20,
                'position' => 20,
            ]
        );
    }

    public function version175()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->update(
            $connection->getTableName('magento_rma'),
            ['trigger_cancel_point' => 1],
            'extension_data like \'%"use_point_order_level":1%\' OR  extension_data like \'%"use_point_order_level":"1"%\''
        );
    }

    public function version202()
    {
        $configData = [
            'section' => 'rma',
            'website' => null,
            'store'   => null,
            'groups'  => []
        ];
        $paymentMethods = $this->refundHelper->getEnablePaymentMethods();
        $refundMethods = $this->refundHelper->getEnableRefundMethods();
        foreach ($paymentMethods as $paymentMethod => $paymentData) {
            if (array_key_exists(Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE, $refundMethods)) {
                $configData['groups'][$paymentMethod]['fields']['online_member_default']['value'] = Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE;
            }
            if (array_key_exists(Checkmo::PAYMENT_METHOD_CHECKMO_CODE, $refundMethods)) {
                $configData['groups'][$paymentMethod]['fields']['offline_member_default']['value'] = Checkmo::PAYMENT_METHOD_CHECKMO_CODE;
            }
        }
        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }

    public function version213()
    {
        $defaultConnection = $this->getConnection('magento_rma');
        $saleConnection = $this->getConnection('sales_shipment');

        $orderSelect = $defaultConnection->select()->from(
            $defaultConnection->getTableName('magento_rma_grid'),
            ['order_id']
        )->distinct();

        $query = $defaultConnection->query($orderSelect);

        while ($row = $query->fetch()) {
            $orderId = $row['order_id'];

            $shipmentNum = $saleConnection->fetchRow(
                $saleConnection->select()->from(
                    $saleConnection->getTableName('sales_shipment'),
                    [
                        'shipment_num'    =>  new \Zend_Db_Expr('COUNT(*)'),
                        'rejected_shipment_num' =>  new \Zend_Db_Expr("SUM(IF(shipment_status='rejected', 1, 0))")
                    ]
                )->where(
                    'order_id = ?',
                    $orderId
                )->group('order_id')
            );

//            $isShipmentsRejected = ($shipmentNum['shipment_num'] && ($shipmentNum['shipment_num'] == $shipmentNum['rejected_shipment_num']))? 1 : 0;
//
//            $defaultConnection->update(
//                $defaultConnection->getTableName('magento_rma_grid'),
//                ['is_shipments_rejected'  =>  $isShipmentsRejected],
//                ['order_id = ?' => $orderId]
//            );
        }
    }

    public function version214()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->update(
            $connection->getTableName('magento_rma'),
            ['refund_status' => RefundStatusInterface::MANUALLY_CARD_COMPLETED],
            'refund_status=19'
        );

        $connection->update(
            $connection->getTableName('magento_rma_grid'),
            ['refund_status' => RefundStatusInterface::MANUALLY_CARD_COMPLETED],
            'refund_status=19'
        );
    }

    public function version230()
    {
        $groupCondition = [
            'full_partial' => 'full',
            'payment_method' => [Free::PAYMENT_METHOD_FREE_CODE, PaymentMethod::PAYMENT_METHOD_COD],
            'reason' => []
        ];

        $defaultReasonCodes = [11, 12, 14, 15, 16, 21, 22, 23, 24, 41, 44, 45, 51];

        /** @var \Riki\Rma\Model\ResourceModel\Reason\Collection $reasonCollection */
        $reasonCollection = $this->reasonCollectionFactory->create();

        $reasonIds = $reasonCollection->addFieldToFilter('code', ['in' => $defaultReasonCodes])
            ->getAllIds();

        $groupCondition['reason'] = $reasonIds;

        $groupId = $this->mathRandom->getUniqueHash('_');
        $data[$groupId] = $groupCondition;

        /** @var \Magento\Config\Model\Config $config */
        $config = $this->configFactory->create();
        $config->setDataByPath(
            'rma/mass_action/approve_condition',
            serialize($data)
        );
        $config->save();
    }

    public function version240()
    {
        $installer = $this->rmaSetupFactory->create(['setup' => $this->getSetup()]);
        $installer->addAttribute(
            'rma_item',
            'bundle_item_earned_point',
            [
                'type' => 'static',
                'label' => 'Bundle item earned point',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'sort_order' => 30,
                'position' => 30,
            ]
        );
    }

    public function version241()
    {
        $groupCondition = [
            'full_partial' => 'full',
            'payment_method' => 'all',
            'reason' => []
        ];

        $defaultReasonCodes = [11, 12, 13, 14, 15, 16, 21, 22, 23, 24, 33, 34, 35, 36, 37];

        /** @var \Riki\Rma\Model\ResourceModel\Reason\Collection $reasonCollection */
        $reasonCollection = $this->reasonCollectionFactory->create();

        $reasonIds = $reasonCollection->addFieldToFilter('code', ['in' => $defaultReasonCodes])
            ->getAllIds();

        $groupCondition['reason'] = $reasonIds;
        $groupId = $this->mathRandom->getUniqueHash('_');
        $data[$groupId] = $groupCondition;

        $config = $this->configFactory->create();
        $config->setDataByPath(
            'rma/mass_action/approve_condition_accept_request',
            $this->serializer->serialize($data)
        );
        $config->save();
    }

    public function version242()
    {
        // fix Review CC data
        $config = $this->configFactory->create();
        $reviewCcPath = 'rma/mass_action/approve_condition_' . MassActionOption::REVIEW_BY_CC;
        $approveCcPath = 'rma/mass_action/approve_condition_' . MassActionOption::APPROVE_BY_CC;
        $approveCsPath = 'rma/mass_action/approve_condition_' . MassActionOption::APPROVE_BY_CS;

        $configValue = $this->serializer->unserialize($this->scopeConfig->getValue($reviewCcPath));
        $reviewCc = reset($configValue);
        $reviewCc['payment_method'] = [
            PaymentMethod::PAYMENT_METHOD_COD,
            PaymentMethod::PAYMENT_METHOD_CVS,
            PaymentMethod::PAYMENT_METHOD_INVOICED,
            PaymentMethod::PAYMENT_METHOD_PAYGENT,
            PaymentMethod::PAYMENT_METHOD_NPATOBARAI
        ];
        $groupId = $this->mathRandom->getUniqueHash('_');
        $dataReviewCc[$groupId] = $reviewCc;
        $config->setDataByPath(
            $reviewCcPath,
            $this->serializer->serialize($dataReviewCc)
        );
        $config->save();

        // Approve CC & Approve CS data
        $groupCondition = [
            'full_partial' => 'full',
            'payment_method' => 'all',
            'reason' => ['all']
        ];

        $groupId = $this->mathRandom->getUniqueHash('_');
        $dataApproveCcCs[$groupId] = $groupCondition;
        $serializedData = $this->serializer->serialize($dataApproveCcCs);

        $config->setDataByPath(
            $approveCcPath,
            $serializedData
        );
        $config->save();
        $config->setDataByPath(
            $approveCsPath,
            $serializedData
        );
        $config->save();
    }

    public function version243()
    {
        $groupCondition = [
            'full_partial' => 'full',
            'payment_method' => [Free::PAYMENT_METHOD_FREE_CODE, PaymentMethod::PAYMENT_METHOD_COD, 'npatobarai'],
            'reason' => []
        ];

        $defaultReasonCodes = [11, 12, 14, 15, 16, 21, 22, 23, 24, 41, 44, 45, 51];

        /** @var \Riki\Rma\Model\ResourceModel\Reason\Collection $reasonCollection */
        $reasonCollection = $this->reasonCollectionFactory->create();

        $reasonIds = $reasonCollection->addFieldToFilter('code', ['in' => $defaultReasonCodes])
            ->getAllIds();

        $groupCondition['reason'] = $reasonIds;

        $groupId = $this->mathRandom->getUniqueHash('_');
        $data[$groupId] = $groupCondition;

        /** @var \Magento\Config\Model\Config $config */
        $config = $this->configFactory->create();
        $config->setDataByPath(
            'rma/mass_action/approve_condition_reject',
            $this->serializer->serialize($data)
        );
        $config->save();
    }

}
