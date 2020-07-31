<?php

namespace Nestle\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;

/**
 * Class DisplayCustomerAttributesRegistrationPage
 *
 * @package Nestle\Migration\Setup\Patch\Data
 */
class DisplayCustomerAttributesRegistrationPage implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * CustomerSetupFactory
     *
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * EnableDisplayCustomerAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * Do set data for 2 attributes (Membership and Associate to multiple Website).
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        try {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

            //Update config used_in_forms => Customer Account Edit of 2 attributes (Membership and Associate to multiple Website).
            $entityAttributes = [
                'customer' => [
                    'multiple_website' => [
                        'used_in_forms' => ['customer_account_create', 'customer_account_edit']
                    ],
                    'membership' => [
                        'used_in_forms' => ['customer_account_create', 'customer_account_edit']
                    ],
                    'isblacklisted' => [
                        'used_in_forms' => ['customer_account_create', 'customer_account_edit']
                    ],
                    'blacklisted_reason' => [
                        'used_in_forms' => ['customer_account_create', 'customer_account_edit']
                    ],
                    'preferred_payment_method' => [
                        'used_in_forms' => ['customer_account_create', 'customer_account_edit']
                    ],

                ],
            ];
            $customerSetup->upgradeAttributes($entityAttributes);
        } catch (\Exception $exception) {
            throw  $exception;
        }
    }

    /**
     * Get aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Get dependencies.
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }
}
