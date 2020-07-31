<?php
// @codingStandardsIgnoreFile
namespace Riki\Tax\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Tax\Model\Calculation;

class UpgradeData implements UpgradeDataInterface
{
    const COD_PAYMENT_FEE_TEN_PERCENT = 330;

    const EIGHT_PERCENT_TAX_CODE = '8 Percent';

    const TEN_PERCENT_TAX_CODE = '10 Percent';

    /**
     * @var array
     */
    private $defaultProductTaxClassNames = [
        'Taxable Goods',
        'Retail Customer',
        'Food',
        'Machine'
    ];

    /**
     * @var array
     */
    private $updateTaxConfigPaths = [
        'tax/classes/wrapping_tax_class',
        'tax/classes/payment_tax_class',
        'tax/classes/shipping_tax_class'
    ];

    protected $_resourceConfig;

    protected $_salesConnection;

    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel
    )
    {
        $this->_resourceConfig = $resourceConfig;
        $this->_salesConnection = $orderResourceModel->getConnection();
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /**
         * Configuration tax
         */
        if (version_compare($context->getVersion(), '0.0.2') < 0) {

            // calculation
            $this->_resourceConfig->saveConfig('tax/calculation/round', 'floor', 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/algorithm', Calculation::CALC_UNIT_BASE, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/based_on', 'origin', 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/price_includes_tax', 0, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/shipping_includes_tax', 0, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/apply_after_discount', 0, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/discount_tax', 1, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/calculation/apply_tax_on', 0, 'default', 0);

            // destination
            $this->_resourceConfig->saveConfig('tax/defaults/country', 'JP', 'default', 0);

            // display
            $this->_resourceConfig->saveConfig('tax/display/type', 2, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/display/shipping', 2, 'default', 0);
        }

        /**
         * hide the full tax summary
         */
        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $this->_resourceConfig->saveConfig('tax/cart_display/full_summary', 0, 'default', 0);
            $this->_resourceConfig->saveConfig('tax/sales_display/full_summary', 0, 'default', 0);
        }

        /**
         * Setup payment tax config
         */
        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            /* try to detect tax class payment exist*/
            $taxClassTable = $setup->getTable('tax_class');
            $className = \Riki\Tax\Model\ClassModel::TAX_CLASS_NAME_PAYMENT;
            $rateCode = \Riki\Tax\Model\ClassModel::TAX_RATE_CODE_PAYMENT;

            $sql = "SELECT count(class_id) FROM $taxClassTable WHERE class_name = '".$className."'";
            $count = $setup->getConnection()->fetchOne($sql);
            if( $count <= 0 ){
                $data1 = [
                    [
                        'class_name' => \Riki\Tax\Model\ClassModel::TAX_CLASS_NAME_PAYMENT,
                        'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT
                    ],
                    [
                        'class_name' => \Riki\Tax\Model\ClassModel::TAX_CLASS_NAME_SHIPPING,
                        'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT
                    ],
                    [
                        'class_name' => \Riki\Tax\Model\ClassModel::TAX_CLASS_NAME_WRAPPING,
                        'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT
                    ]
                ];
                foreach ($data1 as $row) {
                    $setup->getConnection()->insertForce($taxClassTable, $row);
                }

                $data2 = [
                    [
                        'tax_calculation_rate_id' => 6,
                        'tax_country_id' => 'JP',
                        'tax_region_id' => '*',
                        'tax_postcode' => '*',
                        'code' => \Riki\Tax\Model\ClassModel::TAX_RATE_CODE_PAYMENT,
                        'rate' => '8'
                    ],
                    [
                        'tax_calculation_rate_id' => 7,
                        'tax_country_id' => 'JP',
                        'tax_region_id' => '*',
                        'tax_postcode' => '*',
                        'code' => \Riki\Tax\Model\ClassModel::TAX_RATE_CODE_SHIPPING,
                        'rate' => '8'
                    ],
                    [
                        'tax_calculation_rate_id' => 8,
                        'tax_country_id' => 'JP',
                        'tax_region_id' => '*',
                        'tax_postcode' => '*',
                        'code' => \Riki\Tax\Model\ClassModel::TAX_RATE_CODE_WRAPPING,
                        'rate' => '8'
                    ]
                ];
                foreach ($data2 as $row) {
                    $setup->getConnection()->insertOnDuplicate($setup->getTable('tax_calculation_rate'), $row);
                }
            }

            $data3 = [
                [
                    'tax_calculation_rule_id' => 4,
                    'code' => \Riki\Tax\Model\ClassModel::TAX_RULE_CODE_PAYMENT,
                    'priority' => 0,
                    'position' => 0,
                    'calculate_subtotal' => 0
                ],
                [
                    'tax_calculation_rule_id' => 5,
                    'code' => \Riki\Tax\Model\ClassModel::TAX_RULE_CODE_SHIPPING,
                    'priority' => 0,
                    'position' => 0,
                    'calculate_subtotal' => 0
                ],
                [
                    'tax_calculation_rule_id' => 6,
                    'code' => \Riki\Tax\Model\ClassModel::TAX_RULE_CODE_WRAPPING,
                    'priority' => 0,
                    'position' => 0,
                    'calculate_subtotal' => 0
                ]
            ];
            foreach ($data3 as $row) {
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tax_calculation_rule'), $row);
            }

            $customerClassId = 3;
            $data4 = [
                [
                    'tax_calculation_rate_id' => 6,
                    'tax_calculation_rule_id' => 4,
                    'customer_tax_class_id' => $customerClassId,
                    'product_tax_class_id' => 7
                ],
                [
                    'tax_calculation_rate_id' => 7,
                    'tax_calculation_rule_id' => 5,
                    'customer_tax_class_id' => $customerClassId,
                    'product_tax_class_id' => 8
                ],
                [
                    'tax_calculation_rate_id' => 8,
                    'tax_calculation_rule_id' => 6,
                    'customer_tax_class_id' => $customerClassId,
                    'product_tax_class_id' => 9
                ]
            ];
            foreach ($data4 as $row) {
                $setup->getConnection()->insertForce($setup->getTable('tax_calculation'), $row);
            }
        }

        /**
         * show grand total
         */
        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $this->_resourceConfig->saveConfig('tax/sales_display/grandtotal', 1, 'default', 0);
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('quote_item');
            $connection->addColumn($tableName,
                'commission_condition_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Value is configurable from backend',
                ]
            );
            $tableName = $setup->getTable('sales_order_item');
            $connection->addColumn($tableName,
                'commission_condition_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Value is configurable from backend',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                'tax_for_authority',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => null,
                    'comment' => 'Tax for tax authority',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $this->_updateTaxConfiguration($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function _updateTaxConfiguration($setup)
    {
        $connection = $setup->getConnection();
        $taxClassTbl = $setup->getTable('tax_class');
        $coreConfigDataTbl = $setup->getTable('core_config_data');
        $select = $connection->select()
            ->from($taxClassTbl, 'class_id')
            ->where('class_name = ?', self::TEN_PERCENT_TAX_CODE);
        $tenPercentTaxClassId = $connection->fetchOne($select);
        foreach ($this->updateTaxConfigPaths as $configPath) {
            $bind = ['value' => $tenPercentTaxClassId];
            $where = ['path = ?' => $configPath];
            $connection->update($coreConfigDataTbl, $bind, $where);
        }
    }
}
