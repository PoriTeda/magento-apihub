<?php
// @codingStandardsIgnoreFile
namespace Bluecom\Paygent\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Email\Model\TemplateFactory as TemplateFactory;
use Magento\Email\Model\ResourceModel\Template\Collection as TemplateCollection;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var TemplateFactory
     */
    protected $templateEmail;
    /**
     * @var TemplateCollection
     */
    protected $templateCollection;
    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $connectionHelper;

    /**
     * UpgradeData constructor.
     * @param TemplateFactory $template
     * @param TemplateCollection $collection
     * @param \Riki\ArReconciliation\Setup\SetupHelper $connectionHelper
     */
    public function __construct(
        TemplateFactory $template,
        TemplateCollection $collection,
        \Riki\ArReconciliation\Setup\SetupHelper $connectionHelper
    ) {
        $this->templateEmail = $template;
        $this->templateCollection = $collection;
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            $this->upgradeToVersion208($setup);
        }

        if (version_compare($context->getVersion(), '2.2.0', '<')) {
            $this->upgradeToVersion220($setup);
        }

        $setup->endSetup();
    }
    
    private function upgradeToVersion208($setup)
    {

        /**
         * install data default
         */
        $data = [
            [
                'error_code' =>'1G12',
                'backend_message' => '1G12:カード使用不可 (お客様事由・カード会社事由による)',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。(1G12)　',
            ],
            [
                'error_code' => '1G30',
                'backend_message' => '1G30:取引判定保留エラー(カード会社が取引内容、金額を総合的に判断し、保留された場合・有人判定)',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。(1G30)',
            ],

            [
                'error_code' => '1G54',
                'backend_message' => '1G54: 1日利用回数金額オーバー',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。',
            ],
            [
                'error_code' => '1G55',
                'backend_message' => '1G55: 1日利用限度額オーバー',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。',
            ],

            [
                'error_code' => '1G56',
                'backend_message' => '1G56:取引エラー(事故、盗難、無効カードなどが取引に使用された場合)',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。(1G56)',
            ],
            [
                'error_code' => '1G61',
                'backend_message' => '1G61:無効カード',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。(1G61)',
            ],
            [
                'error_code' => '1G65',
                'backend_message' => '1G65: カード番号エラー',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。理由：カード番号エラーです。(1G65)',
            ],
            [
                'error_code' => '1G71',
                'backend_message' => '',
                'email_message' => '',
            ],
            [
                'error_code' => '1G74',
                'backend_message' => '',
                'email_message' => '',
            ],

            [
                'error_code' => '1G78',
                'backend_message' => '',
                'email_message' => '',
            ],

            [
                'error_code' => '1G83',
                'backend_message' => '1G97:当該要求拒否エラー(オーソリ処理の要求が拒否され、カードが取扱不能な場合)',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。(1G97)',
            ],
            [
                'error_code' => '1P65',
                'backend_message' => '1P65: カード番号入力エラー',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。理由：カード番号エラーです。(1P65)',
            ],

            [
                'error_code' => '1P71',
                'backend_message' => '',
                'email_message' => '',
            ],

            [
                'error_code' => '1P74',
                'backend_message' => '',
                'email_message' => '',
            ],
            [
                'error_code' => '1P78',
                'backend_message' => '',
                'email_message' => '',
            ],
            [
                'error_code' => '1P83',
                'backend_message' => '1P83: カード有効期限エラー',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。理由：カード有効期限エラーです。(1P83)',
            ],
            [
                'error_code' => 'Others',
                'backend_message' => 'ペイジェントに問い合わせが必要です。',
                'email_message' => '下記のご注文につきまして、ご入力されましたお客様のクレジットカードでのお取引を 完了することができませんでした。',
            ],

        ];
        $setup->getConnection()->insertMultiple($setup->getTable('riki_paygent_error_handling'), $data);
    }

    public function upgradeToVersion220($setup)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $salesConnection */
        $salesConnection = $this->connectionHelper->getSalesConnection();

        $orderTable = $salesConnection->getTableName('sales_order');

        $bind = [ 'ivr_transaction' => 'canceled' ];

        $condition = "ivr_transaction IS NOT NULL and ivr_transaction not in ('canceled', 'error')";

        $salesConnection->update($orderTable, $bind, $condition);

    }
}
