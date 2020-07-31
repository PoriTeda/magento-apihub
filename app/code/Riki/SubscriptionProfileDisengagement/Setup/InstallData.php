<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionProfileDisengagement\Setup;

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
        if (!$setup->tableExists($setup->getTable('subscription_disengagement_reason'))) {
            return;
        }

        $reasons = [
            [
                1,
                '消費しきれない(季節要因）'
            ],
            [
                2,
                '定期便乗換え（NBA/NDG/SPT)'
            ],
            [
                3,
                'AMBへ乗換え'
            ],
            [
                4,
                'WEBから再受注のため'
            ],
            [
                5,
                '定期便を1本にまとめるため'
            ],
            [
                6,
                '近くの小売店で購入をする'
            ],
            [
                7,
                'コミット期間の完了が理由'
            ],
            [
                8,
                '商品価格が高いため'
            ],
            [
                9,
                '配送料/手数料が高いため'
            ],
            [
                10,
                '家族構成の変化・好みの変化から飲む機会が減った、飲まなくなった'
            ],
            [
                11,
                '体調が悪くなり、飲めない'
            ],
            [
                12,
                '味が好みに合わない'
            ],
            [
                13,
                'マシンが不具合/壊れた/使いづらい'
            ],
            [
                14,
                '定期便の運用ルールが面倒'
            ],
            [
                15,
                '注文の間違いがあり、取り消しを行う'
            ],
            [
                16,
                '他社のマシン・システムを使用するため'
            ],
            [
                17,
                '理由をおっしゃっていただけない'
            ],
            [
                18,
                'その他'
            ],
            [
                19,
                '集金面倒/掃除が面倒（AMB)'
            ],
            [
               20,
                '施設環境理由(AMB)'
            ],
            [
                21,
                '購入方法(AMB)'
            ],
            [
                22,
                '退職/転勤/異動(AMB)'
            ],
            [
                23,
                '事務所閉鎖(AMB)'
            ],
            [
                24,
                'AA訪問関連(AMB)'
            ],
            [
                25,
                '購入量が多かった(AMB)'
            ],
        ];

        $setup->getConnection()->insertArray(
            $setup->getTable('subscription_disengagement_reason'),
            ['code', 'title'], $reasons
        );
    }
}
