<?php
// @codingStandardsIgnoreFile
/**
 * ProductStockStatus Upgrade Schema
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ProductStockStatus\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
/**
 * Class UpgradeSchema
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var ModuleDataSetupInterface
     */
    private $eavSetup;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;
    /**
     * @var \Riki\ProductStockStatus\Model\UpdateProduct
     */
    protected $updateProduct;
    /**
     * @var WriterInterface
     */
    protected $configWriter;

    protected $storeConfig;
    /**
     * UpgradeSchema constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $eavSetup
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Riki\ProductStockStatus\Model\UpdateProduct $updateProduct,
        WriterInterface $writer,
        Context $context
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
        $this->eavAttribute = $eavAttribute;
        $this->updateProduct = $updateProduct;
        $this->configWriter = $writer;
        $this->storeConfig = $context->getScopeConfig();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.0.1', '<')) {
            //drop old table
            $dropTable = 'riki_product_status';
            $installer->getConnection()->dropTable(
                $installer->getTable($dropTable)
            );
            //create  new table if exist
            $tableName = 'riki_product_stock_status';
            if(!$setup->getConnection()->isTableExists($tableName)) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable($tableName))
                    ->addColumn(
                        'status_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Index Id'
                    )->addColumn(
                        'status_name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'Status Name'
                    )->addColumn(
                        'sufficient_message',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        ['nullable' => true],
                        'Sufficient message'
                    )->addColumn(
                        'short_message',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        ['nullable' => true],
                        'Short message'
                    )->addColumn(
                        'outstock_message',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        ['nullable' => true],
                        'Out of stock message'
                    )->addColumn(
                        'threshold_message',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        ['nullable' => true],
                        'Threshold Message'
                    );
                $installer->getConnection()->createTable($table);
            }
            //insert sample data
        }//end if

        if (version_compare($context->getVersion(), '0.0.3', '<')) {
            $tableName = $setup->getTable('riki_product_stock_status');
            //alter column
            $sql = "ALTER TABLE $tableName MODIFY COLUMN `status_id` INT auto_increment";
            $setup->run($sql);
            $setup->run("TRUNCATE TABLE $tableName");
            if($setup->getConnection()->tableColumnExists($tableName, 'threshold_message'))
            {
                $setup->getConnection()->dropColumn($tableName,'threshold_message');
            }
            $setup->getConnection()->addColumn(
                $tableName,
                'threshold',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 5,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Threshold Number'
                ]
            );

            $setup->getConnection()->addColumn(
                $tableName,
                'status_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Status Code'
                ]
            );


            $setup->run("TRUNCATE TABLE $tableName");
            //install data
            $data = [
                ['','10093','SPT遅延用201605','こちらの商品をご注文の場合は、同時にご注文いただいた商品とあわせて5月21日以降順次出荷となります。','こちらの商品をご注文の場合は、同時にご注文いただいた商品とあわせて5月21日以降順次出荷となります。','申し訳ございませんが、現在品切れ中です。　次回の入荷は未定です。','100'],
                ['','10043','次回入荷なし予約用','ご予約受付中。','残りわずかですので、ご予約はお早めに。','当製品は好評につき完売致しました。','100'],
                ['','10066','次回入荷案内05中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は5月中旬の予定です。','100'],
                ['','10065','次回入荷案内05初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は5月初旬の予定です。','100'],
                ['','10063','次回入荷案内04中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は4月中旬の予定です。','100'],
                ['','10064','次回入荷案内04下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は4月下旬の予定です。','100'],
                ['','10069','次回入荷案内06中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は6月中旬の予定です。','100'],
                ['','10070','次回入荷案内06下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は6月下旬の予定です。','100'],
                ['','10067','次回入荷案内05下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は5月下旬の予定です。','100'],
                ['','10068','次回入荷案内06初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は6月初旬の予定です。','100'],
                ['','10056','次回入荷案内02初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は2月初旬の予定です。','100'],
                ['','10057','次回入荷案内02中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は2月中旬の予定です。','100'],
                ['','10054','次回入荷案内01中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は1月中旬の予定です。','100'],
                ['','10055','次回入荷案内01下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は1月下旬の予定です。','100'],
                ['','10058','次回入荷案内02下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は2月下旬の予定です。','100'],
                ['','10061','次回入荷案内03下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は3月下旬の予定です。','100'],
                ['','10062','次回入荷案内04初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は4月初旬の予定です。','100'],
                ['','10059','次回入荷案内03初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は3月初旬の予定です。','100'],
                ['','10060','次回入荷案内03中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は3月中旬の予定です。','100'],
                ['','10033','次回入荷なし用(40管理用)','ご注文受付中。','残りわずかですので、ご注文はお早めに。','販売は終了いたしました。','40'],
                ['','10084','次回入荷案内11中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は11月中旬の予定です。','100'],
                ['','10085','次回入荷案内11下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は11月下旬の予定です。','100'],
                ['','10081','次回入荷案内10中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は10月中旬の予定です。','100'],
                ['','10083','次回入荷案内11初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は11月初旬の予定です。','100'],
                ['','10086','次回入荷案内12初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は12月初旬の予定です。','100'],
                ['','10089','次回入荷日指定用','ご注文受付中。','数量限定のためお早めにお買い求めください。','申し訳ございません、次回の入荷は2月12日の予定です。 数量限定のためお早めにお買い求めください。','100'],
                ['','10099','次回販売案内用5','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は6月17日の予定です。','100'],
                ['','10087','次回入荷案内12中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は12月中旬の予定です。','100'],
                ['','10088','次回入荷案内12下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は12月下旬の予定です。','100'],
                ['','10047','次回販売案内用3','ご注文受付中。','残りわずかですので、ご注文はお早めに。','3月11日よりリニューアル予定です。','100'],
                ['','10052','SPT玄米茶用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','10月24日（土）正午以降ご注文受付開始予定。入荷状況により注文受付開始が前後する場合がございます。','10'],
                ['','10035','在庫10管理用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','10'],
                ['','10045','在庫30管理用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','30'],
                ['','10071','次回入荷案内07初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は7月初旬の予定です。','100'],
                ['','10077','次回入荷案内09初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は9月初旬の予定です。','100'],
                ['','10080','次回入荷案内10初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は10月初旬の予定です。','100'],
                ['','10074','次回入荷案内08初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は8月初旬の予定です。','100'],
                ['','10076','次回入荷案内08下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は8月下旬の予定です。','100'],
                ['','10053','次回入荷案内01初','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は1月初旬の予定です。','100'],
                ['','10000','共通表示用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','100'],
                ['','10002','新香味焙煎用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','通常販売分の注文受付は終了。ご好評につき、受注生産にて予約販売予定。まもなく受付。','100'],
                ['','10013','ニュートリション','ご注文受付中。','ご注文受付中。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','1'],
                ['','10023','在庫20管理用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','20'],
                ['','10041','次回販売案内用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は3月2日の予定です。','100'],
                ['','10051','SPTほうじ茶用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','10月19日（月）正午以降ご注文受付開始予定。入荷状況により注文受付開始が前後する場合がございます。','10'],
                ['','10015','ピッコロ在庫切れ用','ご注文受付中。','ご注文受付中。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','100'],
                ['','10029','商品数量限定用（20管理用）','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。午前10：00から次回の販売を開始します。','20'],
                ['','10019','1日数量限定用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。毎朝9：00から販売を再開しております。','100'],
                ['','10020','次回入荷なし用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','販売は終了いたしました。','100'],
                ['','10025','在庫40管理用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','40'],
                ['','10027','次回入荷なし用(50管理用)','ご注文受付中。','残りわずかですので、ご注文はお早めに。','販売は終了いたしました。','50'],
                ['','10011','ネスカフェ ドルチェ グスト マシン・カプセル','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','100'],
                ['','10012','ビューティーバー','ご注文受付中。','ご注文受付中。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','1'],
                ['','10017','MD9740用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。','100'],
                ['','10010','輸入菓子（ワールドスイーツ）','ご注文受付中。','残りわずかですので、ご注文はお早めに。','ご注文を有難うございました。完売のため、注文の受付は終了いたしました。','200'],
                ['','10079','次回入荷案内09下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は9月下旬の予定です。','100'],
                ['','10082','次回入荷案内10下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は10月下旬の予定です。','100'],
                ['','10075','次回入荷案内08中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は8月中旬の予定です。','100'],
                ['','10078','次回入荷案内09中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は9月中旬の予定です。','100'],
                ['','10039','次回入荷なし用(10管理用)','ご注文受付中。','残りわずかですので、ご注文はお早めに。','販売は終了いたしました。','10'],
                ['','10021','在庫50管理用','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。　次回の入荷をお待ちください。','50'],
                ['','10096','次回販売案内用2','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は6月14日の予定です。','100'],
                ['','10031','次回入荷なし用(20管理用)','ご注文受付中。','残りわずかですので、ご注文はお早めに。','販売は終了いたしました。','20'],
                ['','10098','次回販売案内用4','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は6月16日の予定です。','100'],
                ['','10091','次回入荷未定','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在品切れ中です。　次回の入荷は未定です。','100'],
                ['','10095','次回販売案内用1','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は6月13日の予定です。','100'],
                ['','10097','次回販売案内用3','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ありませんが、現在売り切れ中です。次回の入荷は6月15日の予定です。','100'],
                ['','10072','次回入荷案内07中','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は7月中旬の予定です。','100'],
                ['','10073','次回入荷案内07下','ご注文受付中。','残りわずかですので、ご注文はお早めに。','申し訳ございませんが、現在売り切れ中です。次回の入荷は7月下旬の予定です。','100'],
                ['','10001','バリスタ予約用','ご注文受付中。10月中旬以降、順次発送致します。','残りわずかですので、ご注文はお早めに。10月中旬以降、順次発送致します。','今回の予約注文受付は終了。ご好評につき10月中旬以降に数量限定にて予約販売を予定しております。','100'],
                ['','10009','輸入菓子（ウォンカ・モッタ）','ご注文受付中。11月上旬より順次お届け致します。','残りわずかですので、ご注文はお早めに。11月上旬より順次お届け致します。','ご注文を有難うございました。完売のため、予約注文の受付は終了いたしました。','200'],
                ['','10007','ネスカフェ フラジール 受注生産用','ご注文受付中。11月中旬以降、順次お届け致します。','残りわずかですので、ご注文はお早めに。11月中旬以降、順次お届け致します。','ご注文を有難うございました。受注生産の注文受付は終了いたしました。','500'],
                ['','10008','ブライト','ご注文受付中。約10日でお届け致します。','残りわずかですので、ご注文はお早めに。約10日でお届け致します。','ご注文を有難うございました。完売いたしました。','50'],
                ['','10037','次回販売案内用（FP）','予約販売は終了いたしました。2月10日から通常販売いたします。','予約販売は終了いたしました。2月10日から通常販売いたします。','予約販売は終了いたしました。2月10日から通常販売いたします。','100'],
                ['','10049','次回入荷なし予告用','在庫売り切れ次第販売終了です。','在庫売り切れ次第販売終了です。','販売終了しました。','100'],

            ];
            foreach ($data as $row) {
                $bind = [
                    'status_code' => $row[1],
                    'status_name' => $row[2],
                    'sufficient_message' => $row[3],
                    'short_message' => $row[4],
                    'outstock_message' => $row[5],
                    'threshold' => $row[6]
                ];
                $setup->getConnection()->insert($tableName, $bind);
            }
        }
        //set default value for stock status
        if (version_compare($context->getVersion(), '0.0.4', '<')) {
            $field = 'stock_display_type';
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            //update attribute to be int
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $field,
                'backend_type',
                'int'
            );

            $this->updateProduct->setProductDefaultValue();
        }
        if (version_compare($context->getVersion(), '0.0.5', '<')) {
            $emailPath = 'catalog/productalert_cron/error_email';
            $generalPath = 'trans_email/ident_general/email';
            $genralEmail = $this->storeConfig->getValue('trans_email/ident_general/email');
            $this->configWriter->save($emailPath,$genralEmail);
        }
        $installer->endSetup();
    }
}