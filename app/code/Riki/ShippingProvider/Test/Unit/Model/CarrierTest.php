<?php
/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShippingProvider\Test\Unit\Model;

use Riki\ShippingProvider\Model\Carrier;

/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Carrier
     *
     * @var Carrier
     */
    protected $model;

    /**
     * PHPUnit_Framework_MockObject_MockObject
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfig;

    /**
     * PHPUnit_Framework_MockObject_MockObject
     *
     * @var \Magento\Shipping\Model\Rate\Result|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateResultFactory;

    /**
     * PHPUnit_Framework_MockObject_MockObject
     *
     * @var \Magento\Shipping\Model\Rate\Result|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateResult;

    /**
     * PHPUnit_Framework_MockObject_MockObject
     *
     * @var \Magento\Quote\Model\Quote\Address\RateResult\Method|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateMethodFactory;

    /**
     * PHPUnit_Framework_MockObject_MockObject
     *
     * @var \Magento\Quote\Model\Quote\Address\RateResult\Method|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateResultMethod;

    /**
     * LocationData
     *
     * @var array
     */
    protected $locationData = [
        'title' => 'Store 1',
        'street' => '4895 Norman Street',
        'phone' => '323-329-5126',
        'message' => 'Open hours 8:00-21:00',
    ];

    /**
     * ShippingOrigin
     *
     * @var array
     */
    protected $shippingOrigin = [
        'country_id' => 'US',
        'region_id' => '12',
        'postcode' => '90014',
        'city' => 'Los Angeles',
    ];

    /**
     * SetUp
     *
     * @inheritdoc
     * @return     $this
     */
    protected function setUp()
    {
        $this->scopeConfig = $this->getMockBuilder(
            'Magento\Framework\App\Config\ScopeConfigInterface'
        )->getMockForAbstractClass();
        $rateErrorFactory = $this->getMockBuilder(
            'Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory'
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMockForAbstractClass();
        $this->rateResultFactory = $this->getMockBuilder(
            'Magento\Shipping\Model\Rate\ResultFactory'
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->rateResult = $this->getMockBuilder(
            'Magento\Shipping\Model\Rate\Result'
        )->disableOriginalConstructor()->getMock();
        $this->rateMethodFactory = $this->getMockBuilder(
            'Magento\Quote\Model\Quote\Address\RateResult\MethodFactory'
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->rateResultMethod = $this->getMockBuilder(
            'Magento\Quote\Model\Quote\Address\RateResult\Method'
        )->disableOriginalConstructor()->getMock();

        $this->rateResultFactory->expects($this->any())->method('create')
            ->willReturn($this->rateResult);
        $this->rateMethodFactory->expects($this->any())->method('create')
            ->willReturn($this->rateResultMethod);

        $this->model = new Carrier(
            $this->scopeConfig,
            $rateErrorFactory,
            $logger,
            $this->rateResultFactory,
            $this->rateMethodFactory
        );
    }

    /**
     * TestGetAllowedMethods
     *
     * @return $this
     */
    public function testGetAllowedMethods()
    {
        $name = 'In-Store Pickup';
        $code = 'riki_shipping';
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->willReturn($name);

        $methods = $this->model->getAllowedMethods();
        $this->assertArrayHasKey($code, $methods);
        $this->assertEquals($name, $methods[$code]);
    }

    /**
     * TestCollectRatesNotActive
     *
     * @return $this
     */
    public function testCollectRatesNotActive()
    {
        /* @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->getMockBuilder(
            'Magento\Quote\Model\Quote\Address\RateRequest'
        )->disableOriginalConstructor()->getMock();

        $this->mockIsActive(false);
        $this->assertFalse($this->model->collectRates($request));
    }

    /**
     * TestCollectRates
     *
     * @return $this
     */
    public function testCollectRates()
    {
        /* @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->getMockBuilder(
            'Magento\Quote\Model\Quote\Address\RateRequest'
        )->disableOriginalConstructor()->getMock();

        $this->mockIsActive(true);
        $this->mockGetLocations();
        $this->mockBuildRateForLocation();

        $this->rateResult->expects($this->once())->method('append')
            ->with($this->rateResultMethod);

        $result = $this->model->collectRates($request);
        $this->assertInstanceOf('Magento\Shipping\Model\Rate\Result', $result);
    }

    /**
     * MockIsActive
     *
     * @param mix $result result
     *
     * @return $this
     */
    protected function mockIsActive($result)
    {
        $this->scopeConfig
            ->expects($this->at(0))
            ->method('getValue')
            ->with('carriers/riki_shipping/active')
            ->willReturn($result);
    }

    /**
     * MockIsActive
     *
     * @return $this
     */
    protected function mockGetLocations()
    {
        $this->scopeConfig
            ->expects($this->at(1))
            ->method('getValue')
            ->with('carriers/riki_shipping/store_locations')
            ->willReturn(serialize([$this->locationData]));
        $this->mockGetShippingOrigin();
    }

    /**
     * MockGetShippingOrigin
     *
     * @return $this
     */
    protected function mockGetShippingOrigin()
    {
        $this->scopeConfig
            ->expects($this->at(2))
            ->method('getValue')
            ->willReturn($this->shippingOrigin['country_id']);
        $this->scopeConfig
            ->expects($this->at(3))
            ->method('getValue')
            ->willReturn($this->shippingOrigin['region_id']);
        $this->scopeConfig
            ->expects($this->at(4))
            ->method('getValue')
            ->willReturn($this->shippingOrigin['postcode']);
        $this->scopeConfig
            ->expects($this->at(5))
            ->method('getValue')
            ->willReturn($this->shippingOrigin['city']);
    }

    /**
     * MockBuildRateForLocation
     *
     * @return $this
     */
    protected function mockBuildRateForLocation()
    {
        $this->rateResultMethod->expects($this->at(0))->method('setData')
            ->with('carrier', 'riki_shipping');
        $this->scopeConfig
            ->expects($this->at(6))
            ->method('getValue')
            ->willReturn('In-Store Pickup');
        $this->rateResultMethod->expects($this->at(1))->method('setData')
            ->with('carrier_title', 'In-Store Pickup');
        $this->rateResultMethod->expects($this->at(2))->method('setData')
            ->with(
                'method_title',
                '4895 Norman Street, 
                Los Angeles, US, 90014 (Open hours 8:00-21:00)'
            );
        $this->rateResultMethod->expects($this->at(3))->method('setData')
            ->with('method', 'store_1');
        $this->rateResultMethod->expects($this->once())->method('setPrice')
            ->with(10);
        $this->rateResultMethod->expects($this->at(5))->method('setData')
            ->with('cost', 10);
    }
}
