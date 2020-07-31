<?php
// @codingStandardsIgnoreFile
namespace Riki\Tax\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * install tax classes
         */
        $data = [
            [
                'class_id' => 4,
                'class_name' => 'Food',
                'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT,
            ],
            [
                'class_id' => 5,
                'class_name' => 'Machine',
                'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT,
            ],
            [
                'class_id' => 6,
                'class_name' => 'Penalty fee',
                'class_type' => \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT
            ],
        ];
        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_class'), $row);
        }

        /**
         * install tax calculation rates
         */
        $data = [
            [
                'tax_calculation_rate_id' => 3,
                'tax_country_id' => 'JP',
                'tax_region_id' => '*',
                'tax_postcode' => '*',
                'code' => 'Food Rate',
                'rate' => '8',
            ],
            [
                'tax_calculation_rate_id' => 4,
                'tax_country_id' => 'JP',
                'tax_region_id' => '*',
                'tax_postcode' => '*',
                'code' => 'Machine Rate',
                'rate' => '8'
            ],
            [
                'tax_calculation_rate_id' => 5,
                'tax_country_id' => 'JP',
                'tax_region_id' => '*',
                'tax_postcode' => '*',
                'code' => 'Penalty Fee Rate',
                'rate' => '0'
            ],
        ];
        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_calculation_rate'), $row);
        }

        /**
         * install tax calculation rules
         */
        $data = [
            [
                'tax_calculation_rule_id' => 1,
                'code' => 'Food tax',
                'priority' => 0,
                'position' => 0,
                'calculate_subtotal' => 0
            ],
            [
                'tax_calculation_rule_id' => 2,
                'code' => 'Machine tax',
                'priority' => 0,
                'position' => 0,
                'calculate_subtotal' => 0
            ],
            [
                'tax_calculation_rule_id' => 3,
                'code' => 'Penalty fee tax',
                'priority' => 0,
                'position' => 0,
                'calculate_subtotal' => 0
            ],
        ];

        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_calculation_rule'), $row);
        }

        /**
         * install tax calculation 
         */
        $data = [
            [
                'tax_calculation_id' => 1,
                'tax_calculation_rate_id' => 3,
                'tax_calculation_rule_id' => 1,
                'customer_tax_class_id' => 3,
                'product_tax_class_id' => 4
            ],
            [
                'tax_calculation_id' => 2,
                'tax_calculation_rate_id' => 4,
                'tax_calculation_rule_id' => 2,
                'customer_tax_class_id' => 3,
                'product_tax_class_id' => 5
            ],
            [
                'tax_calculation_id' => 3,
                'tax_calculation_rate_id' => 5,
                'tax_calculation_rule_id' => 3,
                'customer_tax_class_id' => 3,
                'product_tax_class_id' => 6
            ],
        ];

        foreach ($data as $row) {
            $setup->getConnection()->insertForce($setup->getTable('tax_calculation'), $row);
        }

    }
}
