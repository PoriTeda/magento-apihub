<?php
// @codingStandardsIgnoreFile
namespace Riki\Cms\Setup;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Upgrade Data script.
 *
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Page factory.
     *
     * @var PageFactory
     */
    private $pageFactory;

    /* @var BlockFactory */
    private $blockFactory;

    public function __construct(PageFactory $pageFactory, BlockFactory $blockFactory)
    {
        $this->pageFactory = $pageFactory;
        $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.5') < 0) {
            $staticBlocks = [
                'no_subscription' => [
                        'identifier' => 'no-subscription',
                        'is_active' => 1,
                        'title' => 'No Subscription',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                   <p><img width="100%" src="{{view url="Magento_Catalog::images/free_html_content.png"}}" alt="あなたのほっとするひと時と笑顔の" /></p>
CONTENT
                    ],
                'header_block' => [
                    'identifier' => 'riki-header-block',
                    'is_active' => 1,
                    'title' => 'Header',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                    <div id="subnavi_outer">
                    <div id="header_btn_suvnavi">{{block class="Riki\Theme\Block\Html\Head\Login" template="Riki_Theme::html/head/login.phtml"}}</div>
                    <div id="header_btn_cart"><a href="{{store url='checkout/cart'}}"> <img class="pc" src="{{view url="images/header/btn_header_cart.jpg"}}" alt="会員登録（無料）" /> <img class="sp" src="{{view url="images/header/btn_cart.png"}}" alt="会員登録（無料）" /> </a></div>
                    </div>
                    <div class="cwcn">
                    <p><img src="{{view url="images/header/header_cwcn.jpg"}}" alt="あなたのほっとするひと時と笑顔のために CHOOSE WELLNESS CHOOSE NESTLE" /></p>
                    </div>
                    <div class="txtwelcome" data-bind="scope: 'customer'">
                        <span>ようこそ<strong data-bind="text: customer().fullname ? customer().fullname : 'ゲスト'">ゲスト</strong>さん</span>
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
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.6') < 0) {
            $staticBlocks = [
                'mypage_seasonalgift' => [
                        'identifier' => 'mypage_seasonalgift',
                        'is_active' => 1,
                        'title' => 'Mypage Seasonalgift',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                   <h3 class="title">らくらくギフトお届け便</h3>
                    <div class="block seasonalgift">
                    <a class="more" href="#"><span>ご利用方法について</span></a> <textarea id="seasonalgift" name="seasonalgift" rows="3" cols="30"> 現在「ご注文票作成サポート」はご利用期間外です。
                        </textarea>
                    <p><img src="{{view url="images/account/mypage_seasonalgift.jpg"}}" alt="Mypage Seasonalgift" /></p>
                    </div>
CONTENT
                    ],
                'mypage_topright' => [
                        'identifier' => 'mypage_topright',
                        'is_active' => 1,
                        'title' => 'Promotion banner',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                    ],
                'about_coins' => [
                        'identifier' => 'about_coins',
                        'is_active' => 1,
                        'title' => 'About Coins',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                        <div class="item">
                        <ul>
                        <li><span class="link"><a>ネスレコインについて</a></span></li>
                        <li><span class="link"><a>ネスレコインが貯まるコンテンツ</a></span></li>
                        <li><span class="link"><a>ネスレコインで応募！</a></span></li>
                        </ul>
                        </div>
CONTENT
                    ],
                'mypage_campaign' => [
                        'identifier' => 'mypage_campaign',
                        'is_active' => 1,
                        'title' => 'Mypage Campaign',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                        <p><img src="{{view url="images/account/mypage_campaign.jpg"}}" alt="Mypage Campaign" /></p>
CONTENT
                    ],
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.7') < 0) {
            $staticBlocks = [
                'pre-defined-message-search-no-results' => [
                    'identifier' => 'search_noresult',
                    'is_active' => 1,
                    'title' => 'Search no results',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p>お探しの商品が見つかりませんでした</p>
CONTENT
                ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if (version_compare($context->getVersion(), '2.0.8') < 0) {
            $staticBlocks = [
                'header_block' => [
                    'identifier' => 'riki-header-block',
                    'is_active' => 1,
                    'title' => 'Header',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                    <div id="subnavi_outer">
                    <div id="header_btn_suvnavi">
                    {{block class="Riki\Theme\Block\Html\Head\Login" template="Riki_Theme::html/head/login.phtml"}}</div>
                    <div id="header_btn_cart"><a href="{{store url='checkout/cart'}}">
                    <img class="pc" src="{{view url="images/header/btn_header_cart.jpg"}}" alt="会員登録（無料）" />
                    <img class="sp" src="{{view url="images/header/btn_cart.png"}}" alt="会員登録（無料）" /> </a></div>
                    </div>
                    <div class="cwcn">
                    <p><img src="{{view url="images/header/header_cwcn.jpg"}}" alt="あなたのほっとするひと時と笑顔のために CHOOSE WELLNESS CHOOSE NESTLE" /></p>
                    </div>
                    <div class="txtwelcome" data-bind="scope: 'customer'">
                        <span>ようこそ<strong data-bind="text: customer().fullname ? customer().fullname : 'ゲスト'">ゲスト</strong>さん</span>
                    </div>
                    <div class="navigation">
                    <ul>
                    <li><a href="https://stagingec2.nestle.jp/">HOME</a></li>
                    <li> <a href="https://shop.nestle.jp/front/app/info/help/">ご利用ガイドと利用規約</a></li>
                    <li><a href="https://shop.nestle.jp/front/app/info/compliance/">特定商取引法に基づく表示</a></li>
                    <li><a href="https://shop.nestle.jp/front/contents/address/">アドレス帳機能について</a></li>
                    <li><a href="https://shop.nestle.jp/front/app/info/faq/">よくある質問</a></li>
                    <li class="last"><a href="https://shop.nestle.jp/front/contents/inquiry/">お問い合わせ</a></li>
                    </ul>
                    </div>
CONTENT
                ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.9') < 0) {
            $staticBlocks = [
                'mypage_topright' => [
                        'identifier' => 'mypage_topright',
                        'is_active' => 1,
                        'title' => 'Promotion banner',
                        'store_id' => 0,
                        'content' => <<<'CONTENT'
                   <p><img src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                    ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.10') < 0) {
            $staticBlocks = [
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
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.11') < 0) {
            $staticBlocks = [
                'about_coins' => [
                    'identifier' => 'about_coins',
                    'is_active' => 1,
                    'title' => 'About Coins',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                        <div class="item">
                        <ul>
                        <li><a href="http://nestle.jp/member/#/nestle_coin" class="link">ネスレコインについて</a></li>
                        <li><a href="http://nestle.jp/member/#point_program" class="link">ネスレコインが貯まるコンテンツ</a></li>
                        <li><a href="http://p.nestle.jp/coin_present/" class="link">ネスレコインで応募！</a></li>
                        </ul>
                        </div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.13') < 0) {
            $staticBlocks = [
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
                    <li><a href="https://shop.nestle.jp/front/contents/top/" target="_blank">ネスレ通販オンラインショップへ</a>｜</li>
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
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.14') < 0) {
            $staticBlocks = [
                'no_subscription' => [
                    'identifier' => 'no-subscription',
                    'is_active' => 1,
                    'title' => 'No Subscription',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img width="100%" src="{{view url="Magento_Catalog::images/free_html_content.png"}}" alt="あなたのほっとするひと時と笑顔の" /></p>
CONTENT
                ]
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.15') < 0) {
            $staticBlocks = [
                'mypage_seasonalgift' => [
                    'identifier' => 'mypage_seasonalgift',
                    'is_active' => 1,
                    'title' => 'Mypage Seasonalgift',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <h3 class="title">らくらくギフトお届け便</h3>
                    <div class="block seasonalgift">
                    <a class="more" href="https://int.shop.nestle.jp/contents/HowToUse/" target="_blank"><span>ご利用方法について</span></a> <textarea id="seasonalgift" name="seasonalgift" rows="3" cols="30"> 現在「ご注文票作成サポート」はご利用期間外です。
                        </textarea>
                    <p><img src="{{view url="images/account/mypage_seasonalgift.jpg"}}" alt="Mypage Seasonalgift" /></p>
                    </div>
CONTENT
                ]
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.16') < 0) {
            $staticBlocks = [
                'header_block_cnc' => [
                    'identifier' => 'riki-header-cnc-block',
                    'is_active' => 1,
                    'title' => 'Header CNC',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div class="cwcn">
<p><img src="{{view url="images/header/header_cwcn.jpg"}}" alt="あなたのほっとするひと時と笑顔のために CHOOSE WELLNESS CHOOSE NESTLE" /></p>
</div>
<div class="navigation">
<ul>
<li><a href="https://int.shop.nestle.jp/front/app/catalog/category/init?searchCategoryCode=cnc">HOME</a></li>
</ul>
</div>
CONTENT
                ],
                'header_block_cis' => [
                    'identifier' => 'riki-header-cis-block',
                    'is_active' => 1,
                    'title' => 'Header CIS',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div class="cwcn">
<p><img src="{{view url="images/header/header_cwcn.jpg"}}" alt="あなたのほっとするひと時と笑顔のために CHOOSE WELLNESS CHOOSE NESTLE" /></p>
</div>
<div class="navigation">
<ul>
<li><a href="https://int.shop.nestle.jp/front/app/catalog/category/init?searchCategoryCode=cis">HOME</a></li>
</ul>
</div>
CONTENT
                ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.17') < 0) {
            $staticPages = [
                '404_not_found' => [
                    'identifier' => 'no-route',
                    'is_active' => 1,
                    'title' => 'お探しのページが見つかりませんでした',
                    'store_id' => 0,
                    'page_layout' => '1column',
                    'content_heading' => 'お探しのページが見つかりませんでした',
                    'content' => <<<'CONTENT'
                    <p>申し訳ありません。</br/>
お探しのページが見つかりませんでした。</p>
<p>URLが変更になったか、商品の販売が終了している可能性があります。</p>
<p>ご覧になりたいページをお探しの際には、{{block class="Riki\Checkout\Block\Checkout\Onepage\Success" template="Riki_Checkout::checkout/link_top_page.phtml"}}よりサイト内検索機能をご利用ください。</p>
CONTENT
                ]
            ];

            $this->_updateStaticPages($staticPages);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.18') < 0) {
            $staticBlocks = [
                'shopping_cart_1' => [
                    'identifier' => 'shopping-cart-1',
                    'is_active' => 1,
                    'title' => 'Shopping Cart 1',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div class="cart-tips">※商品のご購入は、「注文を確定する」ボタンを押した時点で注文手続きが完了いたします。<br/>「カート」に商品を入れた時点では、在庫は確保されません。「カート」に入れた時点で在庫があった商品でも、在庫数が少ない商品や注文が集中する商品等においては、注文手続きを行っている途中に商品が品切れになる場合やご希望の個数をご購入いただけない場合がございます。</div>
CONTENT
                ],
                'shopping_cart_2' => [
                    'identifier' => 'shopping-cart-2',
                    'is_active' => 1,
                    'title' => 'Shopping Cart 2',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div>
                        <a href="https://shop.nestle.jp/front/contents/inquiry"><img src="{{view url="Riki_Checkout::images/green-contact.png"}}" alt=""/></a>
                    </div>
CONTENT
                ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.0.19') < 0) {
            $staticBlocks = [
                'about_coins' => [
                    'identifier' => 'about_coins',
                    'is_active' => 1,
                    'title' => 'About Coins',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                        <div class="item">
                        <ul>
                        <li><a href="http://nestle.jp/member/#/nestle_coin" target="_blank" class="link">ネスレコインについて</a></li>
                        <li><a href="http://nestle.jp/member/#point_program" target="_blank" class="link">ネスレコインが貯まるコンテンツ</a></li>
                        <li><a href="http://p.nestle.jp/coin_present/" target="_blank" class="link">ネスレコインで応募！</a></li>
                        </ul>
                        </div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.0') < 0) {
            $staticBlocks = [
                'subscription_list_top_message' => [
                    'identifier' => 'subscription_list_top_message',
                    'is_active' => 1,
                    'title' => 'Subscription List Top Message',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div>以下の定期お届け便のお届け回について、「お届け予定日時の変更」、「お届け商品の変更」、「お届け先の変更」、「お支払方法の変更」、「お届け間隔の変更」、「次回のお届けのお休み」、「コース外商品の追加」ができます。<br/>※次回のお届け分が既に「出荷準備中」の場合は、お届け情報をご変更いただけません。<br/>※「お届け間隔を変更する」は次々回のお届け予定日時から適用されます。次回定期お届け便の日時をご変更される場合は、「注文内容を変更する」からお届け予定日時をご変更ください。<br/>※サービス料に関しては、「お届け予定日時」は「決済予定日」となります。</div>
CONTENT
                ],
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.1') < 0) {
            $staticBlocks = [
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
                    <li><a href="http://nestle.jp/" target="_blank">ネスレのホームへ</a><span>｜</span></li>
                    <li><a href="https://shop.nestle.jp/front/contents/top/" target="_blank">ネスレ通販オンラインショップへ</a><span>｜</span></li>
                    <li><a href="http://nestle.jp/faq/" target="_blank">お問合せ</a><span>｜</span></li>
                    <li><a href="http://nestle.jp/a_web/" target="_blank">サイトの運営方針</a><span>｜</span></li>
                    <li><a href="http://nestle.jp/privacy/" target="_blank">個人情報保護方針</a><span>｜</span></li>
                    <li><a href="http://nestle.jp/point/" target="_blank">ポイントプログラム</a><span>｜</span></li>
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
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.2') < 0) {
            $staticBlocks = [
                'about_selectable_date' => [
                    'identifier' => 'about-selectable-date',
                    'is_active' => 1,
                    'title' => 'About Selectable Date',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div>
                    <div>次回のお届日は、お届け予定日から1ヶ月先の日程の間でご変更いただくことが可能です。</div>
                    <div>　　例：　前回のお届けが10月20日に完了した場合</div>
                    <dl>
                    <dt>2週間コース</dt>
                    <dd>4月20日　＋　2週間　＋　1ヶ月　＝　5月2日まで</dd>
                    <dt>1ヶ月コース</dt>
                    <dd>4月20日　＋　1ヶ月　＋　1ヶ月　＝　6月19日まで</dd>
                    <dt>2ヶ月コース</dt>
                    <dd>4月20日　＋　2ヶ月　＋　1ヶ月　＝　7月19日まで</dd>
                    <dt>3ヶ月コース</dt>
                    <dd>4月20日　＋　3ヶ月　＋　1ヶ月　＝　8月19日まで</dd>
                    </dl>
                    </div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.3') < 0) {
            $staticBlocks = [
                'about_selectable_date' => [
                    'identifier' => 'about-selectable-date',
                    'is_active' => 1,
                    'title' => 'About Selectable Date',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div>
                    <div>次回のお届日は、お届け予定日から1ヶ月先の日程の間でご変更いただくことが可能です。</div>
                    <div>　　例：　前回のお届けが4月20日に完了した場合</div>
                    <dl>
                    <dt>2週間コース</dt>
                    <dd>4月20日　＋　2週間　＋　1ヶ月　＝　6月3日まで</dd>
                    <dt>1ヶ月コース</dt>
                    <dd>4月20日　＋　1ヶ月　＋　1ヶ月　＝　6月19日まで</dd>
                    <dt>2ヶ月コース</dt>
                    <dd>4月20日　＋　2ヶ月　＋　1ヶ月　＝　7月19日まで</dd>
                    <dt>3ヶ月コース</dt>
                    <dd>4月20日　＋　3ヶ月　＋　1ヶ月　＝　8月19日まで</dd>
                    </dl>
                    </div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.4') < 0) {
            $staticBlocks = [
                'machine_rental_add' => [
                    'identifier' => 'machine-rental-add',
                    'is_active' => 1,
                    'title' => 'Machine rental add banner',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div class="machine-rental-add"><a href="https://nestle.jp/enquete/ec/1704_machine_rental_add/enquete.php
">マシンをお持ちでない方はこちらからレンタルマシンをお申込みください。
            </a></div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }
        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.5') < 0) {
            $staticBlocks = [
                'mypage_topright_for_normal' => [
                    'identifier' => 'mypage_topright_for_normal',
                    'is_active' => 1,
                    'title' => 'Mypage Topright for Normal',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                ],
                'mypage_topright_for_subscriber_xxx' => [
                    'identifier' => 'mypage_topright_for_subscriber_xxx',
                    'is_active' => 1,
                    'title' => 'Mypage Topright for Subscriber x day',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                ],
                'mypage_topright_for_clubmember_yyy' => [
                    'identifier' => 'mypage_topright_for_clubmember_yyy',
                    'is_active' => 1,
                    'title' => 'Mypage Topright for Clubmember y day',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                ],
                'mypage_topright_for_subscriber' => [
                    'identifier' => 'mypage_topright_for_subscriber',
                    'is_active' => 1,
                    'title' => 'Mypage Topright for Subscriber ',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                ],
                'mypage_topright_for_clubmember' => [
                    'identifier' => 'mypage_topright_for_clubmember',
                    'is_active' => 1,
                    'title' => 'Mypage Topright for  Clubmember',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-1.jpg"}}" alt="Promotion banner" /></p>
                    <p><img style="width: 100%;" src="{{view url="images/account/mypage_topright-2.jpg"}}" alt="Promotion banner" /></p>
CONTENT
                ]
            ];
            $this->_updateBlocks($staticBlocks);
        }


        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.6') < 0) {
            $staticBlocks = [
                'stock_point_delivery_explanation' => [
                    'identifier' => 'stock_point_delivery_explanation',
                    'is_active' => 1,
                    'title' => 'Stock Point delivery explanation',
                    'store_id' => 0,
                    'content' => ''
                ]
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.7') < 0) {
            $staticBlocks = [
                'stock_point_delivery_explanation' => [
                    'identifier' => 'stock_point_delivery_explanation',
                    'is_active' => 1,
                    'title' => 'Stock Point delivery explanation',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                    <div style="padding: 5px; display: inline-block;">
                        <a href="https://machieco.jp/machiecobin" target="_blank">
                            <img src="{{view url="images/machiecobutton.PNG"}}" alt="machieco便について詳しく" width="250" />
                        </a>
                    </div>
CONTENT
                ]
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.8') < 0) {
            $staticBlocks = [
                'stock_point_delivery_explanation' => [
                    'identifier' => 'stock_point_delivery_explanation',
                    'is_active' => 1,
                    'title' => 'Stock Point delivery explanation',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                        <a href="https://machieco.jp/machiecobin" target="_blank" >machieco便についてはコチラ↓</a><br/>
                        <a href="https://machieco.jp/machiecobin" target="_blank" >
                            <img src="{{view url="images/nestle_MACHIECO.jpg"}}"  width="250" alt="machieco便について詳しく">
                        </a>
CONTENT
                ]
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.1.9') < 0) {
            $staticBlocks = [
                'stock_point_delivery_explanation_not_allowed' => [
                    'identifier' => 'stock_point_delivery_explanation_not_allowed',
                    'is_active' => 1,
                    'title' => 'Stock Point delivery explanation not allowed',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                        <a href="https://machieco.jp/machiecobin" target="_blank" >machieco便についてはコチラ↓</a><br/>
                        <a href="https://machieco.jp/machiecobin" target="_blank" >
                            <img src="{{view url="images/nestle_MACHIECO.jpg"}}"  width="250" alt="machieco便について詳しく">
                        </a>
CONTENT
                ],
                'stock_point_delivery_explanation_oos' => [
                    'identifier' => 'stock_point_delivery_explanation_oos',
                    'is_active' => 1,
                    'title' => 'Stock Point delivery explanation OOS',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                        <a href="https://machieco.jp/machiecobin" target="_blank" >machieco便についてはコチラ↓</a><br/>
                        <a href="https://machieco.jp/machiecobin" target="_blank" >
                            <img src="{{view url="images/nestle_MACHIECO.jpg"}}"  width="250" alt="machieco便について詳しく">
                        </a>
CONTENT
                ]

            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.0') < 0) {
            $staticBlocks = [
                'warning_message_for_profile_list' => [
                    'identifier' => 'warning_message_for_profile_list',
                    'is_active' => 1,
                    'title' => 'Warning message for profile list',
                    'store_id' => 0,
                    'content' => ''
                ],
            ];
            $this->_updateBlocks($staticBlocks);
        }

        // Add new block 'order_success_subscription' for order success subscription hanpukai page
        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.1') < 0) {
            $staticBlocks = [
                'order_success_subscription' => [
                    'identifier' => 'order_success_subscription',
                    'is_active' => 0,
                    'title' => 'Order success subscription',
                    'store_id' => 0,
                    'content' => <<<CONTENT
                        <center></center>
                        <center></center>
                        <center>
                           <p><a href="https://nestle.jp/login.php?enquete_id=10119">
                               <img src="{{media url="wysiwyg/2____1700x370.jpg"}}" alt="" /></a>
                           </p>
                           <p><a href="https://order.nestle.jp/ec/subscription-page/view/index/id/NS000279S/frequency/21">
                               <img src="{{media url="wysiwyg/2_aquawith.png"}}" alt="" /></a>
                           </p>
                           <center>
                              <p><a href="https://shop.nestle.jp/front/contents/NBAMchrental/2rent2/">
                                  <img src="{{media url="wysiwyg/3_multi.png"}}" alt="" /></a>
                              </p>
                              <center>
                                 <p>&nbsp;</p>
                                 <p><span style="font-size: 140%;">&nbsp;<span style="color: #319b42;"><strong>MACHI ECO便</strong></span>をご利用の方は、こちらから引き続きECO HUBをお選びください。</span>
                                     <a href="https://machieco.jp/"><img src="{{media url="wysiwyg/category_banner/bnr_MACHI_ECO.JPG"}}" alt="" width="460" /></a>
                                 </p>
                              </center>
                              <hr />
                           </center>
                           <p>&nbsp;</p>
                        </center>
CONTENT
                ],
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.2') < 0) {
            $staticBlocks = [
                'shopping_cart_1' => [
                    'identifier' => 'shopping-cart-1',
                    'is_active' => 1,
                    'title' => 'Shopping Cart 1',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <div class="cart-tips cart-tips_noty"><span class="cart-tips_title cl_red">※商品のご購入は、「注文を確定する」ボタンを押した時点で注文手続きが完了いたします。</span><br/><span class="cart-tips_up cl_red">「カート」に商品を入れた時点では、在庫は確保されません。</span>「カート」に入れた時点で在庫があった商品でも、在庫数が少ない商品や注文が集中する商品等においては、注文手続きを行っている途中に商品が品切れになる場合やご希望の個数をご購入いただけない場合がございます。</div>
CONTENT
                ]
            ];

            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.3') < 0) {
            $staticBlocks = [
                'profile_list_disengagement_info' => [
                    'identifier' => 'profile_list_disengagement_info',
                    'is_active' => 1,
                    'title' => 'Subscription profile disengagement',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                    <ul>
                        <li>・定期便の種類によって、解約条件が異なります。</li>
                        <li>・一部の定期便については、こちらの解約フォームでのキャンセルを受け付けていません。恐れ入りますが、お電話にてお問い合わせください。</li>
                        <li>・次回お届け予定日の12日前までにご連絡ください。（年末年始・ゴールデンウィーク・お盆休み等の長期休暇期間は、余裕を持って12日前よりお早めにご連絡ください。）</li>
                        <li>・次回お届けが確定している場合は、次回お届け分のキャンセルはできません。次々回のお届け分からキャンセルします</li>
                    </ul>
CONTENT
                ],
            ];
            $this->_updateBlocks($staticBlocks);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.4') < 0) {
            $staticBlocks = [
                'footer' => [
                    'identifier' => 'riki-block-footer',
                    'is_active' => 1,
                    'title' => 'Footer',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <div class="footer content">
                    <p class="copy">（c）ネスレグループ</p>
                    <p class="btnpagetop"><a class="btn-top" href="#header"><span class="d-pc">トップへ戻る<i class="fa fa-arrow-up pc"></i> </span><span class="d-sp"><i class="fa fa-angle-up"></i></span></a></p>
                    <ul class="btnfooter">
                    <li><a href="http://nestle.jp/" target="_blank">ネスレのホームへ</a>｜</li>
                    <li><a href="https://shop.nestle.jp/front/contents/top/" target="_blank">ネスレ通販オンラインショップへ</a>｜</li>
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
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.5') < 0) {
            $staticBlocks = [
                'footer' => [
                    'identifier' => 'riki-block-footer',
                    'is_active' => 1,
                    'title' => 'Footer',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                   <div class="footer content">
                    <p class="copy">（c）ネスレグループ</p>
                    <p class="btnpagetop"><a class="btn-top" href="#header"><span class="d-pc">トップへ戻る<i class="fa fa-arrow-up pc"></i> </span><span class="d-sp"><i class="fa fa-angle-up"></i></span></a></p>
                    <ul class="btnfooter">
                    <li><a href="http://nestle.jp/" target="_blank">ネスレのホームへ</a>｜</li>
                    <li><a href="https://shop.nestle.jp/front/contents/top/" target="_blank">ネスレ通販オンラインショップへ</a>｜</li>
                    <li><a href="http://nestle.jp/faq/" target="_blank">お問合せ</a>｜</li>
                    <li><a href="http://nestle.jp/a_web/" target="_blank">サイトの運営方針</a>｜</li>
                    <li><a href="http://nestle.jp/privacy/" target="_blank">個人情報保護方針</a>｜</li>
                    <li><a href="http://nestle.jp/point/" target="_blank">ポイントプログラム</a>｜</li>
                    <li><a href="http://b.nestle.co.jp/map/" target="_blank">サイトマップ</a></li>
                    </ul>
                    <div id="footer_inner" class="clearfix">
                
                    <div id="copyright">
                    <address>Copyright (C) Nestle Group All rights reserved.</address></div>
                    </div>
                    </div>
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }
        if ($context->getVersion() && version_compare($context->getVersion(), '2.2.6') < 0) {
            $staticBlocks = [
                'footer' => [
                    'identifier' => 'riki-block-pagetop',
                    'is_active' => 1,
                    'title' => 'Footer Page Top ',
                    'store_id' => 0,
                    'content' => <<<'CONTENT'
                  
                    
                    <p class="btnpagetop"><a class="btn-top" href="#header"><span class="d-pc">トップへ戻る<i class="fa fa-arrow-up pc"></i> </span><span class="d-sp"><i class="fa fa-angle-up"></i></span></a></p>
                    
                    
CONTENT
                ]
            ];

            foreach ($staticBlocks as $blockId => $staticBlockData) {
                $staticBlock = $this->blockFactory->create();

                $staticBlock->load($staticBlockData['identifier'], 'identifier');

                if ($staticBlock->getId()) {
                    $staticBlock->delete();
                    $staticBlock = $this->blockFactory->create();
                }

                $staticBlock->setData($staticBlockData);
                $staticBlock->save();
            }
        }

        $setup->endSetup();
    }

    protected function _updateBlocks($blocks)
    {
        foreach ($blocks as $staticBlockData) {
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

    }

    protected function _updateStaticPages($pages)
    {
        foreach ($pages as $staticPageData) {
            /* @var $staticBlock \Magento\Cms\Model\Block */
            $staticPage = $this->pageFactory->create();

            $staticPage->load($staticPageData['identifier'], 'identifier');

            if ($staticPage->getId()) {
                $staticPage->delete();
                $staticPage = $this->pageFactory->create();
            }

            $staticPage->setData($staticPageData);
            $staticPage->save();
        }

    }

}
