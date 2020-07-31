<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request\Shared;

use Magento\Eway\Gateway\Request\Shared\RequestDataBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;

/**
 * Class RequestDataBuilderTest
 */
class RequestDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const RESPONSE_URL = 'https://test.response.com';

    /**
     * @var RequestDataBuilder
     */
    private $builder;

    /**
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeResolverMock;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlHelperMock;

    protected function setUp()
    {
        $this->localeResolverMock = $this
            ->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->getMockForAbstractClass();

        $this->urlHelperMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();

        $this->builder = new RequestDataBuilder(
            $this->localeResolverMock,
            $this->urlHelperMock
        );
    }

    /**
     * Run test for build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            RequestDataBuilder::LANGUAGE => 'EN',
            RequestDataBuilder::CUSTOMER_READONLY => true,
            RequestDataBuilder::REDIRECT_URL => self::RESPONSE_URL,
            RequestDataBuilder::CANCEL_URL => self::RESPONSE_URL
        ];

        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn('en_GB');

        $this->urlHelperMock->expects(static::exactly(2))
            ->method('getBaseUrl')
            ->willReturn(self::RESPONSE_URL);

        $result = $this->builder->build([]);

        static::assertEquals($expected, $result);
    }
}
