<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Helper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Directory\Model\Country | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMock;

    /**
     * @var \Magento\Directory\Model\Region | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $regionMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Framework\Url\EncoderInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlEncoderMock;

    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $model;

    protected function setUp()
    {
        $this->countryMock = $this->createMock(\Magento\Directory\Model\Country::class);
        $this->regionMock = $this->createPartialMock(
            \Magento\Directory\Model\Region::class,
            ['load', 'getCode', 'getName', '__wakeup']
        );

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $className = \Magento\Rma\Helper\Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        $this->storeManagerMock = $arguments['storeManager'];
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->urlEncoderMock = $context->getUrlEncoder();
        $this->scopeConfigMock = $context->getScopeConfig();
        $countryFactoryMock = $arguments['countryFactory'];
        $countryFactoryMock->expects($this->any())->method('create')->will($this->returnValue($this->countryMock));
        $regionFactoryMock = $arguments['regionFactory'];
        $regionFactoryMock->expects($this->any())->method('create')->will($this->returnValue($this->regionMock));
        $this->model = $objectManagerHelper->getObject($className, $arguments);
    }

    /**
     * @dataProvider getReturnAddressDataProvider
     */
    public function testGetReturnAddressData($useStoreAddress, $scopeConfigData, $mockConfig, $expectedResult)
    {
        $this->scopeConfigMock->expects(
            $this->atLeastOnce()
        )->method(
            'isSetFlag'
        )->with(
            \Magento\Rma\Model\Rma::XML_PATH_USE_STORE_ADDRESS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $mockConfig['store_id']
        )->will(
            $this->returnValue($useStoreAddress)
        );

        $this->scopeConfigMock->expects(
            $this->atLeastOnce()
        )->method(
            'getValue'
        )->will(
            $this->returnValueMap($scopeConfigData)
        );

        $this->countryMock->expects($this->any())->method('loadByCode')->will($this->returnSelf());
        $this->countryMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($mockConfig['country_name']));

        $this->regionMock->expects($this->any())->method('load')->will($this->returnSelf());
        $this->regionMock->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($mockConfig['region_id']));
        $this->regionMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($mockConfig['region_name']));

        $this->assertEquals($this->model->getReturnAddressData($mockConfig['store_id']), $expectedResult);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getReturnAddressDataProvider()
    {
        return [
            [
                true,
                [
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_CITY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'AF'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ZIP,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_REGION_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS2,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS1,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => 'AF',
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => 'Afghanistan',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ],
            ],
            [
                false,
                [
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_CITY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_COUNTRY_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'AF'
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_ZIP,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_REGION_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_ADDRESS2,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_ADDRESS1,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ],
                    [
                        \Magento\Rma\Model\Shipping::XML_PATH_CONTACT_NAME,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Hafizullah Amin'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => 'AF',
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => 'Afghanistan',
                    'firstname' => 'Hafizullah Amin',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ]
            ],
            [
                true,
                [
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_CITY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        null
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ZIP,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_REGION_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS2,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS1,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => null,
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => '',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ]
            ]
        ];
    }

    /**
     * @dataProvider trackProvider
     *
     * @param string $className
     * @param string $key
     * @param string $method
     */
    public function testGetTrackingPopupUrlBySalesModel($className, $key, $method)
    {
        $hash = 'hash';
        $params = [
            '_direct' => 'rma/tracking/popup',
            '_query' => ['hash' => $hash]
        ];
        $url = 'url';

        $trackMock = $this->createPartialMock($className, ['getProtectCode', 'getId', 'getStoreId', 'getEntityId']);

        $methodResult = 'method result';
        $protectCode = 'protect code';

        $trackMock->expects($this->once())
            ->method($method)
            ->willReturn($methodResult);
        $trackMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectCode);

        $this->urlEncoderMock->expects($this->once())
            ->method('encode')
            ->with("{$key}:{$methodResult}:{$protectCode}")
            ->willReturn($hash);

        $storeModelMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeModelMock->expects($this->once())
            ->method('getUrl')
            ->with('', $params)
            ->willReturn($url);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeModelMock);

        $this->assertEquals($url, $this->model->getTrackingPopupUrlBySalesModel($trackMock));
    }

    public function trackProvider()
    {
        return [
            [\Magento\Rma\Model\Rma::class, 'rma_id', 'getId'],
            [\Magento\Rma\Model\Shipping::class, 'track_id', 'getEntityId']
        ];
    }
}
