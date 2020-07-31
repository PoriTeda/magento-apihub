<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Request\HtmlRedirect;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class OrderDataBuilderTest
 *
 * Test for class \Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder
 */
class OrderDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const ORDER_INCREMENT_ID = 'test-cart-id';

    const GRAND_TOTAL_AMOUNT = '2.7000';

    const CURRENCY_CODE = 'USD';

    const STORE_ID = 1;

    const ORDER_ID = 1;

    const RESPONSE_URL = 'https://test.response.com';

    const INSTALLATION_ID = 'test-id';

    const REQUEST_TYPE_AUTHORIZE = 'E';

    const TEST_ACTION = 'test-action-value';

    const TEST_URL = 'gateway-url-test-value';

    const LIVE_URL = 'gateway-url-live-value';

    const MD5_SECRET = 'test-secret';

    const SIGNATURE_FIELDS = 'instId:cartId:amount:currency';

    /**
     * @var array
     */
    protected $addressData = [
        'getStreetLine1' => 'line-1',
        'getStreetLine2' =>  'line-2',
        'getPrefix' => 'prefix',
        'getFirstname' => 'firstname',
        'getMiddlename' => 'middlename',
        'getLastname' => 'lastname',
        'getSuffix' => 'suffix',
        'getCity' => 'city',
        'getRegionCode' => 'region',
        'getPostcode' => 'postcode',
        'getCountryId' => 'countryid',
        'getTelephone' => 'telephone',
        'getEmail' => 'email',
    ];

    /**
     * @var OrderDataBuilder
     */
    protected $orderDataBuilder;

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $localeResolver;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->urlHelperMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();
        $this->localeResolver = $this->getMockBuilder(
            \Magento\Framework\Locale\ResolverInterface::class
        )
            ->getMockForAbstractClass();

        $this->orderDataBuilder = new OrderDataBuilder(
            $this->configMock,
            $this->urlHelperMock,
            $this->localeResolver
        );
    }

    /**
     * Run test build method (success)
     *
     * @param $fixContact
     * @param $hideContact
     * @param $testMode
     * @param $expected
     * @return void
     * @dataProvider buildSuccessDataProvider
     */
    public function testBuildSuccess($fixContact, $hideContact, $testMode, $expected)
    {
        $paymentDOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentDOMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->getOrderMock());
        $this->localeResolver->expects(static::once())
            ->method('getLocale')
            ->willReturn('en_GB');

        $this->urlHelperMock->expects(static::once())
            ->method('getUrl')
            ->with(OrderDataBuilder::RESPONSE_URL)
            ->willReturn(self::RESPONSE_URL);

        $this->configMock->expects(static::any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['installation_id', self::STORE_ID, self::INSTALLATION_ID],
                    ['payment_action', self::STORE_ID, AbstractMethod::ACTION_AUTHORIZE],
                    ['test_action', self::STORE_ID, self::TEST_ACTION],
                    ['gateway_url_test', self::STORE_ID, self::TEST_URL],
                    ['gateway_url', self::STORE_ID, self::LIVE_URL],
                    ['md5_secret', self::STORE_ID, self::MD5_SECRET],
                    ['signature_fields', self::STORE_ID, self::SIGNATURE_FIELDS],
                    ['fix_contact', self::STORE_ID, $fixContact],
                    ['hide_contact', self::STORE_ID, $hideContact],
                    ['sandbox_flag', self::STORE_ID, $testMode]
                ]
            );

        $result = $this->orderDataBuilder->build(['payment' => $paymentDOMock]);

        $this->assertEquals($expected, $result);
    }

    /**
     *
     * @case #1 fix_contact and hide_contact are true, test mode on
     * @case #2 fix_contact and hide_contact are false, test mode on
     * @case #3 fix_contact and hide_contact are false, test mode off, live mode on
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildSuccessDataProvider()
    {
        return [
            1 => [
                'fix_contact' => '0',
                'hide_contact' => '1',
                'sandbox_flag' => '1',
                'expected' => [
                    'fields' => [
                        OrderDataBuilder::CART_ID => self::ORDER_INCREMENT_ID,
                        OrderDataBuilder::AMOUNT => sprintf('%.2F', self::GRAND_TOTAL_AMOUNT),
                        OrderDataBuilder::CURRENCY => self::CURRENCY_CODE,
                        OrderDataBuilder::ORDER_ID => self::ORDER_ID,
                        OrderDataBuilder::STORE_ID => self::STORE_ID,
                        OrderDataBuilder::INSTALLATION_ID => self::INSTALLATION_ID,
                        OrderDataBuilder::AUTH_MODE => self::REQUEST_TYPE_AUTHORIZE,
                        OrderDataBuilder::ADDRESS_1 => $this->addressData('getStreetLine1'),
                        OrderDataBuilder::ADDRESS_2 => $this->addressData('getStreetLine2'),
                        OrderDataBuilder::TOWN => $this->addressData('getCity'),
                        OrderDataBuilder::REGION => $this->addressData('getRegionCode'),
                        OrderDataBuilder::POSTCODE => $this->addressData('getPostcode'),
                        OrderDataBuilder::COUNTRY => $this->addressData('getCountryId'),
                        OrderDataBuilder::TELEPHONE => $this->addressData('getTelephone'),
                        OrderDataBuilder::EMAIL => $this->addressData('getEmail'),
                        OrderDataBuilder::LANGUAGE => 'en',
                        OrderDataBuilder::HIDE_CURRENCY => true,
                        OrderDataBuilder::FIX_CONTACT => true,
                        OrderDataBuilder::HIDE_CONTACT => true,
                        OrderDataBuilder::PAYMENT_CALLBACK => self::RESPONSE_URL,
                        OrderDataBuilder::NAME => self::TEST_ACTION,
                        OrderDataBuilder::TEST_MODE => OrderDataBuilder::TEST_MODE_VALUE,
                        OrderDataBuilder::SIGNATURE => $this->getSignature()
                    ],
                    'action' => self::TEST_URL
                ],
            ],
            2 => [
                'fix_contact' => '1',
                'hide_contact' => '0',
                'sandbox_flag' => '1',
                'expected' => [
                    'fields' => [
                        OrderDataBuilder::CART_ID => self::ORDER_INCREMENT_ID,
                        OrderDataBuilder::AMOUNT => sprintf('%.2F', self::GRAND_TOTAL_AMOUNT),
                        OrderDataBuilder::CURRENCY => self::CURRENCY_CODE,
                        OrderDataBuilder::ORDER_ID => self::ORDER_ID,
                        OrderDataBuilder::STORE_ID => self::STORE_ID,
                        OrderDataBuilder::INSTALLATION_ID => self::INSTALLATION_ID,
                        OrderDataBuilder::AUTH_MODE => self::REQUEST_TYPE_AUTHORIZE,
                        OrderDataBuilder::ADDRESS_1 => $this->addressData('getStreetLine1'),
                        OrderDataBuilder::ADDRESS_2 => $this->addressData('getStreetLine2'),
                        OrderDataBuilder::TOWN => $this->addressData('getCity'),
                        OrderDataBuilder::REGION => $this->addressData('getRegionCode'),
                        OrderDataBuilder::POSTCODE => $this->addressData('getPostcode'),
                        OrderDataBuilder::COUNTRY => $this->addressData('getCountryId'),
                        OrderDataBuilder::TELEPHONE => $this->addressData('getTelephone'),
                        OrderDataBuilder::EMAIL => $this->addressData('getEmail'),
                        OrderDataBuilder::LANGUAGE => 'en',
                        OrderDataBuilder::HIDE_CURRENCY => true,
                        OrderDataBuilder::PAYMENT_CALLBACK => self::RESPONSE_URL,
                        OrderDataBuilder::NAME => self::TEST_ACTION,
                        OrderDataBuilder::TEST_MODE => OrderDataBuilder::TEST_MODE_VALUE,
                        OrderDataBuilder::SIGNATURE => $this->getSignature()
                    ],
                    'action' => self::TEST_URL
                ],
            ],
            3 => [
                'fix_contact' => '1',
                'hide_contact' => '0',
                'sandbox_flag' => '0',
                'expected' => [
                    'fields' => [
                        OrderDataBuilder::CART_ID => self::ORDER_INCREMENT_ID,
                        OrderDataBuilder::AMOUNT => sprintf('%.2F', self::GRAND_TOTAL_AMOUNT),
                        OrderDataBuilder::CURRENCY => self::CURRENCY_CODE,
                        OrderDataBuilder::ORDER_ID => self::ORDER_ID,
                        OrderDataBuilder::STORE_ID => self::STORE_ID,
                        OrderDataBuilder::INSTALLATION_ID => self::INSTALLATION_ID,
                        OrderDataBuilder::AUTH_MODE => self::REQUEST_TYPE_AUTHORIZE,
                        OrderDataBuilder::NAME => 'prefix firstname middlename lastname suffix',
                        OrderDataBuilder::ADDRESS_1 => $this->addressData('getStreetLine1'),
                        OrderDataBuilder::ADDRESS_2 => $this->addressData('getStreetLine2'),
                        OrderDataBuilder::TOWN => $this->addressData('getCity'),
                        OrderDataBuilder::REGION => $this->addressData('getRegionCode'),
                        OrderDataBuilder::POSTCODE => $this->addressData('getPostcode'),
                        OrderDataBuilder::COUNTRY => $this->addressData('getCountryId'),
                        OrderDataBuilder::TELEPHONE => $this->addressData('getTelephone'),
                        OrderDataBuilder::EMAIL => $this->addressData('getEmail'),
                        OrderDataBuilder::LANGUAGE => 'en',
                        OrderDataBuilder::HIDE_CURRENCY => true,
                        OrderDataBuilder::PAYMENT_CALLBACK => self::RESPONSE_URL,
                        OrderDataBuilder::TEST_MODE => OrderDataBuilder::LIVE_MODE_VALUE,
                        OrderDataBuilder::SIGNATURE => $this->getSignature()
                    ],
                    'action' => self::LIVE_URL
                ]
            ]
        ];
    }

    /**
     * Get signature
     *
     * @return string
     */
    private function getSignature()
    {
        return hash(
            'md5',
            implode(
                OrderDataBuilder::GLUE,
                [
                    self::MD5_SECRET,
                    self::INSTALLATION_ID,
                    self::ORDER_INCREMENT_ID,
                    sprintf('%.2F', self::GRAND_TOTAL_AMOUNT),
                    self::CURRENCY_CODE
                ]
            )
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OrderInterface
     */
    protected function getOrderMock()
    {
        $this->orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $this->orderMock->expects(static::once())
            ->method('getOrderIncrementId')
            ->willReturn(self::ORDER_INCREMENT_ID);
        $this->orderMock->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn(self::GRAND_TOTAL_AMOUNT);
        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE);
        $this->orderMock->expects(static::once())
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);
        $this->orderMock->expects(static::once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn($this->getAddressMock());

        return $this->orderMock;
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject|AddressAdapterInterface
     */
    protected function getAddressMock()
    {
        $addressMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class)
            ->getMockForAbstractClass();

        $addressMock->expects(static::once())
            ->method('getStreetLine1')
            ->willReturn($this->addressData('getStreetLine1'));
        $addressMock->expects(static::once())
            ->method('getStreetLine2')
            ->willReturn($this->addressData('getStreetLine2'));
        $addressMock->expects(static::exactly(2))
            ->method('getPrefix')
            ->willReturn($this->addressData('getPrefix'));
        $addressMock->expects(static::once())
            ->method('getFirstname')
            ->willReturn($this->addressData('getFirstname'));
        $addressMock->expects(static::exactly(2))
            ->method('getMiddlename')
            ->willReturn($this->addressData('getMiddlename'));
        $addressMock->expects(static::once())
            ->method('getLastname')
            ->willReturn($this->addressData('getLastname'));
        $addressMock->expects(static::exactly(2))
            ->method('getSuffix')
            ->willReturn($this->addressData('getSuffix'));

        $addressMock->expects(static::once())
            ->method('getCity')
            ->willReturn($this->addressData('getCity'));
        $addressMock->expects(static::once())
            ->method('getRegionCode')
            ->willReturn($this->addressData('getRegionCode'));
        $addressMock->expects(static::once())
            ->method('getPostcode')
            ->willReturn($this->addressData('getPostcode'));
        $addressMock->expects(static::once())
            ->method('getCountryId')
            ->willReturn($this->addressData('getCountryId'));
        $addressMock->expects(static::once())
            ->method('getTelephone')
            ->willReturn($this->addressData('getTelephone'));
        $addressMock->expects(static::once())
            ->method('getEmail')
            ->willReturn($this->addressData('getEmail'));

        return $addressMock;
    }

    /**
     * Run test build method (Exception)
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $this->orderDataBuilder->build([]);
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function addressData($method)
    {
        return $this->addressData[$method];
    }
}
