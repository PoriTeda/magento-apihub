<?php
// @codingStandardsIgnoreFile
namespace Riki\Cms\Setup;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData.
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    protected $configWriter;

    /**
     * InstallData constructor.
     *
     * @param PageFactory  $pageFactory
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->pageFactory = $pageFactory;
        $this->blockFactory = $blockFactory;
        $this->configWriter = $configWriter;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $staticBlocks = [
            'campaign_details' => [
                'identifier' => 'campaign_details',
                'is_active' => 1,
                'title' => 'Campaign Details',
                'store_id' => 0,
                'content' => <<<'CONTENT'
                   <p>Rich text field to display campaign details</p>
CONTENT
            ],
            'header_block' => [
                'identifier' => 'riki-header-block',
                'is_active' => 1,
                'title' => 'Header',
                'store_id' => 0,
                'content' => <<<'CONTENT'
                    <div class="cwcn">
<p><img src="{{view url="images/header/header_cwcn.jpg"}}" alt="あなたのほっとするひと時と笑顔のために CHOOSE WELLNESS CHOOSE NESTLE" /></p>
</div>
<div class="navigation">
<ul>
<li><a href="https://stagingec2.nestle.jp/">HOME</a></li>
<li><a href="https://shop.nestle.jp/front/app/info/help/">ご利用ガイドと利用規約</a></li>
<li><a href="https://shop.nestle.jp/front/app/info/compliance/">特定商取引法に基づく表示</a></li>
<li><a href="https://shop.nestle.jp/front/contents/address/">アドレス帳機能について</a></li>
<li><a href="https://shop.nestle.jp/front/app/info/faq/">よくある質問</a></li>
<li class="last"><a href="https://shop.nestle.jp/front/contents/inquiry/">お問い合わせ</a></li>
</ul>
</div>
CONTENT
            ],
            'footer' => [
                'identifier' => 'riki-block-footer',
                'is_active' => 1,
                'title' => 'Footer',
                'store_id' => 0,
                'content' => <<<'CONTENT'
                   <div class="footer content">
                    <p class="copy">（c）ネスレグループ</p>
                    <p class="btnpagetop"><a href="#header"> <img src="{{view url="images/footer/btn_pagetop.png"}}" alt="" /></a></p>
                    <ul class="btnfooter">
                    <li><a href="http://nestle.jp/" target="_blank">ネスレのホームへ</a>｜</li>
                    <li><a href="#" target="_blank">ネスレ通販オンラインショップへ</a>｜</li>
                    <li><a href="http://nestle.jp/faq/" target="_blank">お問合せ</a>｜</li>
                    <li><a href="http://nestle.jp/a_web/" target="_blank">サイトの運営方針</a>｜</li>
                    <li><a href="http://nestle.jp/privacy/" target="_blank">個人情報保護方針</a>｜</li>
                    <li><a href="http://nestle.jp/point/" target="_blank">ポイントプログラム</a>｜</li>
                    <li><a href="http://b.nestle.co.jp/map/" target="_blank">サイトマップ</a></li>
                    </ul>
                    <div id="footer_inner" class="clearfix">
                    <div id="footer_description">
                    <p>ネスレ通販オンラインショップでは、ギフトにも最適なコーヒー「ネスカフェ」をはじめ、「バリスタ」や「ドルチェ グスト」といったコーヒーマシン、世界初のカプセル式ティー専用マシン「スペシャル.T」、「キットカット」等のお菓子など、幅広い分野の商品を取り揃えています。<br /> お買い上げ金額に応じてネスレショッピングポイントも貯まります！ぜひネスレ通販オンラインショップで、楽しくおトクにお買いものを！</p>
                    </div>
                    <div id="copyright">
                    <p class="copyright"><a href="http://nestle.jp/" target="_blank"> <img src="{{view url="images/logo.png"}}" alt="" /></a></p>
                    <address>Copyright (C) Nestle Group All rights reserved.</address></div>
                    </div>
                    </div>
CONTENT
            ],
            'pre-defined-message-search-no-results' => [
                'identifier' => 'pre-defined-message-search-no-results',
                'is_active' => 1,
                'title' => 'Pre-defined message search no results',
                'store_id' => 0,
                'content' => <<<'CONTENT'
                   <p>お探しの商品が見つかりませんでした</p>
CONTENT
            ],
        ];

        foreach ($staticBlocks as $blockId => $staticBlockData) {
            /* @var $staticBlock \Magento\Cms\Model\Block */
            $staticBlock = $this->blockFactory->create();

            $staticBlock->load($staticBlockData['identifier'], 'identifier');

            if ($staticBlock->getId()) {
                $staticBlock->delete();
                $staticBlock = $this->blockFactory->create();
            }

            $staticBlock->setData($staticBlockData);
            $staticBlock->save();
        }

        $this->configWriter->save('design/header/logo_width', '172');
        $this->configWriter->save('design/header/logo_height', '75');
        $this->configWriter->save('design/footer/copyright', '');

        $setup->endSetup();
    }
}
