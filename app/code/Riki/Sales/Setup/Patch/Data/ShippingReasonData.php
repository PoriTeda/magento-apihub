<?php

namespace Riki\Sales\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ShippingReasonData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * PatchInitial constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup->startSetup();

        $dataShippingReason = [
            [
                'description' => 'AMB保守パーツ'
            ],
            [
                'description' => '未着・不足'
            ],
            [
                'description' => '商品間違い'
            ],
            [
                'description' => 'お詫び'
            ],
            [
                'description' => '破損・汚損'
            ],
            [
                'description' => 'マシンメンテナンス保守'
            ]
        ];

        foreach ($dataShippingReason as $reason) {
            $setup->getConnection()->insert($setup->getTable('riki_shipping_reason'), $reason);
        }

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
