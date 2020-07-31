<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Sales\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class ShippingCauseData implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */

    /**
     * ShippingCauseData constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
    ) {
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply()
    {
        $setup =$this->moduleDataSetup->startSetup();
        $dataShippingCause = [
            [
                'description' => '保守パーツ提供（AMBのみ'
            ],
            [
                'description' => '誤案内・注文ミス（コールセンター）'
            ],
            [
                'description' => '誤案内・注文ミス（体験型販売）'
            ],
            [
                'description' => '誤案内・注文ミス（AA/FSS）'
            ],
            [
                'description' => '誤案内・注文ミス（その他販売窓口）'
            ],
            [
                'description' => '配送業者持ち戻り'
            ],
            [
                'description' => '配送業者の対応・取り扱いが悪い'
            ],
            [
                'description' => '倉庫・配送上の破損・汚損'
            ],
            [
                'description' => '在庫欠品'
            ],
            [
                'description' => '倉庫・配送業者作業遅れ'
            ],
            [
                'description' => '倉庫ピッキングミス'
            ],
            [
                'description' => '原因不明の未着'
            ],
            [
                'description' => '賞味期限がルールに基づいていない'
            ],
            [
                'description' => 'キャンペーン運用ミス'
            ],
            [
                'description' => '無償出荷前提のキャンペーン運用'
            ],
            [
                'description' => 'プレゼント商品の未着'
            ],
            [
                'description' => 'システム原因'
            ],
            [
                'description' => '返金遅延'
            ],
            [
                'description' => 'LOHACO連携エラー・欠品'
            ],
            [
                'description' => 'HOLD定期便'
            ],
            [
                'description' => 'プレゼント商品一括アップロード'
            ],
            [
                'description' => '顧客の注文ミス（商品違い）'
            ],
            [
                'description' => '顧客の注文ミス（レンタルマシン申し込み漏れ）'
            ],
            [
                'description' => '賞味期限に不承'
            ],
            [
                'description' => 'わがまま・言いがかり'
            ],
            [
                'description' => '複合要因'
            ],
        ];

        foreach ($dataShippingCause as $cause) {
            $setup->getConnection()->insert($setup->getTable('riki_shipping_cause'), $cause);
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
        /**
         * This internal Magento method, that means that some patches with time can change their names,
         * but changing name should not affect installation process, that's why if we will change name of the patch
         * we will add alias here
         */
        return [];
    }
}