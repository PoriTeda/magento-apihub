<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Test\Unit\Block;

/**
 * @covers \Magento\MultipleWishlist\Block\Behaviour
 */
class BehaviourTest extends \PHPUnit\Framework\TestCase
{
    const CREATE_WISHLIST_ROUTE = 'wishlist/index/createwishlist';

    /**
     * @var \Magento\MultipleWishlist\Block\Behaviour
     */
    protected $blockBehaviour;

    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->blockBehaviour = $objectManager->getObject(
            \Magento\MultipleWishlist\Block\Behaviour::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * @covers \Magento\MultipleWishlist\Block\Behaviour::getCreateUrl
     * @param bool $isSecure
     * @param string $url
     * @param string $expectedResult
     * @dataProvider getCreateUrlDataProvider
     */
    public function testGetCreateUrl($isSecure, $url, $expectedResult)
    {
        $this->requestMock->expects($this->once())
            ->method('isSecure')
            ->willReturn($isSecure);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                self::CREATE_WISHLIST_ROUTE,
                [
                    '_secure' => $isSecure
                ]
            )
            ->willReturn($url);

        $this->assertStringStartsWith($expectedResult, $this->blockBehaviour->getCreateUrl());
    }

    public function getCreateUrlDataProvider()
    {
        return [
            'http' => [
                'isSecure' => false,
                'url' => 'http://site-name.com/wishlist/index/createwishlist',
                'expectedResult' => 'http://'
            ],
            'https' => [
                'isSecure' => true,
                'url' => 'https://site-name.com/wishlist/index/createwishlist',
                'expectedResult' => 'https://'
            ]
        ];
    }
}
