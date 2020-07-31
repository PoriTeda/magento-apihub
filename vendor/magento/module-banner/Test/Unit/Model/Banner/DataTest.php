<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Model\Banner;

use Magento\Banner\Model\Config;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var int
     */
    const STORE_ID = 1;

    /**
     * @var \Magento\Banner\Model\Banner\Data
     */
    private $unit;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $bannerResource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $httpContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $currentWebsite;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $banner;

    protected function setUp()
    {
        $this->bannerResource = $this->createMock(\Magento\Banner\Model\ResourceModel\Banner::class);
        $this->checkoutSession = $this->createPartialMock(
            \Magento\Checkout\Model\Session::class,
            ['getQuoteId', 'getQuote']
        );
        $this->httpContext = $this->createMock(\Magento\Framework\App\Http\Context::class);
        $this->currentWebsite = $this->createMock(\Magento\Store\Model\Website::class);
        $this->banner = $this->createMock(\Magento\Banner\Model\Banner::class);

        $pageFilterMock = $this->createMock(\Magento\Cms\Model\Template\Filter::class);
        $pageFilterMock->expects($this->any())->method('filter')->will($this->returnArgument(0));
        $filterProviderMock = $this->createMock(\Magento\Cms\Model\Template\FilterProvider::class);
        $filterProviderMock->expects($this->any())->method('getPageFilter')->will($this->returnValue($pageFilterMock));

        $currentStore = $this->createMock(\Magento\Store\Model\Store::class);
        $currentStore->expects($this->any())->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $storeManager->expects($this->once())->method('getStore')->will($this->returnValue($currentStore));
        $storeManager->expects($this->once())->method('getWebsite')->will($this->returnValue($this->currentWebsite));

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->unit = $helper->getObject(
            \Magento\Banner\Model\Banner\Data::class,
            [
                'banner' => $this->banner,
                'bannerResource' => $this->bannerResource,
                'checkoutSession' => $this->checkoutSession,
                'httpContext' => $this->httpContext,
                'filterProvider' => $filterProviderMock,
                'storeManager' => $storeManager,
            ]
        );
    }

    /**
     * @param array $result
     * @return array
     */
    protected function getExpectedResult($result)
    {
        return [
            'items' => $result + [
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => [],
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => [],
                Config::BANNER_WIDGET_DISPLAY_FIXED => [],
            ],
            'store_id' => self::STORE_ID
        ];
    }

    public function testGetBannersContentFixed()
    {
        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getActiveBannerIds')->willReturn([123]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('Fixed Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_FIXED => [
                    123 => [
                        'content' => 'Fixed Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }

    public function testGetBannersContentCatalogRule()
    {
        $this->httpContext->expects($this->any())->method('getValue')->willReturn('customer_group');
        $this->currentWebsite->expects($this->any())->method('getId')->willReturn('website_id');

        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')
            ->with('website_id', 'customer_group')->willReturn([123]);
        $this->bannerResource->expects($this->once())->method('getActiveBannerIds')->willReturn([]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('CatalogRule Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => [
                    123 => [
                        'content' => 'CatalogRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }

    public function testGetBannersContentSalesRule()
    {
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getAppliedRuleIds']);
        $quote->expects($this->any())->method('getAppliedRuleIds')->willReturn('15,11,12');
        $this->checkoutSession->expects($this->once())->method('getQuoteId')->will($this->returnValue(8000));
        $this->checkoutSession->expects($this->once())->method('getQuote')->will($this->returnValue($quote));

        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->with([15, 11, 12])
            ->willReturn([123]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getActiveBannerIds')->willReturn([]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('SalesRule Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => [
                    123 => [
                        'content' => 'SalesRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }
}
