<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Config;

class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \DOMDocument
     */
    protected $source;

    /**
     * @var \Magento\Support\Model\Report\Config\Converter
     */
    protected $converter;

    /**
     * @var string
     */
    protected $configDir;

    protected function setUp()
    {
        $this->source = new \DOMDocument();

        /** @var \Magento\Support\Model\Report\Config\Converter $converter */
        $this->converter = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))
            ->getObject(\Magento\Support\Model\Report\Config\Converter::class);

        $this->configDir = realpath(__DIR__) . DIRECTORY_SEPARATOR . '_files/';
    }

    public function testConvertValidShouldReturnArray()
    {
        $expected = [
            'groups' => [
                'general' => [
                    'title' => __('General'),
                    'sections' => [
                        40 => \Magento\Support\Model\Report\Group\General\VersionSection::class,
                        50 => \Magento\Support\Model\Report\Group\General\DataCountSection::class
                    ],
                    'priority' => 10,
                    'data' => [
                        \Magento\Support\Model\Report\Group\General\VersionSection::class => [],
                        \Magento\Support\Model\Report\Group\General\DataCountSection::class => []
                    ]
                ],
                'environment' => [
                    'title' => __('Environment'),
                    'sections' => [
                        410 => \Magento\Support\Model\Report\Group\Environment\EnvironmentSection::class
                    ],
                    'priority' => 30,
                    'data' => [\Magento\Support\Model\Report\Group\Environment\EnvironmentSection::class => []
                    ]
                ]
            ]
        ];
        $this->source->load($this->configDir . 'report_valid.xml');
        $this->assertEquals($expected, $this->converter->convert($this->source));
    }

    /**
     * @param string $file
     * @param string $message
     * @dataProvider convertInvalidArgumentsDataProvider
     */
    public function testConvertInvalidArgumentsShouldThrowException($file, $message)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage($message);
        $this->source->load($this->configDir . $file);
        $result = $this->converter->convert($this->source);
        $this->assertNotNull($result);
    }

    /**
     * @return array
     */
    public function convertInvalidArgumentsDataProvider()
    {
        return [
            ['report_absent_name.xml', 'Attribute "name" of one of "group"s does not exist'],
            ['report_absent_sections.xml', 'Tag "sections" of one of "group"s does not exist']
        ];
    }
}
