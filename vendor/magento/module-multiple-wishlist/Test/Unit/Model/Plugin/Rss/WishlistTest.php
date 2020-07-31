<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Model\Plugin\Rss;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class WishlistTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\MultipleWishlist\Model\Plugin\Rss\Wishlist */
    protected $wishlist;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\MultipleWishlist\Helper\Rss|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlInterface;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeConfig;

    /** @var \Magento\Customer\Helper\View|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerViewHelper;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerRepository;

    protected function setUp()
    {
        $this->helper = $this->createMock(\Magento\MultipleWishlist\Helper\Rss::class);
        $this->urlInterface = $this->createMock(\Magento\Framework\UrlInterface::class);

        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->customerViewHelper = $this->createMock(\Magento\Customer\Helper\View::class);
        $this->customerRepository = $this->getMockForAbstractClass(
            \Magento\Customer\Api\CustomerRepositoryInterface::class,
            [],
            '',
            false
        );

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wishlist = $this->objectManagerHelper->getObject(
            \Magento\MultipleWishlist\Model\Plugin\Rss\Wishlist::class,
            [
                'wishlistHelper' => $this->helper,
                'urlBuilder' => $this->urlInterface,
                'scopeConfig' => $this->scopeConfig,
                'customerViewHelper' => $this->customerViewHelper,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * @dataProvider aroundGetHeaderDataProvider
     *
     * @param bool $multipleEnabled
     * @param int $customerId
     * @param bool $isDefault
     * @param array $expectedResult
     */
    public function testAroundGetHeader($multipleEnabled, $customerId, $isDefault, $expectedResult)
    {
        $subject = $this->createMock(\Magento\Wishlist\Model\Rss\Wishlist::class);
        $wishlist = $this->createPartialMock(
            \Magento\Wishlist\Model\Wishlist::class,
            [
                'getId',
                'getCustomerId',
                'getName',
                'getSharingCode',
                '__wakeup'
            ]
        );
        $wishlist->expects($this->any())->method('getId')->will($this->returnValue(5));
        $wishlist->expects($this->any())->method('getCustomerId')->will($this->returnValue(8));
        $wishlist->expects($this->any())->method('getName')->will($this->returnValue('Wishlist1'));
        $wishlist->expects($this->any())->method('getSharingCode')->will($this->returnValue('code'));

        $customer = $this->getMockForAbstractClass(\Magento\Customer\Api\Data\CustomerInterface::class, [], '', false);
        $customer->expects($this->any())->method('getId')->will($this->returnValue($customerId));
        $customer->expects($this->any())->method('getEmail')->will($this->returnValue('test@example.com'));

        $this->helper->expects($this->any())->method('getWishlist')->will($this->returnValue($wishlist));
        $this->helper->expects($this->any())->method('getCustomer')->will($this->returnValue($customer));
        $this->helper->expects($this->any())->method('isWishlistDefault')->will($this->returnValue($isDefault));
        $this->helper->expects($this->any())->method('getDefaultWishlistName')->will($this->returnValue('Wishlist1'));

        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->with('wishlist/general/multiple_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ->will($this->returnValue($multipleEnabled));

        $this->customerRepository
            ->expects($this->any())
            ->method('getById')
            ->with(8)
            ->will($this->returnValue($customer));

        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->with($customer)
            ->will($this->returnValue('Customer1'));

        $this->urlInterface
            ->expects($this->any())
            ->method('getUrl')
            ->with('wishlist/shared/index', ['code' => 'code'])
            ->will($this->returnValue('http://url.com/rss/feed/index/type/wishlist/wishlist_id/5'));

        $proceed = function () use ($expectedResult) {
            return $expectedResult;
        };

        $this->assertEquals($expectedResult, $this->wishlist->aroundGetHeader($subject, $proceed));
    }

    public function aroundGetHeaderDataProvider()
    {
        return [
            [false, 8, true, [
                'title' => 'title',
                'description' => 'title',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 8, true, [
                'title' => 'Customer1\'s Wish List',
                'description' => 'Customer1\'s Wish List',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 8, false, [
                'title' => 'Customer1\'s Wish List (Wishlist1)',
                'description' => 'Customer1\'s Wish List (Wishlist1)',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 9, false, [
                'title' => 'Customer1\'s Wish List (Wishlist1)',
                'description' => 'Customer1\'s Wish List (Wishlist1)',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
        ];
    }
}
