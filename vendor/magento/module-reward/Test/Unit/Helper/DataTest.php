<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Reward\Helper\Data
     */
    protected $subject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $className = \Magento\Reward\Helper\Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->storeManagerMock = $arguments['storeManager'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->websiteMock = $this->createMock(\Magento\Store\Model\Website::class);

        $this->subject = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testIsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(\Magento\Reward\Helper\Data::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertTrue($this->subject->isEnabled());
    }

    public function testGetConfigValue()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = 'section';
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getConfigValue($section, $field, $websiteId));
    }

    public function testGetGeneralConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = \Magento\Reward\Helper\Data::XML_PATH_SECTION_GENERAL;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getGeneralConfig($field, $websiteId));
    }

    public function testGetPointsConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = \Magento\Reward\Helper\Data::XML_PATH_SECTION_POINTS;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getPointsConfig($field, $websiteId));
    }

    public function testGetNotificationConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = \Magento\Reward\Helper\Data::XML_PATH_SECTION_NOTIFICATIONS;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getNotificationConfig($field, $websiteId));
    }

    /**
     * @param int $points
     * @param string $expectedResult
     *
     * @dataProvider formatPointsDeltaDataProvider
     */
    public function testFormatPointsDelta($points, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->formatPointsDelta($points));
    }

    /**
     * @return array
     */
    public function formatPointsDeltaDataProvider()
    {
        return [
            ['points' => -100, 'expectedResult' => '-100'],
            ['points' => 100, 'expectedResult' => '100'],
        ];
    }
}
