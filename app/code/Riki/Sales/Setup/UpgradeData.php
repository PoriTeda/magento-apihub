<?php
// @codingStandardsIgnoreFile
namespace Riki\Sales\Setup;;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $_config;

    /**
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_salesConnection;

    public function __construct(
        \Magento\Config\Model\ResourceModel\Config  $config,
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    )
    {
        $this->_config = $config ;
        $this->_salesConnection = $orderResource->getConnection();
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

        if (version_compare($context->getVersion(), '1.1.6') < 0) {

            $reasons = [
                '商品戻り有り（配送中破損＜倉庫破損・配送業者破損＞',
                '商品戻り有り（受取後、ダメージ発見）',
                '商品戻り有り（倉庫起因　ピックミス・テレコ納品）',
                '商品戻り有り（賞味期限クレーム）',
                '商品戻り有り（製品不良）',
                '商品戻り有り（コールセンター起因）',
                '商品戻り有り（その他）',
                '商品戻り無し（受取後、ダメージ発見）',
                '商品戻り無し（時間指定不履行等その他配送／倉庫起因クレーム）',
                '商品戻り無し（プレゼント添付漏れ）',
                '商品戻り無し（賞味期限クレーム）',
                '商品戻り無し（製品不良）',
                '商品戻り無し（コールセンター起因）',
                '商品戻り無し（キャンペーンプレゼント）',
                '商品戻り無し（その他）',
                'マシン部品不足',
                'マシン部品不良',
                'マシンメンテナンス交換',
            ];

            $data = [];
            foreach($reasons as $index  =>  $reason){
                $data[time() . '_' . rand(100, 999)] = [
                      'code'    =>  $index + 1,
                      'title'   =>  $reason
                ];
            }

            $this->_config->saveConfig('riki_order/replacement_order/reason',
                serialize($data),
                'default',0 );
        }

        if (version_compare($context->getVersion(), '1.9.0') < 0) {
            $reasons = [
                '注文間違い(Wrong order)',
                '不要になった（No need）',
                '定期便の内容変更ができなかった(Not change a subscription profile)',
                'ネスレ通販起因 (Due to  EC site)',
            ];

            $data = [];
            foreach($reasons as $index  =>  $reason){
                $data[time() . '_' . rand(100, 999)] = [
                    'title'   =>  $reason
                ];
            }

            $this->_config->saveConfig('riki_order/cancellation/reason',
                serialize($data),
                'default',0 );
        }

        if (version_compare($context->getVersion(), '1.9.1') < 0) {
            $reasons = [
                '注文間違い(Wrong order)',
                '不要になった（No need）',
                '定期便の内容変更ができなかった(Not change a subscription profile)',
                'ネスレ通販起因 (Due to  EC site)',
            ];

            $data = [];
            foreach($reasons as $index  =>  $reason){
                $data[time() . '_' . rand(100, 999)] = [
                    'code'    =>  $index + 1,
                    'title'   =>  $reason
                ];
            }

            $this->_config->saveConfig('riki_order/cancellation/reason',
                serialize($data),
                'default',0 );
        }

        /* UPDATE sales_order_grid AS grid,
            (SELECT
            order_id, is_preorder
            FROM
            riki_preorder_order_preorder
            WHERE
            is_preorder != 0
            LIMIT 1 , 500000) AS pre
            SET
            grid.is_preorder = pre.is_preorder
            WHERE
            grid.entity_id = pre.order_id;
         */

        $setup->endSetup();
    }
}
