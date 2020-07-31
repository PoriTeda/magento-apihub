<?php
// @codingStandardsIgnoreFile
namespace Riki\Customer\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory; /* For Attribute create  */

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    protected $_customerSetupFactory;
    protected $_config;
    protected $eavSetupFactory;
    protected $indexerFactory;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    /**
     * Init
     *
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        Config $config, EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
    )

    {
        $this->_customerSetupFactory = $customerSetupFactory;
        $this->_config = $config;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->_eavAttribute = $eavAttribute;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->indexerFactory = $indexerFactory;
        $this->_storeManager = $storeManager;
        $this->dateTime = $dateTime;
        $this->_conditionFactory = $conditionFactory;

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

        $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributes = [
                'firstnamekana' =>
                    [
                        'is_required' => true,
                        'is_user_defined' => 0,
                        'is_system' => 0,
                        'sort_order' => 45,
                        'position' => 45,
                    ],
                'lastnamekana' =>
                    [
                        'is_required' => true,
                        'is_user_defined' => 0,
                        'is_system' => 0,
                        'sort_order' => 65,
                        'position' => 65,
                    ]
            ];

            foreach ($attributes as $code => $options) {
                $customerSetup->updateAttribute(
                    Customer::ENTITY,
                    $code,
                    $options
                );

                $customerSetup->updateAttribute(
                    'customer_address',
                    $code,
                    array_merge($options, [
                        'sort_order' => $code == 'firstnamekana' ? 25 : 42,
                        'position' => $code == 'firstnamekana' ? 25 : 42,
                    ])
                );

                $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $code);
                $attribute->setData('used_in_forms', ['adminhtml_checkout', 'adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
                $attribute->save();

                $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', $code);
                $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address']);
                $attribute->save();
            }

            $customerSetup->addAttribute('customer_address', 'riki_nickname', [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => true,
                'system' => 0,
                'sort_order' => 5,
                'position' => 5,
                'label' => 'Nickname',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'riki_nickname');
            $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address']);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'shosha_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 170,
                'position' => 170,
                'label' => 'Shosha Name',
            ]);
            $attributeName = 'b2b_flag';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'checkbox',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 180,
                'position' => 180,
                'label' => 'B2B',
            ]);

            $attributeName = 'shosha_brand_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 190,
                'position' => 190,
                'label' => 'Shosha branch name',
            ]);

            $attributeName = 'shosha_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 200,
                'position' => 200,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:3;s:15:"min_text_length";i:3;}',
                'label' => 'Shosha code',
            ]);

            $attributeName = 'business_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 210,
                'position' => 210,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:10;s:15:"min_text_length";i:10;}',
                'label' => 'Business code',
            ]);

            $attributeName = 'commission';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'decimal',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 220,
                'position' => 220,
                'label' => 'Commission',
            ]);

            $attributeName = 'company_name_kana';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 230,
                'position' => 230,
                'label' => 'Company name (Kana)',
            ]);

            $attributeName = 'department';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 240,
                'position' => 240,
                'label' => 'Department',
            ]);

            $attributeName = 'department_kana';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 250,
                'position' => 250,
                'label' => 'Department (Kana)',
            ]);
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'b2b_flag';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 180,
                'position' => 180,
                'label' => 'B2B'
            ]);
        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {

            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->addAttribute('customer_address', 'riki_type_address', [
                'type' => 'varchar',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Address\AddressType',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 5,
                'position' => 5,
                'label' => 'Address Type',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'riki_type_address');
            $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address']);
            $attribute->save();

            $table = $setup->getTable('sales_order_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_type_address',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Address Type']
                );
            }

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);


            $customerSetup->addAttribute(
                Customer::ENTITY,
                'riki_ambassador',
                [
                    'type' => 'int',
                    'label' => 'Riki Ambassador',
                    'input' => 'select',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'default' => 0,
                    'required' => false,
                    'sort_order' => 60,
                    'position' => 60,
                    'visible' => true,
                    'system' => false
                ]
            );

            // add attribute to form
            /** @var  $attribute */

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'riki_ambassador');
            $attribute->setData('used_in_forms', ['adminhtml_checkout', 'adminhtml_customer']);
            $attribute->save();

            $table = $setup->getTable('sales_order_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_ambassador',
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => 0, 'comment' => 'Ambassador']
                );
            }

        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'consumer_db_id';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'unique' => true,
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 310,
                'position' => 310,
                'label' => 'ConsumerDB_ID'
            ]);

            $attributeName = 'consumer_db_last_update_date';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'datetime',
                'visible' => false,
                'required' => false,
                'system' => 0,
                'sort_order' => 320,
                'position' => 320,
                'label' => 'Consumer DB Last update date'
            ]);
        }
        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'shosha_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 170,
                'position' => 170,
                'label' => 'Shosha Name',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'b2b_flag';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 180,
                'position' => 180,
                'label' => 'B2B'
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'shosha_brand_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 190,
                'position' => 190,
                'label' => 'Shosha branch name',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'shosha_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 200,
                'position' => 200,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:3;s:15:"min_text_length";i:3;}',
                'label' => 'Shosha code',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'business_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 210,
                'position' => 210,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:10;s:15:"min_text_length";i:10;}',
                'label' => 'Business code',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'commission';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'decimal',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 220,
                'position' => 220,
                'label' => 'Commission',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'company_name_kana';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 230,
                'position' => 230,
                'label' => 'Company name (Kana)',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeNameOld = 'department';

            $customerSetup->removeAttribute('customer', $attributeNameOld);
            $attributeNamedmew = 'apartment';
            $customerSetup->addAttribute('customer', $attributeNamedmew, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 240,
                'position' => 240,
                'label' => 'Department',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeNamedmew);
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();

            $attributeNameOld = 'department_kana';

            $customerSetup->removeAttribute('customer', $attributeNameOld);
            $attributeNamedmew = 'apartment_kana';
            $customerSetup->addAttribute('customer', $attributeNamedmew, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 250,
                'position' => 250,
                'label' => 'Department (Kana)',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeNamedmew);
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeNameOld = 'apartment';
            $customerSetup->removeAttribute('customer', $attributeNameOld);
            $attributeNamedmew = 'department';
            $customerSetup->addAttribute('customer', $attributeNamedmew, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 240,
                'position' => 240,
                'label' => 'Department',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeNamedmew);
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();

            $attributeNameOld = 'apartment_kana';
            $customerSetup->removeAttribute('customer', $attributeNameOld);
            $attributeNamedmew = 'department_kana';
            $customerSetup->addAttribute('customer', $attributeNamedmew, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 250,
                'position' => 250,
                'label' => 'Department (Kana)',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeNamedmew);
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();

            $customerSetup->addAttribute('customer_address', 'apartment', [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 50,
                'position' => 5,
                'label' => 'Apartment',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'apartment');
            $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address']);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->addAttribute('customer_address', 'riki_nickname', [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => true,
                'system' => 0,
                'sort_order' => 5,
                'position' => 5,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:20;s:15:"min_text_length";i:0;}',
                'label' => 'お届け先選択で表示名(全角)',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'riki_nickname');
            $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address', 'adminhtml_checkout']);
            $attribute->save();

            $customerSetup->addAttribute('customer_address', 'apartment', [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 50,
                'position' => 5,
                'label' => 'アパート•マンション•ビル(全角)',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'apartment');
            $attribute->setData('used_in_forms', ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address', 'adminhtml_checkout']);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), '1.1.1', '<')) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributes = array(
                "status_machine_NBA" => "Status machine NBA",
                "status_machine_NDG" => "Status machine NDG",
                "status_machine_SPT" => "status machine SPT",
                "status_machine_BLC" => "Status machine BLC",
                "status_machine_Nespresso" => "Status machine Nespresso"
            );
            $sortOrder = 260;
            foreach ($attributes as $field => $label) {
                $sortOrder += 10;
                $customerSetup->addAttribute(
                    'customer', $field, [
                        'type' => 'int',
                        'input' => 'select',
                        'visible' => true,
                        'required' => false,
                        'system' => 0,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'label' => $label,
                        'source' => 'Riki\Customer\Model\StatusMachine',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
                    ]
                );
            }
        }


        if (version_compare($context->getVersion(), '1.1.4', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            /**
             * Add attributes to the eav/attribute
             */
            $eavSetup->removeAttribute('customer', 'japan_firstname');
            $eavSetup->removeAttribute('customer', 'japan_lastname');
        }
        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'consumer_db_id';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'unique' => true,
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 310,
                'position' => 310,
                'label' => 'ConsumerDB_ID'
            ]);

            $attributeName = 'consumer_db_last_update_date';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'datetime',
                'visible' => false,
                'required' => false,
                'system' => 0,
                'sort_order' => 320,
                'position' => 320,
                'label' => 'Consumer DB Last update date'
            ]);
        }
        if (version_compare($context->getVersion(), '1.1.5', '<')) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->removeAttribute('customer', 'riki_ambassador');
            $table = $setup->getTable('quote');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} DROP COLUMN customer_riki_ambassador ");
            }
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} DROP COLUMN customer_riki_ambassador ");
            }
            $table = $setup->getTable('sales_order_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} DROP COLUMN riki_ambassador ");
            }
        }

        if (version_compare($context->getVersion(), '1.1.7') < 0) {

            $this->_config->saveConfig('customer/address_templates/text',
                '{{var riki_nickname}}
                    {{var lastname}}{{depend firstname}}
                     {{if postcode}}〒{{var postcode}}{{/if}}
                   {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    T: {{var telephone}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/oneline',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if city}}{{var city}},  {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/html',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if city}}{{var city}},  {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/pdf',
                '{{var riki_nickname}}
                     {{var lastname}}{{depend firstname}}
                     {{if postcode}}〒{{var postcode}}{{/if}}
                   {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    T: {{var telephone}}',
                'default', 0);
        }

        if (version_compare($context->getVersion(), '1.2.0') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'consumer_db_id');
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'consumer_db_last_update_date');
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();


            $attributes = array(
                "status_machine_NBA" => "Status machine NBA",
                "status_machine_NDG" => "Status machine NDG",
                "status_machine_SPT" => "status machine SPT",
                "status_machine_BLC" => "Status machine BLC",
                "status_machine_Nespresso" => "Status machine Nespresso"
            );
            foreach ($attributes as $field => $label) {
                $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $field);
                $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
                $attribute->save();
            }
        }


        if (version_compare($context->getVersion(), '1.2.1') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'offline_customer';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 79,
                'position' => 79,
                'label' => 'Offline Customer'
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.2') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'offline_customer';

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), '1.2.3') < 0) {

            $attributeId = $this->_eavAttribute->getIdByCode('customer_address', 'postcode');
            if ($attributeId) {
                $table = $setup->getTable('customer_eav_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("UPDATE  {$table} SET sort_order = 71  WHERE attribute_id = {$attributeId} ");
                }
            }
        }

        if (version_compare($context->getVersion(), '1.2.4') < 0) {
            /**
             * CustomerSetup.php
             * @var \Magento\Customer\Model\Setup
             */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->addAttribute('customer', 'amb_sale', [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 80,
                'position' => 80,
                'label' => 'Ambassador Sales',
                'default' => 0
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.5') < 0) {

            $attributeUpdate = ['prefix' => 6, 'lastnamekana' => 35, 'suffix' => 41, 'company' => 42, 'postcode' => 43, 'region' => 44, 'city' => 45, 'apartment' => 46,
                'street' => 47, 'country_id' => 48, 'telephone' => 49];
            foreach ($attributeUpdate as $key => $value) {
                $attributeId = $this->_eavAttribute->getIdByCode('customer_address', $key);
                if ($attributeId) {
                    $table = $setup->getTable('customer_eav_attribute');
                    if ($setup->getConnection()->isTableExists($table) == true) {
                        $setup->run("UPDATE  {$table} SET sort_order = {$value}  WHERE attribute_id = {$attributeId} ");
                    }
                }
            }

        }
        if (version_compare($context->getVersion(), '1.2.6') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);


            //remove old
            $attributeName = 'department_kana';
            $customerSetup->removeAttribute('customer', $attributeName);
            $attributeName = 'shosha_brand_name';
            $customerSetup->removeAttribute('customer', $attributeName);
            $attributeName = 'shosha_name';
            $customerSetup->removeAttribute('customer', $attributeName);
            $attributeName = 'business_code';
            $customerSetup->removeAttribute('customer', $attributeName);
            $attributeName = 'company_name_kana';
            $customerSetup->removeAttribute('customer', $attributeName);


            $attributeName = 'department';
            $customerSetup->removeAttribute('customer', $attributeName);
//            //Create new
            $attributeName = 'shosha_business_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 210,
                'position' => 210,
                'label' => 'Business code',
            ]);
            $attributeName = 'shosha_cmp';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 230,
                'position' => 230,
                'label' => 'Company name',
            ]);

            $attributeName = 'shosha_cmp_kana';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 230,
                'position' => 230,
                'label' => 'Company name - Kana',
            ]);
            $attributeName = 'shosha_dept';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 240,
                'position' => 240,
                'label' => 'Company department name',
            ]);

            $attributeName = 'shosha_dept_kana';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 241,
                'position' => 241,
                'label' => 'Company department name - Kana',
            ]);

            $attributeName = 'shosha_in_charge';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 242,
                'position' => 242,
                'label' => 'Name of person in charge',
            ]);

            $attributeName = 'shosha_in_charge_kana';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 243,
                'position' => 243,
                'label' => 'Name of person in charge - Kana',
            ]);

            $attributeName = 'invoice_postode';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 244,
                'position' => 244,
                'label' => 'Company zipcode',
            ]);

            $attributeName = 'invoice_address1';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 245,
                'position' => 245,
                'label' => 'Company address 1',
            ]);
            $attributeName = 'invoice_address2';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 246,
                'position' => 246,
                'label' => 'Company address 2',
            ]);

            $attributeName = 'invoice_address1_kana';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 247,
                'position' => 247,
                'label' => 'Company address 1 - Kana',
            ]);
            $attributeName = 'invoice_address2_kana';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 248,
                'position' => 248,
                'label' => 'Company address 2 - Kana',
            ]);
            $attributeName = 'shosha_phone';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 249,
                'position' => 249,
                'label' => 'Company phone number',
            ]);
            $attributeName = 'shosha_comission';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 250,
                'position' => 250,
                'label' => 'Commission',
            ]);

            //end
            $attributeName = 'shosha_code';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Shosha\ShoshaCode',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 200,
                'position' => 200,
                'label' => 'Shosha code',
            ]);

            $attributeName = 'shosha_first_code';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Shosha\StoreCode',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 251,
                'position' => 251,
                'label' => 'First Code',
            ]);

            $attributeName = 'shosha_second_code';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Shosha\StoreCode',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 252,
                'position' => 252,
                'label' => 'Second Code',
            ]);
            //apply to form
            $attributesName = [
                'shosha_code',
                'shosha_business_code',
                'shosha_cmp',
                'shosha_cmp_kana',
                'shosha_dept',
                'shosha_dept_kana',
                'shosha_in_charge',
                'shosha_in_charge_kana',
                'invoice_postode',
                'invoice_address1',
                'invoice_address2',
                'invoice_address1_kana',
                'invoice_address2_kana',
                'shosha_phone',
                'shosha_first_code',
                'shosha_second_code',
                'shosha_comission',

            ];
            foreach ($attributesName as $attributeName) {
                $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
                $attribute->setData('used_in_forms', ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']);
                $attribute->save();
            }

        }

        if (version_compare($context->getVersion(), '1.2.7') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->addAttribute(
                Customer::ENTITY, 'nhs_introducer',
                [
                    'type' => 'varchar',
                    'label' => 'NHS introducer',
                    'input' => 'text',
                    'default' => '',
                    'required' => false,
                    'sort_order' => 1000,
                    'position' => 1000,
                    'visible' => true,
                    'user_defined' => true,
                    'system' => false
                ]
            );

            // add attribute to form

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'nhs_introducer');
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.2.8') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);


            $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'multiple_website',
                [
                    'type' => 'varchar',
                    'label' => 'Associate to multiple Website',
                    'input' => 'multiselect',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'source' => 'Riki\Customer\Model\Config\Source\MultipleWebsite',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'default' => 1,
                    'required' => true,
                    'sort_order' => 11,
                    'position' => 11,
                    'visible' => true,
                    'system' => false,
                    'user_defined' => true,
                ]
            );


            // add attribute to form
            /** @var  $attribute */
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'multiple_website')
                ->addData([
                    'attribute_set_id' => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms' => ['adminhtml_customer'],
                ]);

            $attribute->save();

        }
        if (version_compare($context->getVersion(), '1.2.9') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'consumer_db_id',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }

        if (version_compare($context->getVersion(), '1.3.0') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'amb_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\AmbType',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 1,
                'position' => 1,
                'label' => 'Is Ambassador?',
            ]);
            // add attribute to form
            $customerSetup->getEavConfig()->clear();
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'occupation';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Occupation',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 98,
                'position' => 98,
                'label' => 'Occupation',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();


            $attributeName = 'marital_status';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Marital',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 101,
                'position' => 101,
                'label' => 'Marital status',
            ]);
            // add attribute to form
//            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
//            $attribute->setData('used_in_forms', ['adminhtml_customer']);
//            $attribute->save();
//


            $attributeName = 'email_1_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\EmailType',
                'visible' => true,
                'required' => true,
                'system' => 0,
                'sort_order' => 79,
                'position' => 79,
                'label' => 'Email 1 type',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'email_2_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\EmailType',
                'visible' => true,
                'required' => true,
                'system' => 0,
                'sort_order' => 82,
                'position' => 82,
                'label' => 'Email 2 type',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
            $attributeName = 'email_2';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'validate_rules' => 'a:1:{s:16:"input_validation";s:5:"email";}',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 84,
                'position' => 84,
                'label' => 'Email 2',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();


            $attributeName = 'application_date';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'datetime',
                'label' => 'Application date',
                'input' => 'date',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'required' => false,
                'sort_order' => 103,
                'system' => false,
                'input_filter' => 'date',
                'validate_rules' => 'a:1:{s:16:"input_validation";s:4:"date";}',
                'position' => 103
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'authorized_date';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'datetime',
                'label' => 'Authorized date',
                'input' => 'date',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'required' => false,
                'sort_order' => 105,
                'system' => false,
                'input_filter' => 'date',
                'input_filter' => 'date',
                'validate_rules' => 'a:1:{s:16:"input_validation";s:4:"date";}',
                'position' => 105
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'petshop_code';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\Petshop',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 106,
                'position' => 106,
                'label' => 'Petshop',
            ]);
//            // add attribute to form
//            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
//            $attribute->setData('used_in_forms', ['adminhtml_customer']);
//            $attribute->save();
//
            $attributeName = 'pet_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'label' => 'Pet name',
                'input' => 'text',
                'default' => '',
                'required' => false,
                'sort_order' => 107,
                'position' => 107,
                'visible' => true,
                'user_defined' => true,
                'system' => false
            ]);
            // add attribute to form
//            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
//            $attribute->setData('used_in_forms', ['adminhtml_customer']);
//            $attribute->save();

            $attributeName = 'pet_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'label' => 'Pet type',
                'input' => 'text',
                'default' => '',
                'required' => false,
                'sort_order' => 108,
                'position' => 108,
                'visible' => true,
                'user_defined' => true,
                'system' => false
            ]);
            // add attribute to form
//            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
//            $attribute->setData('used_in_forms', ['adminhtml_customer']);
//            $attribute->save();

            $attributeName = 'pet_gender';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 109,
                'position' => 109,
                'label' => 'Pet gender',
                'option' => ['values' => ['Male', 'Female']],
            ]);
            // add attribute to form
//            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
//            $attribute->setData('used_in_forms', ['adminhtml_customer']);
//            $attribute->save();

            $attributeName = 'pet_birth_date';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'datetime',
                'label' => 'Pet birth date',
                'input' => 'date',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'required' => false,
                'sort_order' => 111,
                'system' => false,
                'input_filter' => 'date',
                'validate_rules' => 'a:1:{s:16:"input_validation";s:4:"date";}',
                'position' => 111
            ]);

            $attributeName = 'amb_com_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 660,
                'position' => 660,
                'label' => 'Amb company name'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'amb_com_division_name';
//            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 670,
                'position' => 670,
                'label' => 'Amb company name'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'amb_com_division_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 680,
                'position' => 680,
                'label' => 'Amb company department name'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'amb_charge_person';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 690,
                'position' => 690,
                'label' => 'Amb name of person in charge'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'amb_ph_num';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 700,
                'position' => 700,
                'label' => 'Amb company phone number'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'consumer_db_id';
            $customerSetup->updateAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'unique' => true,
                'is_visible' => false,
                'is_used_for_customer_segment' => false,
                'required' => false,
                'system' => 0,
                'sort_order' => 310,
                'position' => 310,
                'label' => 'ConsumerDB_ID'
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'consumer_db_id');
            $attribute->setData('used_in_forms', null);
            $attribute->save();

            $customerSetup->updateAttribute('customer', 'amb_sale', [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 650,
                'position' => 650,
                'label' => 'Ambassador Sales',
                'default' => 0
            ]);
        }

        if (version_compare($context->getVersion(), '1.3.1') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'email_1_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\EmailType',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 79,
                'position' => 79,
                'label' => 'Email 1 type',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'email_2_type';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Riki\Customer\Model\EmailType',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 82,
                'position' => 82,
                'label' => 'Email 2 type',
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.3.2') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'b2b_flag';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 180,
                'position' => 180,
                'label' => 'Invoice Customer'
            ]);
        }

        if (version_compare($context->getVersion(), '1.3.3') < 0) {

            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'commission';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'decimal',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 119,
                'position' => 119,
                'label' => 'Commission',
            ]);
            // add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'b2b_flag',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );


            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_code',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_first_code',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_cmp',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_cmp_kana',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_dept',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_dept_kana',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_in_charge',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_in_charge_kana',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'shosha_business_code',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }

        if (version_compare($context->getVersion(), '1.3.4') < 0) {
            $attributeName = 'block_orders';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'default' => 0,
                'sort_order' => 320,
                'position' => 320,
                'label' => 'Block Orders'
            ]);

            $customerSetup->getEavConfig()->clear();
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'b2b_flag',
                [
                    'default_value' => 0
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.3.6') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'shosha_phone';
            $customerSetup->updateAttribute(
                Customer::ENTITY,
                $attributeName,
                [
                    'is_required' => true
                ]
            );

            $attributeName = 'shosha_comission';
            $customerSetup->updateAttribute(
                Customer::ENTITY,
                $attributeName,
                [
                    'sort_order' => 253,
                    'position' => 253
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'b2b_flag',
                [
                    'default_value' => 0
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.3.7') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributeName = 'shosha_phone';
            $customerSetup->updateAttribute(
                Customer::ENTITY,
                $attributeName,
                [
                    'is_required' => false
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.3.8') < 0) {

            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'shosha_comission';
            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'decimal',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 253,
                'position' => 253,
                'label' => 'Commission',
            ]);

            // add attribute to form
            $customerSetup->getEavConfig()->clear();
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), '1.3.9') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $petAttr = [
                'petshop_code', 'application_date', 'authorized_date', 'pet_name', 'pet_type', 'pet_gender', 'pet_birth_date', 'amb_charge_person'
            ];
            foreach ($petAttr as $attributeCode) {
                $customerSetup->removeAttribute('customer', $attributeCode);
            }
        }

        if (version_compare($context->getVersion(), '1.4.0') < 0) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $petAttr = [
                "marital_status", "occupation"
            ];
            foreach ($petAttr as $attributeCode) {
                $customerSetup->removeAttribute('customer', $attributeCode);
            }
        }

        if (version_compare($context->getVersion(), '1.4.1') < 0) {
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'block_orders',
                [
                    'default_value' => 0
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.4.2') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'is_whitelisted';
            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'int',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'default' => 1,
                'sort_order' => 254,
                'position' => 254,
                'label' => 'Is Whitelisted',
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.4.3') < 0) {

            $attributes = [
                'lastname' =>
                    [
                        'validate_rules' => 'a:2:{s:15:"max_text_length";i:100;s:15:"min_text_length";i:0;}',
                    ]
            ];

            foreach ($attributes as $code => $options) {
                $customerSetup->updateAttribute(
                    'customer_address',
                    $code,
                    $options
                );
            }
        }


        if (version_compare($context->getVersion(), '1.4.4') < 0) {

            $data = [
                [
                    'Segment for Employee',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"9";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 9) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for CNC_Status',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"5";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 5) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for CIS_Status',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"6";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 6) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for MILANO_Status',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"7";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 7) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for ALEGRIA_Status',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"8";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 8) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for ambassador company address',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:2:{i:0;a:7:{s:4:"type";s:64:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Address";s:9:"attribute";N;s:8:"operator";N;s:5:"value";i:1;s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:75:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes";s:9:"attribute";s:7:"company";s:8:"operator";s:2:"==";s:5:"value";s:0:"";s:18:"is_value_processed";b:0;}}}i:1;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"3";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_address_entity') . '` AS `customer_address` WHERE (customer_address.parent_id = :customer_id) AND ((IF((SELECT 1 FROM `' . $setup->getTable('customer_address_entity') . '` AS `val` WHERE (`val`.`entity_id` = `customer_address`.`entity_id`) AND (`val`.`company` = \'\') LIMIT 1), 1, 0) = 1)) LIMIT 1), 1, 0) = 1) AND (IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = \'3\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment for Ambassador',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:10:"membership";s:8:"operator";s:2:"==";s:5:"value";a:1:{i:0;s:1:"3";}s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_varchar') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'190\') AND (`main`.`value` = 3) LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Segment prefecture',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:7:{s:4:"type";s:64:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Address";s:9:"attribute";N;s:8:"operator";N;s:5:"value";i:1;s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:75:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes";s:9:"attribute";s:9:"region_id";s:8:"operator";s:2:"==";s:5:"value";s:0:"";s:18:"is_value_processed";b:0;}}}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_address_entity') . '` AS `customer_address` WHERE (customer_address.parent_id = :customer_id) AND ((IF((SELECT 1 FROM `' . $setup->getTable('customer_address_entity') . '` AS `val` WHERE (`val`.`entity_id` = `customer_address`.`entity_id`) AND (`val`.`region_id` = \'\') LIMIT 1), 1, 0) = 1)) LIMIT 1), 1, 0) = 1))'
                ],

            ];
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[0],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[1],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[2],
                    'apply_to' => 1
                ];
                $setup->getConnection()->insert($setup->getTable('magento_customersegment_segment'), $bind);

                $segmentId = $setup->getConnection()->lastInsertId($setup->getTable('magento_customersegment_segment'));

                $website = $this->_storeManager->getWebsites();
                $websiteIds = array();
                foreach ($website as $site) {
                    $websiteIds[] = $site->getId();
                    $bind = ['segment_id' => $segmentId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_customersegment_website'), $bind);
                }


                //match customer
                if (!empty($row[1])) {
                    $conditions = unserialize($row[1]);
                    if (is_array($conditions) && !empty($conditions)) {
                        $conditionObj = $this->_conditionFactory->create('Combine\Root');
                        $conditionObj->setRule($this)->setId('1')->setPrefix('conditions');
                        $conditionObj->loadArray($conditions);
                        $customerIds = $conditionObj->getSatisfiedIds($websiteIds);

                        $relatedCustomers = array();

                        foreach ($websiteIds as $websiteId) {

                            foreach ($customerIds as $customerId) {
                                $relatedCustomers[] = [
                                    'entity_id' => $customerId,
                                    'website_id' => $websiteId,
                                ];
                            }
                        }

                        $now = $this->dateTime->formatDate(time());

                        if (count($relatedCustomers)) {
                            $count = 0;
                            $data = [];
                            foreach ($relatedCustomers as $customer) {
                                $data[] = [
                                    'segment_id' => $segmentId,
                                    'customer_id' => $customer['entity_id'],
                                    'website_id' => $customer['website_id'],
                                    'added_date' => $now,
                                    'updated_date' => $now,
                                ];
                                $count++;
                                if ($count % 1000 == 0) {
                                    $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                                    $data = [];
                                }
                            }
                            if (!empty($data)) {
                                $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                            }
                        }
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '1.4.5') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'firstnamekana',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false,
                ]
            );

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'lastnamekana',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false,]
            );
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }

        if (version_compare($context->getVersion(), '1.4.6', '<')) {
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $attributes = array(
                "status_machine_NBA" => "Machine rental status (Barista)",
                "status_machine_NDG" => "Machine rental status (Dolce gusto)",
                "status_machine_SPT" => "Machine rental status (Special T)",
                "status_machine_BLC" => "Machine rental status (BLC)",
                "status_machine_Nespresso" => "Machine Machine rental status (Nespresso)"
            );
            foreach ($attributes as $field => $label) {
                $customerSetup->updateAttribute(
                    'customer', $field, [
                        'frontend_label' => $label,
                        'label' => $label
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.6.1') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'cedyna_counter';
            $customerSetup->removeAttribute('customer', $attributeName);
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'decimal',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'default' => 0,
                'sort_order' => 254,
                'position' => 254,
                'label' => 'Cedyna Monthly Counter',
            ]);
            $customerSetup->getEavConfig()->clear();
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.4.7') < 0) {
            $table = $setup->getTable('customer_address_entity');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'consumer_db_address_id',
                    ['type' => Table::TYPE_INTEGER, null, 'default' => null, 'comment' => 'Consumer db Address Id']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.1') < 0) {

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'membership',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }
        if (version_compare($context->getVersion(), '1.5.2', '<')) {

            $attributes = array(
                "status_machine_NBA" => "Machine rental status (Barista)",
                "status_machine_NDG" => "Machine rental status (Dolce gusto)",
                "status_machine_SPT" => "Machine rental status (Special T)",
                "status_machine_BLC" => "Machine rental status (BLC)",
                "status_machine_Nespresso" => "Machine Machine rental status (Nespresso)"
            );
            foreach ($attributes as $field => $label) {
                $setup->getConnection()->delete($setup->getTable('magento_customersegment_segment'), "`conditions_serialized` LIKE '%$field%'");
                $customerSetup->removeAttribute(
                    'customer', $field
                );
            }
        }
        if (version_compare($context->getVersion(), '1.5.3', '<')) {
            $attributes = array(
                "KEY_ADDRESS2" => "Address2",
                "KEY_ADDRESS3" => "Address3",
                "KEY_ADDRESS4" => "Address4"
            );
            $sortOrder = 49;
            $required = true;
            foreach ($attributes as $field => $label) {
                if($field == "KEY_ADDRESS4"){
                    $required = false;
                }
                $customerSetup->addAttribute(
                    'customer_address', $field,[
                        'type' => 'varchar',
                        'input' => 'text',
                        'visible' => true,
                        'required' => $required,
                        'system' => 0,
                        'label' => $label,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'used_in_forms' => ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address', 'adminhtml_checkout']
                    ]
                );
                $sortOrder+=1;
            }
        }
        if (version_compare($context->getVersion(), '1.5.4', '<')) {
            $attributes ="riki_address_legacy_id";
            $customerSetup->addAttribute(
                'customer_address', $attributes,[
                    'type' => 'varchar',
                    'unique' => true,
                    'visible' => false,
                    'required' => false,
                    'system' => 0,
                    'sort_order' => 150,
                    'position' => 150,
                    'label' => 'Consumer db address ID'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.5.5', '<')) {
            $attributes = array(
                "telephone" => "Phone number(XXX-XXXX-XXXX)"
            );
            foreach ($attributes as $field => $label) {
                $customerSetup->updateAttribute(
                    'customer_address', $field, [
                        'label' => $label,
                        'frontend_label' =>$label,
                        'backend_label' =>$label
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.5.6', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $attributes = array(
                "MD0000" => "Dolche Gusto",
                "PM0000" => "Barista",
                "SPM0000" => "Barista i",
                "NM0000" => "Nespresso",
                "ST0000" => "Special-T",
                "OT0000" => "Other"
            );
            $sortOrder = 800;
            foreach ($attributes as $field => $label) {
                $customerSetup->addAttribute(
                    'customer', $field,[
                        'type' => 'int',
                        'visible' => true,
                        'required' => false,
                        'system' => 0,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'label' => $label,
                        'is_used_for_customer_segment'=>'1'
                    ]
                );
                $sortOrder+=10;
            }
            foreach ($attributes as $field => $label) {
                $eavSetup->updateAttribute("customer", $field, 'is_used_for_customer_segment', '1');

            }

        }
        if (version_compare($context->getVersion(), '1.5.7') < 0) {

            $data = [
                [
                    'Machine Rental Status Barista i',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:7:"SPM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'508\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Barista',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"PM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'507\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Dolche Gusto',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"MD0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'506\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Nespresso',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"NM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'509\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Special-T',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"ST0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'510\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Other',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"OT0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'511\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],

            ];
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[0],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[1],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[2],
                    'apply_to' => 1
                ];
                $setup->getConnection()->insert($setup->getTable('magento_customersegment_segment'), $bind);

                $segmentId = $setup->getConnection()->lastInsertId($setup->getTable('magento_customersegment_segment'));

                $website = $this->_storeManager->getWebsites();
                $websiteIds = array();
                foreach ($website as $site) {
                    $websiteIds[] = $site->getId();
                    $bind = ['segment_id' => $segmentId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_customersegment_website'), $bind);
                }


                //match customer
                if (!empty($row[1])) {
                    $conditions = unserialize($row[1]);
                    if (is_array($conditions) && !empty($conditions)) {
                        $conditionObj = $this->_conditionFactory->create('Combine\Root');
                        $conditionObj->setRule($this)->setId('1')->setPrefix('conditions');
                        $conditionObj->loadArray($conditions);
                        $customerIds = $conditionObj->getSatisfiedIds($websiteIds);

                        $relatedCustomers = array();

                        foreach ($websiteIds as $websiteId) {

                            foreach ($customerIds as $customerId) {
                                $relatedCustomers[] = [
                                    'entity_id' => $customerId,
                                    'website_id' => $websiteId,
                                ];
                            }
                        }

                        $now = $this->dateTime->formatDate(time());

                        if (count($relatedCustomers)) {
                            $count = 0;
                            $data = [];
                            foreach ($relatedCustomers as $customer) {
                                $data[] = [
                                    'segment_id' => $segmentId,
                                    'customer_id' => $customer['entity_id'],
                                    'website_id' => $customer['website_id'],
                                    'added_date' => $now,
                                    'updated_date' => $now,
                                ];
                                $count++;
                                if ($count % 1000 == 0) {
                                    $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                                    $data = [];
                                }
                            }
                            if (!empty($data)) {
                                $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                            }
                        }
                    }
                }
            }
        }
        if (version_compare($context->getVersion(), '1.5.8') < 0) {

            $attributeId = $this->_eavAttribute->getIdByCode('customer_address', 'postcode');
            if ($attributeId) {
                $table = $setup->getTable('customer_eav_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("UPDATE  {$table} SET sort_order = 1  WHERE attribute_id = {$attributeId} ");
                }
            }
        }
        if (version_compare($context->getVersion(), '1.5.9', '<')) {
            $attributes = array(
                "KEY_ADDRESS2" => "Address2",
                "KEY_ADDRESS3" => "Address3",
                "KEY_ADDRESS4" => "Address4"
            );
            foreach ($attributes as $field => $label) {
                $customerSetup->removeAttribute(
                    'customer_address', $field
                );
            }
        }
        if (version_compare($context->getVersion(), '1.5.10', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $attributes = array(
                "MD0000" => "Dolche Gusto",
                "PM0000" => "Barista",
                "SPM0000" => "Barista i",
                "NM0000" => "Nespresso",
                "ST0000" => "Special-T",
                "OT0000" => "Other"
            );
            
            foreach ($attributes as $field => $label) {
                $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $field);
                $attribute->setData('used_in_forms', ['adminhtml_customer']);
                $attribute->setData('backend_model', 'Magento\Customer\Model\Attribute\Backend\Data\Boolean');

                $attribute->save();
            }

        }
        if (version_compare($context->getVersion(), '1.6.2', '<')) {
            $attributes = array(
                "telephone" => "Phone number"
            );
            foreach ($attributes as $field => $label) {
                $customerSetup->updateAttribute(
                    'customer_address', $field, [
                        'label' => $label,
                        'frontend_label' =>$label,
                        'backend_label' =>$label
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.6.3') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->removeAttribute(
                Customer::ENTITY, 'nhs_introducer'
                
            );
        }
  
        if (version_compare($context->getVersion(), '1.6.4', '<')) {
            $customerSetup->removeAttribute(
                Customer::ENTITY,
                'consumer_db_id'
            );
            $table = $setup->getTable('customer_entity');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'consumer_db_id',
                    ['type' => Table::TYPE_BIGINT, null, 'default' => null, 'comment' => 'Customer consumerDb Id']
                );
            }
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'consumer_db_id',
                [
                    'type' => 'static',
                    'label' => 'Customer ConsumerDb ID',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 900,
                    'visible' => false,
                    'system' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.6.5', '<')) {
            //machine
            $attributes = array(
                "MD0000" => "Dolche Gusto",
                "PM0000" => "Barista",
                "SPM0000" => "Barista i",
                "NM0000" => "Nespresso",
                "ST0000" => "Special-T",
                "OT0000" => "Other"
            );

            foreach ($attributes as $field => $label) {
                $customerSetup->updateAttribute(
                    Customer::ENTITY,
                    $field,
                    [
                        'attribute_model' => null
                    ]
                );
            }
            // END MACHINE
           
        }
        if (version_compare($context->getVersion(), '1.6.6', '<')) {
            
            $table = $setup->getTable('customer_entity');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->modifyColumn(
                    $table, 'consumer_db_id',
                    ['type' => Table::TYPE_TEXT, 'length' => '50', 'default' => null, 'comment' => 'Customer KSS consumerDb Id']
                );
            }
            $customerSetup->addAttribute(
                Customer::ENTITY, 'consumer_db_id',
                [
                    'type' => 'static',
                    'unique' => true,
                    'backend_type' => 'static',
                    'label' => 'Customer ConsumerDb ID',
                    'input' => 'text',
                    'required' => 0,
                    'sort_order' => 900,
                    'visible' => 0,
                    'system' => 0,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                ]
            );

            $query = "UPDATE customer_entity
                        INNER JOIN customer_entity_varchar ON customer_entity_varchar.entity_id = customer_entity.entity_id
                        SET customer_entity.consumer_db_id = customer_entity_varchar.value
                        WHERE  customer_entity_varchar.attribute_id = 328";
            $setup->getConnection()->query($query);
        }
        if (version_compare($context->getVersion(), '1.6.7', '<')) {  
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }

        if (version_compare($context->getVersion(), '1.6.8') < 0) {

            $data = [
                [
                    'Machine Rental Status Barista i',
                    'Machine No Barista i',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:7:"SPM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'508\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Barista',
                    'Machine No Barista',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"PM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'507\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Dolche Gusto',
                    'Machine No Dolche Gusto',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"MD0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'506\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Nespresso',
                    'Machine No Nespresso',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"NM0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'509\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Special-T',
                    'Machine No Special-T',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"ST0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'510\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine Rental Status Other',
                    'Machine No Other',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:6:"OT0000";s:8:"operator";s:2:"==";s:5:"value";s:1:"1";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'511\') AND (`main`.`value` = \'1\') LIMIT 1), 1, 0) = 1))'
                ],

            ];
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[1],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[2],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[3],
                    'apply_to' => 1
                ];
                $setup->getConnection()->update($setup->getTable('magento_customersegment_segment'), $bind, " name ='$row[0]' ");
            }
        }
        if (version_compare($context->getVersion(), '1.6.9', '<')) {
            $attributes = array(
                'LENDING_STATUS_NBA' => 'Machine rental status (Barista)',
                'LENDING_STATUS_NDG' => 'Machine rental status (Dolce gusto)',
                'LENDING_STATUS_SPT' => 'Machine rental status (Special T)',
                'LENDING_STATUS_ICS' => 'Machine rental status (BLC)',
                'LENDING_STATUS_NSP' => 'Machine Machine rental status (Nespresso)',
            );
            $sortOrder = 710;
            foreach ($attributes as $field => $label) {
                $customerSetup->addAttribute(
                    'customer', $field, [
                        'type' => 'int',
                        'input' => 'select',
                        'visible' => true,
                        'required' => false,
                        'system' => 0,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'label' => $label,
                        'source' => 'Riki\Customer\Model\StatusMachine',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                        'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
                        'is_used_for_customer_segment' => '1'
                    ]
                );
                $sortOrder += 10;
            }
            foreach ($attributes as $field => $label) {
                $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $field);
                $attribute->setData('used_in_forms', ['adminhtml_customer']);
                $attribute->setData('is_used_for_customer_segment', '1');

                $attribute->save();
            }
        }
        if (version_compare($context->getVersion(), '1.7.0') < 0) {

            $data = [
                [
                    'Machine rental status (Barista)',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:18:"LENDING_STATUS_NBA";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'514\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    ' Machine rental status (Dolce gusto)',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:18:"LENDING_STATUS_NDG";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'515\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine rental status (Special T)',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:18:"LENDING_STATUS_SPT";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'516\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine rental status (BLC)',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:18:"LENDING_STATUS_ICS";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'517\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Machine rental status (Nespresso)',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:18:"LENDING_STATUS_NSP";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \'518\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ]

            ];
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[0],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[1],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[2],
                    'apply_to' => 1
                ];
                $setup->getConnection()->insert($setup->getTable('magento_customersegment_segment'), $bind);

                $segmentId = $setup->getConnection()->lastInsertId($setup->getTable('magento_customersegment_segment'));

                $website = $this->_storeManager->getWebsites();
                $websiteIds = array();
                foreach ($website as $site) {
                    $websiteIds[] = $site->getId();
                    $bind = ['segment_id' => $segmentId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_customersegment_website'), $bind);
                }


                //match customer
                if (!empty($row[1])) {
                    $conditions = unserialize($row[1]);
                    if (is_array($conditions) && !empty($conditions)) {
                        $conditionObj = $this->_conditionFactory->create('Combine\Root');
                        $conditionObj->setRule($this)->setId('1')->setPrefix('conditions');
                        $conditionObj->loadArray($conditions);
                        $customerIds = $conditionObj->getSatisfiedIds($websiteIds);

                        $relatedCustomers = array();

                        foreach ($websiteIds as $websiteId) {

                            foreach ($customerIds as $customerId) {
                                $relatedCustomers[] = [
                                    'entity_id' => $customerId,
                                    'website_id' => $websiteId,
                                ];
                            }
                        }

                        $now = $this->dateTime->formatDate(time());

                        if (count($relatedCustomers)) {
                            $count = 0;
                            $data = [];
                            foreach ($relatedCustomers as $customer) {
                                $data[] = [
                                    'segment_id' => $segmentId,
                                    'customer_id' => $customer['entity_id'],
                                    'website_id' => $customer['website_id'],
                                    'added_date' => $now,
                                    'updated_date' => $now,
                                ];
                                $count++;
                                if ($count % 1000 == 0) {
                                    $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                                    $data = [];
                                }
                            }
                            if (!empty($data)) {
                                $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                            }
                        }
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '1.7.1', '<')) {
            $attributes = array(
                'legacy_promo_specialT' => 'Already got Special T capsule promotion',
                'legacy_promo_ndgbble' => 'Already got NDG Bubble promotion',
                'legacy_promo_ndggrass' => 'Already got NDG Latte Glass promotion'
            );
            $sortOrder = 860;
            foreach ($attributes as $field => $label) {
                $customerSetup->addAttribute(
                    'customer', $field,[
                        'type' => 'int',
                        'input' => 'select',
                        'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                        'visible' => true,
                        'required' => false,
                        'system' => 0,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'label' => $label,
                        'is_used_for_customer_segment' => 1,
                        'used_in_forms'=> ['adminhtml_customer']
                    ]
                );
                $customerSetup->updateAttribute(
                    'customer', $field,[
                        'is_used_for_customer_segment' => 1,
                        'used_in_forms'=> ['adminhtml_customer']
                    ]
                );
                $sortOrder+=10;
            }
        }

        if (version_compare($context->getVersion(), '1.7.2') < 0) {
            $attributeLegacyPromoSpecialT = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'legacy_promo_specialT')->getAttributeId();
            $attributeLegacyPromoNdgbble = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'legacy_promo_ndgbble')->getAttributeId();
            $attributeLegacyPromoNdggrass = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'legacy_promo_ndggrass')->getAttributeId();

            $data = [
                [
                    'Customers who got the Special T capsule promotion',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:21:"legacy_promo_specialT";s:8:"operator";s:2:"==";s:5:"value";s:1:"0";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \''.$attributeLegacyPromoSpecialT.'\') AND (`main`.`value` = \'0\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Customers who got the NDG Bubble promotion',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:20:"legacy_promo_ndgbble";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \''.$attributeLegacyPromoNdgbble.'\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ],
                [
                    'Customers who got NDG Latte Glass promotion',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:21:"legacy_promo_ndggrass";s:8:"operator";s:2:"==";s:5:"value";s:1:"2";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \''.$attributeLegacyPromoNdggrass.'\') AND (`main`.`value` = \'2\') LIMIT 1), 1, 0) = 1))'
                ]

            ];
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[0],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[1],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[2],
                    'apply_to' => 1
                ];
                $setup->getConnection()->insert($setup->getTable('magento_customersegment_segment'), $bind);

                $segmentId = $setup->getConnection()->lastInsertId($setup->getTable('magento_customersegment_segment'));

                $website = $this->_storeManager->getWebsites();
                $websiteIds = array();
                foreach ($website as $site) {
                    $websiteIds[] = $site->getId();
                    $bind = ['segment_id' => $segmentId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_customersegment_website'), $bind);
                }


                //match customer
                if (!empty($row[1])) {
                    $conditions = unserialize($row[1]);
                    if (is_array($conditions) && !empty($conditions)) {
                        $conditionObj = $this->_conditionFactory->create('Combine\Root');
                        $conditionObj->setRule($this)->setId('1')->setPrefix('conditions');
                        $conditionObj->loadArray($conditions);
                        $customerIds = $conditionObj->getSatisfiedIds($websiteIds);

                        $relatedCustomers = array();

                        foreach ($websiteIds as $websiteId) {

                            foreach ($customerIds as $customerId) {
                                $relatedCustomers[] = [
                                    'entity_id' => $customerId,
                                    'website_id' => $websiteId,
                                ];
                            }
                        }

                        $now = $this->dateTime->formatDate(time());

                        if (count($relatedCustomers)) {
                            $count = 0;
                            $data = [];
                            foreach ($relatedCustomers as $customer) {
                                $data[] = [
                                    'segment_id' => $segmentId,
                                    'customer_id' => $customer['entity_id'],
                                    'website_id' => $customer['website_id'],
                                    'added_date' => $now,
                                    'updated_date' => $now,
                                ];
                                $count++;
                                if ($count % 1000 == 0) {
                                    $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                                    $data = [];
                                }
                            }
                            if (!empty($data)) {
                                $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                            }
                        }
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '1.7.3', '<')) {
            $attributeName = 'cedyna_counter';
            $customerSetup->removeAttribute('customer', $attributeName);
        }

        if (version_compare($context->getVersion(), '1.7.4') < 0) {

            $attributeId = $this->_eavAttribute->getIdByCode('customer_address', 'postcode');
            if ($attributeId) {
                $table = $setup->getTable('customer_eav_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("UPDATE  {$table} SET sort_order = 40  WHERE attribute_id = {$attributeId} ");
                }
            }
        }

        if (version_compare($context->getVersion(), '1.7.5') < 0) {
            $customerSetup->updateAttribute('customer_address', 'postcode', 'sort_order', 43, 43);
            $customerSetup->updateAttribute('customer_address', 'country_id', 'sort_order', 44, 44);
            $customerSetup->updateAttribute('customer_address', 'region', 'sort_order', 45, 45);
            $customerSetup->updateAttribute('customer_address', 'city', 'sort_order', 46, 46);
            $customerSetup->updateAttribute('customer_address', 'street', 'sort_order', 47, 47);
            $customerSetup->updateAttribute('customer_address', 'apartment', 'sort_order', 48, 48);
        }

        if (version_compare($context->getVersion(), '1.7.8') < 0) {

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'membership',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }

        if (version_compare($context->getVersion(), '1.7.9') < 0) {

            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'membership',
                [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
            $indexer = $this->indexerFactory->create();
            $indexer->load('customer_grid');
            $indexer->reindexAll();
        }
        if (version_compare($context->getVersion(), '1.7.10') < 0) {
            /** @var CustomerSetup $customerSetup */
            //$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $attributeName = 'is_whitelisted';
            $customerSetup->updateAttribute('customer', $attributeName, [
                'default_value' => 0
            ]);
        }
        if (version_compare($context->getVersion(), '1.7.11', '<')) {
            $attributesOld = 'legacy_promo_specialT';
            $customerSetup->removeAttribute('customer',$attributesOld);
            $attributesNew = 'legacy_promo_specialt';
            
                $customerSetup->addAttribute(
                    'customer', $attributesNew,[
                        'type' => 'int',
                        'input' => 'select',
                        'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                        'visible' => true,
                        'required' => false,
                        'system' => 0,
                        'sort_order' => 860,
                        'position' => 860,
                        'label' => 'Already got Special T capsule promotion',
                        'is_used_for_customer_segment' => 1,
                        'used_in_forms'=> ['adminhtml_customer']
                    ]
                );
                $customerSetup->cleanCache();
                $customerSetup->updateAttribute(
                    'customer', $attributesNew,[
                        'is_used_for_customer_segment' => 1,
                        'used_in_forms'=> ['adminhtml_customer']
                    ]
                );
        }
        if (version_compare($context->getVersion(), '1.7.12') < 0) {
            $attributeLegacyPromoSpecialt = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'legacy_promo_specialt')->getAttributeId();
           
            $data = [
                [
                    'Customers who got the Special T capsule promotion',
                    'a:7:{s:4:"type";s:60:"Magento\CustomerSegment\Model\Segment\Condition\Combine\Root";s:9:"attribute";N;s:8:"operator";N;s:5:"value";s:1:"1";s:18:"is_value_processed";N;s:10:"aggregator";s:3:"all";s:10:"conditions";a:1:{i:0;a:5:{s:4:"type";s:67:"Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes";s:9:"attribute";s:21:"legacy_promo_specialt";s:8:"operator";s:2:"==";s:5:"value";s:1:"0";s:18:"is_value_processed";b:0;}}}',
                    'SELECT 1 FROM `' . $setup->getTable('customer_entity') . '` AS `root` WHERE ((IF((SELECT 1 FROM `' . $setup->getTable('customer_entity_int') . '` AS `main` WHERE (main.entity_id = :customer_id) AND (main.attribute_id = \''.$attributeLegacyPromoSpecialt.'\') AND (`main`.`value` = \'0\') LIMIT 1), 1, 0) = 1))'
                ]
            ];
            $setup->getConnection()->delete('magento_customersegment_segment','name = "Customers who got the Special T capsule promotion"');
            foreach ($data as $row) {
                $bind = [
                    'name' => $row[0],
                    'description' => '',
                    'is_active' => 1,
                    'conditions_serialized' => $row[1],
                    'processing_frequency' => 1,
                    'condition_sql' => $row[2],
                    'apply_to' => 1
                ];
                $setup->getConnection()->insert($setup->getTable('magento_customersegment_segment'), $bind);

                $segmentId = $setup->getConnection()->lastInsertId($setup->getTable('magento_customersegment_segment'));

                $website = $this->_storeManager->getWebsites();
                $websiteIds = array();
                foreach ($website as $site) {
                    $websiteIds[] = $site->getId();
                    $bind = ['segment_id' => $segmentId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_customersegment_website'), $bind);
                }


                //match customer
                if (!empty($row[1])) {
                    $conditions = unserialize($row[1]);
                    if (is_array($conditions) && !empty($conditions)) {
                        $conditionObj = $this->_conditionFactory->create('Combine\Root');
                        $conditionObj->setRule($this)->setId('1')->setPrefix('conditions');
                        $conditionObj->loadArray($conditions);
                        $customerIds = $conditionObj->getSatisfiedIds($websiteIds);

                        $relatedCustomers = array();

                        foreach ($websiteIds as $websiteId) {

                            foreach ($customerIds as $customerId) {
                                $relatedCustomers[] = [
                                    'entity_id' => $customerId,
                                    'website_id' => $websiteId,
                                ];
                            }
                        }

                        $now = $this->dateTime->formatDate(time());

                        if (count($relatedCustomers)) {
                            $count = 0;
                            $data = [];
                            foreach ($relatedCustomers as $customer) {
                                $data[] = [
                                    'segment_id' => $segmentId,
                                    'customer_id' => $customer['entity_id'],
                                    'website_id' => $customer['website_id'],
                                    'added_date' => $now,
                                    'updated_date' => $now,
                                ];
                                $count++;
                                if ($count % 1000 == 0) {
                                    $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                                    $data = [];
                                }
                            }
                            if (!empty($data)) {
                                $setup->getConnection()->insertMultiple($setup->getTable('magento_customersegment_customer'), $data);
                            }
                        }
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '1.7.13') < 0) {
            $customerSetup->updateAttribute('customer', 'lastname', 'sort_order', 40, 40);
            $customerSetup->updateAttribute('customer', 'lastnamekana', 'sort_order', 41, 41);
            $customerSetup->updateAttribute('customer', 'middlename', 'sort_order', 50, 50);
            $customerSetup->updateAttribute('customer', 'firstname', 'sort_order', 55, 55);
            $customerSetup->updateAttribute('customer', 'firstnamekana', 'sort_order', 56, 56);

            $customerSetup->updateAttribute('customer_address', 'lastname', 'sort_order', 20, 20);
            $customerSetup->updateAttribute('customer_address', 'lastnamekana', 'sort_order', 21, 21);
            $customerSetup->updateAttribute('customer_address', 'middlename', 'sort_order', 30, 30);
            $customerSetup->updateAttribute('customer_address', 'firstname', 'sort_order', 35, 35);
            $customerSetup->updateAttribute('customer_address', 'firstnamekana', 'sort_order', 36, 36);
        }

        if (version_compare($context->getVersion(), '1.7.14', '<')) {

            $attributes = array(
                "paygent_transaction_id" => "Paygent Transaction Id",
                "paygent_transaction_expire" => "Paygent Transaction Expire"
            );

            $sortOrder = 1100;
            foreach ($attributes as $field => $label) {
                $customerSetup->addAttribute(
                    Customer::ENTITY, $field, [
                        'type' => 'varchar',
                        'label' => $label,
                        'input' => 'text',
                        'default' => '',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => $sortOrder,
                        'position' => $sortOrder,
                        'system' => false,
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    ]
                );

                $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $field);
                $attribute->setData('used_in_forms', []);
                $attribute->save();

                $sortOrder+=1;
            }
        }
        if (version_compare($context->getVersion(), '1.8.0') < 0) {
            $aRemoveAttributeShosha = [
                'shosha_code', 'shosha_cmp', 'shosha_cmp_kana',
                'shosha_dept', 'shosha_dept_kana', 'shosha_in_charge',
                'shosha_in_charge_kana', 'shosha_phone', 'shosha_first_code',
                'shosha_second_code','shosha_comission','invoice_postode',
                'invoice_address1','invoice_address2','invoice_address1_kana',
                'invoice_address2_kana','block_orders','cedyna_counter'
            ];
            foreach ($aRemoveAttributeShosha as $attributeCode) {
                $customerSetup->removeAttribute('customer', $attributeCode);
            }
        }
        if (version_compare($context->getVersion(), '1.8.1') < 0) {
            $field = 'gender';
            $customerSetup->updateAttribute('customer',$field,[
               'default_value' => 3
            ]);
        }
        if (version_compare($context->getVersion(), '1.8.2', '<')) {
            $attribute = 'blacklisted_reason';

            $customerSetup->updateAttribute(
                'customer', $attribute,[
                    'input' => 'text',
                    'used_in_forms'=> ['adminhtml_customer'],
                    'type' => 'varchar',
                    'backend_type' => 'varchar'
                ]
            );
            $customerSetup->cleanCache();
        }


        if (version_compare($context->getVersion(), '1.8.3') < 0) {

            $this->_config->saveConfig('customer/address_templates/text',
                '{{depend prefix}}{{var prefix}} {{/depend}}
                {{if riki_nickname}}{{var riki_nickname}}{{/if}}
                 {{var firstname}} 
                {{depend middlename}}{{var middlename}} {{/depend}}
                {{var lastname}}{{depend suffix}} {{var suffix}}{{/depend}}
                {{depend company}}{{var company}}{{/depend}}
                {{if street1}}{{var street1}}
                {{/if}}
                {{depend street2}}{{var street2}}{{/depend}}
                {{depend street3}}{{var street3}}{{/depend}}
                {{depend street4}}{{var street4}}{{/depend}}
                {{if region}}{{var region}}, {{/if}}
                {{if postcode}}{{var postcode}}{{/if}}
                {{var country}}
                T: {{var telephone}}
                {{depend fax}}F: {{var fax}}{{/depend}}
                {{depend vat_id}}VAT: {{var vat_id}}{{/depend}}',
                'websites', 1);
            $this->_config->saveConfig('customer/address_templates/oneline',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/oneline',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'websites', 1);
            $this->_config->saveConfig('customer/address_templates/html',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/html',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}<br/>{{/depend}}
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'websites', 1);
            $this->_config->saveConfig('customer/address_templates/text',
                '{{depend prefix}}{{var prefix}} {{/depend}}
                    {{if riki_nickname}}{{var riki_nickname}}{{/if}}
                     {{var firstname}} 
                    {{depend middlename}}{{var middlename}} {{/depend}}
                    {{var lastname}}{{depend suffix}} {{var suffix}}{{/depend}}
                    {{depend company}}{{var company}}{{/depend}}
                    {{if street1}}{{var street1}}
                    {{/if}}
                    {{depend street2}}{{var street2}}{{/depend}}
                    {{depend street3}}{{var street3}}{{/depend}}
                    {{depend street4}}{{var street4}}{{/depend}}
                    {{if region}}{{var region}}, {{/if}}
                    {{if postcode}}{{var postcode}}{{/if}}
                    {{var country}}
                    T: {{var telephone}}
                    {{depend fax}}F: {{var fax}}{{/depend}}
                    {{depend vat_id}}VAT: {{var vat_id}}{{/depend}}',
                'websites', 1);

            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '1.8.4') < 0) {

            $this->_config->saveConfig('customer/address_templates/oneline',
                '    {{if riki_nickname}}{{var riki_nickname}}{{/if}}
                    {{depend prefix}}{{var prefix}}{{/depend}}
                    {{var firstname}}{{depend middlename}}{{var middlename}} {{/depend}}{{var lastname}}
                    {{depend suffix}}{{var suffix}}{{/depend}}, 
                     {{var street}},{{var region}} {{var postcode}}, {{var country}}',
                'websites', 1);


            $customerSetup->cleanCache();
        }
        if (version_compare($context->getVersion(), '1.8.5') < 0) {

            $this->_config->saveConfig('customer/address_templates/html',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}} {{var firstname}}{{/depend}}<br/>
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'default', 0);
            $this->_config->saveConfig('customer/address_templates/html',
                '    {{depend riki_nickname}}{{var riki_nickname}}<br/>{{/depend}}
                     {{var lastname}}{{depend firstname}}{{var firstname}}{{/depend}}<br/>
                     {{if postcode}}〒{{var postcode}}{{/if}}<br/>
                     {{if region}}{{var region}}, {{/if}}
                     {{if street1}}{{var street1}},{{/if}}
                    {{depend street2}}{{var street2}},{{/depend}}
                    {{depend street3}}{{var street3}},{{/depend}}
                    {{depend street4}}{{var street4}},{{/depend}}
                    {{depend company}}{{var company}},{{/depend}}
                    {{depend apartment}}{{var apartment}}<br/>{{/depend}}
                    {{depend telephone}}T: {{var telephone}}{{/depend}}',
                'websites', 1);

            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '1.8.6') < 0) {
            $customerSetup->addAttribute(
                'customer_address', 'consumer_db_address_id',[
                    'type' => 'static',
                    'backend_type' => 'static',
                    'label' => 'Customer Consumer Address ID',
                    'input' => 'text',
                    'required' => 0,
                    'visible' => 0,
                    'system' => 0,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.8.7') < 0) {
            $attributeName = 'shosha_cmp';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 107,
                'position' => 107,
                'label' => 'Company name'
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);

            $attribute->save();
            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '1.8.8') < 0) {
            $aRemoveAttributeShosha = ['shosha_cmp'];
            foreach ($aRemoveAttributeShosha as $attributeCode) {
                $customerSetup->removeAttribute('customer', $attributeCode);
            }
        }

        if (version_compare($context->getVersion(), '1.9.0') < 0) {

            $attributeName = 'customer_company_name';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 107,
                'position' => 107,
                'label' => 'Company name'
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);

            $attribute->save();
            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.1') < 0) {

            $attributeName = 'key_work_ph_num';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 109,
                'position' => 109,
                'label' => 'Company phone number'
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);

            $attribute->save();
            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.6') < 0) {

            $attributeName = 'COM_POSTAL_CODE';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 700,
                'position' => 700,
                'label' => 'Amb company zipcode'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'COM_ADDRESS1';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 700,
                'position' => 700,
                'label' => 'Amb company prefecture'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'COM_ADDRESS2';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 700,
                'position' => 700,
                'label' => 'Amb company address 2'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $attributeName = 'COM_ADDRESS3';
            $customerSetup->addAttribute('customer', $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'visible' => true,
                'required' => false,
                'system' => 0,
                'sort_order' => 700,
                'position' => 700,
                'label' => 'Amb company address 3'
            ]);
            //add attribute to form
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();

            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.8') < 0) {
            $customerSetup->addAttribute(Customer::ENTITY, 'consumer_data_hash', [
                'type' => 'static',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'system' => false,
                'label' => 'Consumer data hash'
            ]);
        }

        if (version_compare($context->getVersion(), '2.0.9') < 0) {
            $attributeName = 'line_id';
            $customerSetup->addAttribute(Customer::ENTITY, $attributeName, [
                'type' => 'varchar',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'system' => false,
                'label' => 'Line ID'
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->save();
            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.10') < 0) {
            $attributeName = 'reward_point';
            $customerSetup->addAttribute(Customer::ENTITY, $attributeName, [
                'type' => 'int',
                'visible' => false,
                'required' => false,
                'system' => 0,
                'label' => 'Current Balance Point',
                'is_used_for_customer_segment'=>'0'
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
            $attribute->save();
            $customerSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.11', '<')) {
            $customerSetup->addAttribute(
                Customer::ENTITY, 'LENDING_STATUS_DUO', [
                    'type' => 'int',
                    'input' => 'select',
                    'visible' => true,
                    'required' => false,
                    'system' => 0,
                    'sort_order' => 751,
                    'position' => 751,
                    'label' => 'Machine rental status (DUO)',
                    'source' => 'Riki\Customer\Model\StatusMachine',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                ]
            );
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'LENDING_STATUS_DUO');
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->setData('is_used_for_customer_segment', '1');
            $attribute->save();
        }

        $setup->endSetup();
    }
}
