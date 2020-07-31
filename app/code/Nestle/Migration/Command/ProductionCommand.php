<?php

namespace Nestle\Migration\Command;

use Magento\Eav\Model\Config;
use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldToConvert;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\DB\Select\QueryModifierFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Logging\Setup\ObjectConverter;
use Magento\Quote\Setup\ConvertSerializedDataToJsonFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Rma\Setup\SerializedDataConverter;
use Magento\Sales\Setup\SalesOrderPaymentDataConverter;
use Magento\Sales\Setup\SalesSetupFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductionCommand extends \Symfony\Component\Console\Command\Command
{
    const MODULE = "module";
    const TABLE = "table";

    /**
     * @var ConvertSerializedDataToJsonFactory
     */
    protected $convertQuoteSerializedDataToJsonFactory;

    /**
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;

    /**
     * @var AggregatedFieldDataConverter
     */
    protected $aggregatedFieldConverter;

    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var QueryModifierFactory
     */
    protected $queryModifierFactory;

    /**
     * @var Generator
     */
    private $queryGenerator;

    /**
     * ProductionCommand constructor.
     * @param ConvertSerializedDataToJsonFactory $convertQuoteSerializedDataToJsonFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     * @param SalesSetupFactory $salesSetupFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Config $eavConfig
     * @param QueryModifierFactory $queryModifierFactory
     */
    public function __construct(
        ConvertSerializedDataToJsonFactory $convertQuoteSerializedDataToJsonFactory,
        QuoteSetupFactory $quoteSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        AggregatedFieldDataConverter $aggregatedFieldConverter,
        SalesSetupFactory $salesSetupFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        Config $eavConfig,
        QueryModifierFactory $queryModifierFactory,
        Generator $queryGenerator,
        string $name = null
    ) {
        parent::__construct($name);

        $this->convertQuoteSerializedDataToJsonFactory = $convertQuoteSerializedDataToJsonFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->resource = $resource;
        $this->queryModifierFactory = $queryModifierFactory;
        $this->eavConfig = $eavConfig;
        $this->queryGenerator = $queryGenerator;
    }


    /**
     * Configure
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::MODULE,
                null,
                InputOption::VALUE_REQUIRED,
                'module',
                false
            ),
            new InputOption(
                self::TABLE,
                null,
                InputOption::VALUE_REQUIRED,
                'table',
                false
            )
        ];
        $this->setName('nestle:production-data-convert')->setDescription('Production Database Convert command')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption(self::MODULE);
        $tableName = $input->getOption(self::TABLE);

        if (!$module) {
            $output->writeln("Invalid module value!!!");
            exit;
        }

        $start = microtime(true);

        switch ($module) {
            case "Magento_Quote":
                $this->quoteConvert($tableName, $output);
                break;
            case "Magento_Sale":
                $this->saleConvert($tableName, $output);
                break;
            case "Magento_GiftCardAccount":
                $this->giftCardConvert($tableName, $output);
                break;
            case "Magento_Logging":
                $this->loggingConvert();
                break;
            case "Magento_Reward":
                $this->rewardConvert();
                break;
            case "Magento_Rma":
                $this->rmaConvert();
                break;
            default:
                $output->writeln("Invalid module value!!!");
                break;
        }

        $time_elapsed_secs = microtime(true) - $start;
        $minutes = $time_elapsed_secs / 60;
        $output->writeln("$module data convert is done! \n");
        $output->writeln("Total running time: $minutes minutes\n");
    }


    /**
     * Data Convert for quote module
     * @param $tableName
     * @param $output
     * @throws \Magento\Framework\DB\FieldDataConversionException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function quoteConvert($tableName, $output)
    {
        $quoteSetup = $this->quoteSetupFactory->create();
        $fieldsToUpdate = [];
        $tableList = [
            'quote_payment',
            'quote_address',
            'quote_item_option',
        ];
        if (in_array($tableName, $tableList)) {
            switch ($tableName) {
                case "quote_payment":
                    $fieldsToUpdate = [
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable('quote_payment'),
                            'payment_id',
                            'additional_information'
                        ),
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable('quote_payment'),
                            'payment_id',
                            'additional_data'
                        ),
                    ];
                    $this->aggregatedFieldConverter->convert($fieldsToUpdate, $quoteSetup->getConnection());
                    break;
                case "quote_address":
                    $fieldsToUpdate = [
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable($tableName),
                            'address_id',
                            'applied_taxes'
                        )
                    ];
                    $this->aggregatedFieldConverter->convert($fieldsToUpdate, $quoteSetup->getConnection());
                    break;
                case "quote_item_option":
                    $queryModifier = $this->queryModifierFactory->create(
                        'in',
                        [
                            'values' => [
                                'code' => [
                                    'parameters',
                                    'info_buyRequest',
                                    'attributes',
                                    'bundle_option_ids',
                                    'bundle_selection_ids',
                                    'bundle_selection_attributes',
                                ]
                            ]
                        ]
                    );
                    $fieldsToUpdate = [
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable('quote_item_option'),
                            'option_id',
                            'value',
                            $queryModifier
                        )
                    ];
                    $this->aggregatedFieldConverter->convert($fieldsToUpdate, $quoteSetup->getConnection());

                    $select = $quoteSetup->getSetup()
                        ->getConnection()
                        ->select()
                        ->from(
                            $quoteSetup->getSetup()
                                ->getTable('catalog_product_option'),
                            ['option_id']
                        )
                        ->where('type = ?', 'file');
                    $iterator = $this->queryGenerator->generate('option_id', $select);
                    foreach ($iterator as $selectByRange) {
                        $codes = $quoteSetup->getSetup()
                            ->getConnection()
                            ->fetchCol($selectByRange);
                        $codes = array_map(
                            function ($id) {
                                return 'option_' . $id;
                            },
                            $codes
                        );
                        $queryModifier = $this->queryModifierFactory->create(
                            'in',
                            [
                                'values' => [
                                    'code' => $codes
                                ]
                            ]
                        );
                        $this->aggregatedFieldConverter->convert(
                            [
                                new FieldToConvert(
                                    SerializedToJson::class,
                                    $quoteSetup->getTable('quote_item_option'),
                                    'option_id',
                                    'value',
                                    $queryModifier
                                ),
                            ],
                            $quoteSetup->getConnection()
                        );
                    }
                    break;
                default:
                    $output->writeln("Invalid table name value!!!");
                    break;
            }
        } else {
            $output->writeln("Invalid table name value!!!");
            exit;
        }
    }

    /**
     * Data Convert for sale module
     * @param $tableName
     * @param $output
     */
    protected function saleConvert($tableName, $output)
    {
        $salesSetup = $this->salesSetupFactory->create();
        $fieldsToUpdate = [];
        $tableList = [
            'sales_invoice_item',
            'sales_creditmemo_item',
            'sales_order_item',
            'sales_shipment',
            'sales_order_payment',
            'sales_payment_transaction',
        ];

        if (in_array($tableName, $tableList)) {
            switch ($tableName) {
                case "sales_invoice_item":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $salesSetup->getTable($tableName),
                        'entity_id',
                        'tax_ratio'
                    );
                    break;
                case "sales_creditmemo_item":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $salesSetup->getTable($tableName),
                        'entity_id',
                        'tax_ratio'
                    );
                    break;
                case "sales_order_item":
                    $fieldsToUpdate[] = new FieldToConvert(
                        \Magento\Sales\Setup\SerializedDataConverter::class,
                        $salesSetup->getTable($tableName),
                        'item_id',
                        'product_options'
                    );
                    break;
                case "sales_shipment":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $salesSetup->getTable($tableName),
                        'entity_id',
                        'packages'
                    );
                    break;
                case "sales_order_payment":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SalesOrderPaymentDataConverter::class,
                        $salesSetup->getTable($tableName),
                        'entity_id',
                        'additional_information'
                    );
                    break;
                case "sales_payment_transaction":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $salesSetup->getTable($tableName),
                        'transaction_id',
                        'additional_information'
                    );
                    break;
                default:
                    $output->writeln("Invalid table name value!!!");
                    break;
            }
        } else {
            $output->writeln("Invalid table name value!!!");
            exit;
        }

        $this->aggregatedFieldConverter->convert($fieldsToUpdate, $salesSetup->getConnection());
    }


    /**
     * Data Convert for GiftCard Account Module
     * @param $tableName
     * @param $output
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    protected function giftCardConvert($tableName, $output)
    {
        $salesSetup = $this->salesSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $quoteSetup = $this->quoteSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $connection = $quoteSetup->getConnection();
        $fieldsToUpdate = [];
        $tableList = [
            'quote_address',
            'quote',
            'sales_order'
        ];

        if (in_array($tableName, $tableList)) {
            switch ($tableName) {
                case "quote_address":
                    $fieldsToUpdate = [
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable($tableName),
                            'address_id',
                            'gift_cards'
                        ),
                        new FieldToConvert(
                            SerializedToJson::class,
                            $quoteSetup->getTable($tableName),
                            'address_id',
                            'used_gift_cards'
                        )
                    ];
                    break;
                case "quote":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $quoteSetup->getTable($tableName),
                        'entity_id',
                        'gift_cards'
                    );
                    break;
                case "sales_order":
                    $fieldsToUpdate[] = new FieldToConvert(
                        SerializedToJson::class,
                        $salesSetup->getTable($tableName),
                        'entity_id',
                        'gift_cards'
                    );
                    $connection = $salesSetup->getConnection();
                    break;
                default:
                    $output->writeln("Invalid table name value!!!");
                    break;
            }
        } else {
            $output->writeln("Invalid table name value!!!");
            exit;
        }

        $this->aggregatedFieldConverter->convert(
            $fieldsToUpdate,
            $connection
        );

    }

    /**
     * Data Convert for Logging Module
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    protected function loggingConvert()
    {
        $fieldsToUpdate = [
            new FieldToConvert(
                ObjectConverter::class,
                $this->moduleDataSetup->getTable('magento_logging_event'),
                'log_id',
                'info'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_logging_event_changes'),
                'id',
                'original_data'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_logging_event_changes'),
                'id',
                'result_data'
            ),
        ];
        $queryModifier = $this->queryModifierFactory->create(
            'in',
            [
                'values' => [
                    'path' => [
                        'admin/magento_logging/actions',
                    ]
                ]
            ]
        );
        $fieldsToUpdate[] = new FieldToConvert(
            SerializedToJson::class,
            $this->moduleDataSetup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );

        $this->aggregatedFieldConverter->convert($fieldsToUpdate, $this->resource->getConnection('default'));
    }

    /**
     * Data Convert for Reward Module
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    protected function rewardConvert()
    {
        $fieldsToUpdate[] = new FieldToConvert(
            SerializedToJson::class,
            $this->moduleDataSetup->getTable('magento_reward_history'),
            'history_id',
            'additional_data'
        );
        $this->aggregatedFieldConverter->convert($fieldsToUpdate, $this->resource->getConnection('default'));
    }

    /**
     * Data Convert for RMA Module
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    protected function rmaConvert()
    {
        $fields = [
            new FieldToConvert(
                SerializedDataConverter::class,
                $this->moduleDataSetup->getTable('magento_rma_item_entity'),
                'entity_id',
                'product_options'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_rma_shipping_label'),
                'entity_id',
                'packages'
            ),
        ];

        $this->aggregatedFieldConverter->convert($fields, $this->resource->getConnection('default'));
    }
}
