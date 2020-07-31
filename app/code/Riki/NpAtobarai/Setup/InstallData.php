<?php

namespace Riki\NpAtobarai\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Bluecom\PaymentFee\Model\PaymentFeeFactory;
use Bluecom\PaymentFee\Model\PaymentFee;
use Psr\Log\LoggerInterface;
use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use Riki\NpAtobarai\Model\Payment\NpAtobarai as PaymentNpAtobarai;
use Magento\OfflinePayments\Model\Banktransfer;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var PaymentFeeFactory
     */
    protected $paymentFeeFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Custom Processing Order-Status code
     */
    const ORDER_STATUS_NP_ATOBARAI_CODE = 'pending_np';
    /**
     * Custom Processing Order-Status label
     */
    const ORDER_STATUS_NP_ATOBARAI_LABEL = 'PENDING_NP';

    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    protected $statusResourceFactory;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * InstallData constructor.
     * @param LoggerInterface $logger
     * @param PaymentFeeFactory $paymentFeeFactory
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Rma\Helper\Refund $refundHelper
     */
    public function __construct(
        LoggerInterface $logger,
        PaymentFeeFactory $paymentFeeFactory,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Rma\Helper\Refund $refundHelper,
        SerializerInterface $serializer
    ) {
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->logger = $logger;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
        $this->configValueFactory = $configValueFactory;
        $this->refundHelper = $refundHelper;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $this->addPaymentFeeForNpAtobarai();
        $this->addNewOrderStatusForNpAtobarai();
        $this->addPendingReasonMessages();
        $this->addRefundMethodForNpAtobarai();

        $setup->endSetup();
    }

    /**
     * Add payment fee for Np Atobarai
     */
    private function addPaymentFeeForNpAtobarai()
    {
        try {
            /** @var PaymentFee $newPayment */
            $newPayment = $this->paymentFeeFactory->create();
            $data = [
                'payment_code' => 'npatobarai',
                'payment_name' => 'NpAtobarai',
                'fixed_amount' => 330,
                'active' => 1
            ];
            $newPayment->setData($data);
            $newPayment->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    protected function addNewOrderStatusForNpAtobarai()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => self::ORDER_STATUS_NP_ATOBARAI_CODE,
            'label' => self::ORDER_STATUS_NP_ATOBARAI_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        $status->assignState(Order::STATE_NEW, false, true);
    }

    /**
     * Add pending reason messages
     */
    private function addPendingReasonMessages()
    {
        try {
            $this->configValueFactory->create()->load(
                \Riki\NpAtobarai\Model\Transaction\Config::XML_PATH_NP_ATOBARAI_PENDING_REASON,
                'path'
            )->setValue(
                $this->serializer->serialize($this->getDataTransactionPendingReasonMessage())
            )->setPath(
                \Riki\NpAtobarai\Model\Transaction\Config::XML_PATH_NP_ATOBARAI_PENDING_REASON
            )->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @return array
     */
    private function getDataTransactionPendingReasonMessage()
    {
        $values = [];
        $reasonMessages = $this->getReasonMessages();
        foreach ($reasonMessages as $reason) {
            $uniqid = uniqid();
            $id = '_' . $uniqid . '_' . substr($uniqid, -3);
            $values[$id] = $reason;
        }
        return $values;
    }

    /**
     * @return array
     */
    private function getReasonMessages()
    {
        return [
            [
                'code' => 'RE001',
                'title' => 'ご登録いただいた購入者様のお名前は、記載ルールに満たない、またはご本人様のお名前かその他名称かの判断が付かない為、ご確認をお願い致します。（1.お名前は必ずフルネームで登録ください。2.日本人のお名前の場合は、漢字表記でお願いいたします。3.会社名や店舗名をご記載の場合は、苗字のみでの表記も可能です。）'
            ],
            [
                'code' => 'RE002',
                'title' => 'ご登録いただいた購入者様のお名前は、ご本人様のお名前かその他名称かの判断が付かない為、ご確認をお願い致します。（「外国姓」「ハンドルネーム」「会社名（屋号）」など特定ができません。）'
            ],
            [
                'code' => 'RE003',
                'title' => 'ご登録いただいた購入者様のお名前は、機種依存文字（又は文字コード）等が含まれた部分が文字化けしている可能性がある為、ご確認をお願い致します。（機種依存文字（又は文字コード）等が含まれた文字は「？（クエスチョン）」、又は「－（ハイフンでない特殊記号）」と表示されることがあります。）'
            ],
            [
                'code' => 'RE004',
                'title' => 'お客様（購入者）が会社や店舗の場合、ご担当者様のお名前を含めてご登録いただく必要があります。ご担当者様の氏名（フルネーム、漢字表記）のご確認をお願い致します。'
            ],
            [
                'code' => 'RE005',
                'title' => 'ご登録いただいた配送先のお名前は、記載ルールに満たない、またはご本人様のお名前かその他名称かの判断が付かない為、ご確認をお願い致します。（1.お名前は必ずフルネームで登録ください。2.日本人のお名前の場合は、漢字表記でお願いいたします。3.会社名や店舗名をご記載の場合は、苗字のみでの表記も可能です。）'
            ],
            [
                'code' => 'RE006',
                'title' => 'ご登録いただいた配送先のお名前は、ご本人様のお名前かその他名称かの判断が付かない為、ご確認をお願い致します。（「外国姓」「ハンドルネーム」「会社名（屋号）」など特定ができません。）'
            ],
            [
                'code' => 'RE007',
                'title' => 'ご登録いただいた配送先のお名前は、機種依存文字（又は文字コード）等が含まれた部分が文字化けしている可能性がある為、ご確認をお願い致します。（機種依存文字（又は文字コード）等が含まれた文字は「？（クエスチョン）」、又は「－（ハイフンでない特殊記号）」と表示されることがあります。）'
            ],[
                'code' => 'RE008',
                'title' => 'お客様（配送先）が会社や店舗の場合、ご担当者様のお名前を含めてご登録いただく必要があります。ご担当者様の氏名（フルネーム、漢字表記）のご確認をお願い致します。'
            ],[
                'code' => 'RE009',
                'title' => 'ご登録いただいたご住所は、住所情報が不足している可能性がある為、ご確認をお願い致します。（1.建物名や部屋番号がある場合はご登録ください。2.会社名や店舗名は「会社名欄」にご登録ください。）'
            ],
            [
                'code' => 'RE010',
                'title' => 'ご登録いただいたご住所は、住所情報の重複登録、又は判別の難しい記載がある為、ご確認をお願い致します。（建物名や部屋番号がある場合はご登録ください。）'
            ],
            [
                'code' => 'RE011',
                'title' => 'ご登録いただいたご住所は、住所記載としては問題ございませんが、「建物名」「部屋番号」等の詳しい情報が記載されていない可能性がある為、ご確認をお願い致します。（建物名・部屋番号が抜けておりますと請求書が不着となる可能性がございます。）'
            ],
            [
                'code' => 'RE012',
                'title' => 'ご登録いただいたご住所は、住所記載としては問題ございませんが、「会社名」「店舗名」等の詳しい情報が記載されていない可能性がある為、ご確認をお願い致します。（会社名・店舗名が抜けておりますと請求書が不着となる可能性がございます。）'
            ],
            [
                'code' => 'RE013',
                'title' => 'ご登録いただいたご住所は、機種依存文字（又は文字コード）等が含まれた部分が文字化けしている可能性がある為、ご確認をお願い致します。（機種依存文字（又は文字コード）等が含まれた文字は「？（クエスチョン）」、又は「－（ハイフンでない特殊記号）」と表示されることがあります。）'
            ],
            [
                'code' => 'RE014',
                'title' => 'ご登録いただいたご住所のお客様の在籍状況をご確認ください。職員であることが確認できましたら、住所欄の末尾または、部署名欄に「職員」「経営者」「従業員」等を追加ください。'
            ],
            [
                'code' => 'RE015',
                'title' => 'ご登録いただいた配送先のご住所は、住所情報が不足している可能性がある為、ご確認をお願い致します。（1.建物名や部屋番号がある場合はご登録ください。2.会社名や店舗名は「会社名欄」にご登録ください。）'
            ],
            [
                'code' => 'RE016',
                'title' => 'ご登録いただいた配送先のご住所は、住所情報の重複登録、又は判別の難しい記載がある為、ご確認をお願い致します。（建物名や部屋番号がある場合はご登録ください。）'
            ],
            [
                'code' => 'RE017',
                'title' => 'ご登録いただいた配送先のご住所は、住所記載としては問題ございませんが、「建物名」「部屋番号」等の詳しい情報が記載されていない可能性がある為、ご確認をお願い致します。（建物名・部屋番号が抜けておりますと請求書が不着となる可能性がございます。）'
            ],
            [
                'code' => 'RE018',
                'title' => 'ご登録いただいた配送先のご住所は、住所記載としては問題ございませんが、「会社名」「店舗名」等の詳しい情報が記載されていない可能性がある為、ご確認をお願い致します。（会社名・店舗名が抜けておりますと請求書が不着となる可能性がございます。）'
            ],
            [
                'code' => 'RE019',
                'title' => 'ご登録いただいた配送先のご住所は、機種依存文字（又は文字コード）等が含まれた部分が文字化けしている可能性がある為、ご確認をお願い致します。（機種依存文字（又は文字コード）等が含まれた文字は「？（クエスチョン）」、又は「－（ハイフンでない特殊記号）」と表示されることがあります。）'
            ],
            [
                'code' => 'RE020',
                'title' => 'ご登録いただいた配送先のご住所のお客様の在籍状況をご確認ください。職員であることが確認できましたら、住所欄の末尾または、部署名欄に「職員」「経営者」「従業員」等を追加ください。'
            ],
            [
                'code' => 'RE021',
                'title' => 'ご登録いただいたお客様の電話番号は、弊社にて確認したところエラーとなっておりました。お客様に正しい電話番号をご確認ください。'
            ],
            [
                'code' => 'RE022',
                'title' => 'お客様の固定電話番号をご確認していただく必要がある為、ご確認をお願い致します。'
            ],
            [
                'code' => 'RE023',
                'title' => 'ご登録いただいた配送先の電話番号は、弊社にて確認したところエラーとなっておりました。お客様に正しい電話番号をご確認ください。'
            ],
            [
                'code' => 'RE024',
                'title' => 'インターネットからご注文の場合、購入者様のメールアドレスは必須である為、ご確認をお願い致します。（楽天サイト・カタログ等でメールアドレスが取得できない取引につきましては、メールアドレスの欄は空欄にてご登録可能となっておりますが、ご利用前に弊社への申請をお願いしております。）'
            ],
            [
                'code' => 'RE025',
                'title' => 'ご登録いただいた購入者情報に加盟店様のメールアドレスまたは不正なメールアドレスが含まれている可能性があると判断いたしました。加盟店様のメールアドレスを登録することは禁止させていただいている為、ご確認をお願い致します。'
            ],
            [
                'code' => 'RE026',
                'title' => '登録住所が私書箱宛、御社従業員（自社取引）、サイト審査前、送料・手数料のみの請求、禁止商材（デジタルコンテンツ・動物・チケット・受講料等、弊社での加盟審査完了後に新たにお取扱を始めた場合を含みます。）、テスト登録の注文はご利用いただけない為、これらに該当する場合は取引をキャンセルしてください。'
            ],
            [
                'code' => 'RE027',
                'title' => '以前弊社にご登録いただいた取引の中に、一部同様の情報を含むものが存在する為、ご確認をお願い致します。'
            ],
            [
                'code' => 'RE028',
                'title' => '購入者様とお電話がつながらず（不在・留守等）、ご本人確認が取れなかった為、日中、連絡のとれる電話番号のご確認をお願い致します。'
            ],
            [
                'code' => 'RE029',
                'title' => 'より詳細な審査を行うためにお時間をいただく取引です。与信審査結果は翌営業日18:00までにご連絡いたしますので、商品の発送をお待ちくださいますよう、お願い致します。尚、商品の発送をお急ぎの場合は、購入者様へ別の決済方法をご案内いただき、本取引はキャンセルしてください。'
            ],
            [
                'code' => 'RE031',
                'title' => 'ご登録いただいた電話番号は加盟店様のもの、または関連会社様のものである可能性があると判断しました。加盟店様の電話番号を登録することは禁止させていただいているため、ご確認をお願い致します。'
            ]

        ];
    }

    /**
     * Add refund method for Np Atobarai
     */
    private function addRefundMethodForNpAtobarai()
    {
        // Add payment npatobarai to list enable payments.
        $enablePayments = $this->scopeConfig->getValue(
            \Riki\Rma\Api\ConfigInterface::RMA_REFUND_METHOD_ENABLE_PAYMENT
        );
        $enablePayments = explode(',', $enablePayments);
        if (!in_array(PaymentNpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE, $enablePayments)) {
            array_push($enablePayments, PaymentNpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE);

            try {
                $this->configValueFactory->create()->load(
                    \Riki\Rma\Api\ConfigInterface::RMA_REFUND_METHOD_ENABLE_PAYMENT,
                    'path'
                )->setValue(
                    implode(',', $enablePayments)
                )->setPath(
                    \Riki\Rma\Api\ConfigInterface::RMA_REFUND_METHOD_ENABLE_PAYMENT
                )->save();
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }

        // Add default value [online_member_default|offline_member_default|alternative] for Payment Np Atobarai
        $refundMethods = $this->refundHelper->getEnableRefundMethods();
        if (array_key_exists(Checkmo::PAYMENT_METHOD_CHECKMO_CODE, $refundMethods)) {
            $this->addOfflineMemberDefault();
            $this->addOnlineMemberDefault();
        }

        if (array_key_exists(Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE, $refundMethods)) {
            $this->addAlternativeDefault();
        }
    }

    /**
     * Add offline member default
     */
    private function addOfflineMemberDefault()
    {
        $defaultPath = 'rma/' . PaymentNpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE . '/offline_member_default';
        try {
            $this->configValueFactory->create()->load(
                $defaultPath,
                'path'
            )->setValue(
                Checkmo::PAYMENT_METHOD_CHECKMO_CODE
            )->setPath(
                $defaultPath
            )->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Add online member default
     */
    private function addOnlineMemberDefault()
    {
        $defaultPath = 'rma/' . PaymentNpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE . '/online_member_default';
        try {
            $this->configValueFactory->create()->load(
                $defaultPath,
                'path'
            )->setValue(
                Checkmo::PAYMENT_METHOD_CHECKMO_CODE
            )->setPath(
                $defaultPath
            )->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Add alternative member default
     */
    private function addAlternativeDefault()
    {
        $defaultPath = 'rma/' . PaymentNpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE . '/alternative';
        try {
            $this->configValueFactory->create()->load(
                $defaultPath,
                'path'
            )->setValue(
                Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
            )->setPath(
                $defaultPath
            )->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
