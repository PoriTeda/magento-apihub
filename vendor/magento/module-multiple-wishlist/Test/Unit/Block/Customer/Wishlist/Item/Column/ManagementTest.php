<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Management
 */
namespace Magento\MultipleWishlist\Test\Unit\Block\Customer\Wishlist\Item\Column;

class ManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Management
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    protected $wishlistListMock;

    /**
     * @var \Magento\MultipleWishlist\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishlistHelperMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataCustomerMock;

    /**
     * @var \Magento\Wishlist\Model\Wishlist|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wishlistMock;

    /**
     * @var \Magento\Catalog\Block\Product\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->dataCustomerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->wishlistHelperMock = $this->getMockBuilder(\Magento\MultipleWishlist\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistMock = $this->getMockBuilder(\Magento\Wishlist\Model\Wishlist::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistListMock = $objectManagerHelper->getCollectionMock(
            \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection::class,
            [$this->wishlistMock]
        );

        $this->contextMock = $this->getMockBuilder(\Magento\Catalog\Block\Product\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->once())
            ->method('getWishlistHelper')
            ->will($this->returnValue($this->wishlistHelperMock));

        $this->model = $objectManagerHelper->getObject(
            \Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Management::class,
            ['context' => $this->contextMock]
        );
    }

    public function testCanCreateWishlistsNoCustomer()
    {
        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->will($this->returnValue(false));

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlists()
    {
        $this->dataCustomerMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(true));

        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->will($this->returnValue($this->dataCustomerMock));
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->will($this->returnValue(false));

        $this->assertTrue($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlistsLimitReached()
    {
        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->will($this->returnValue($this->dataCustomerMock));
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->will($this->returnValue(true));

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlistsNoCustomerId()
    {
        $this->dataCustomerMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(false));

        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->will($this->returnValue($this->dataCustomerMock));
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->will($this->returnValue(false));

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }
}
