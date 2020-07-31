<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Setup;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Support\Setup\ReportConverter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ReportConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonMock;

    /**
     * @var ReportConverter
     */
    private $serializedReportToJson;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->getMock();
        $this->serializedReportToJson = $objectManager->getObject(
            ReportConverter::class,
            [
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * Test report data converter
     *
     * @return void
     */
    public function testConvert()
    {
        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $data = new \Magento\Framework\Phrase('text');

        $serializedData = serialize(['data' => $data]);
        $jsonData = json_encode(['data' => $data]);

        $this->assertEquals($jsonData, $this->serializedReportToJson->convert($serializedData));
    }

    /**
     * @expectedException \Magento\Framework\DB\DataConverter\DataConversionException
     */
    public function testConvertCorruptedData()
    {
        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $serialized = 'O:8:"stdClass":1:{s:8:"property"';
        $this->serializedReportToJson->convert($serialized);
    }

    /**
     * Test skipping deserialization and json_encoding of valid JSON encoded string
     */
    public function testSkipJsonDataConversion()
    {
        $serialized = '{"property":1}';
        $this->jsonMock->expects($this->never())->method('serialize');
        $this->serializedReportToJson->convert($serialized);
    }
}
