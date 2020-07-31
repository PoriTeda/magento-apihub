<?php
// @codingStandardsIgnoreFile
namespace Riki\StockPoint\Setup;

use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * Contructor
     *
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(CustomerSetupFactory $customerSetupFactory) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        // NED-290: Add new attributes for table customer_address_entity
        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $attributes = [
                'geometry_hash' => [
                    'type' => 'varchar',
                    'input' => 'text',
                    'required' => false,
                    'visible' => false,
                    'sort_order' => 1000,
                    'position' => 1000,
                    'system' => 0,
                    'label' => 'Geometry Hash',
                ],
                'latitude' => [
                    'type' => 'decimal',
                    'input' => 'text',
                    'required' => false,
                    'visible' => false,
                    'sort_order' => 1100,
                    'position' => 1100,
                    'system' => 0,
                    'label' => 'Latitude',
                ],
                'longitude' => [
                    'type' => 'decimal',
                    'input' => 'text',
                    'required' => false,
                    'visible' => false,
                    'sort_order' => 1200,
                    'position' => 1200,
                    'system' => 0,
                    'label' => 'Longitude',
                ]
            ];

            foreach ($attributes as $code => $options) {
                $customerSetup->addAttribute(
                    'customer_address',
                    $code,
                    $options
                );

                $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', $code);
                $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address']);
                $attribute->save();
            }
        }

        $setup->endSetup();
    }
}
