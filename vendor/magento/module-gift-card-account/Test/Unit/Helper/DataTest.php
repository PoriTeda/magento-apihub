<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Helper data test.
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $dataObject;

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getGiftCards'])
            ->getMock();
        $serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->helper = $this->objectManager->getObject(
            \Magento\GiftCardAccount\Helper\Data::class,
            [
                'serializer' => $serializer,
            ]
        );
    }

    /**
     * @covers       \Magento\GiftCardAccount\Helper\Data::getCards()
     * @dataProvider getCardsDataProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testGetCards($value, $expected)
    {
        $this->dataObject->expects($this->once())
            ->method('getGiftCards')
            ->willReturn($value);

        $this->assertSame($expected, $this->helper->getCards($this->dataObject));
    }

    /**
     * @covers \Magento\GiftCardAccount\Helper\Data::setCards()
     * @dataProvider setCardsDataProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testSetCards($value, $expected)
    {
        $this->helper->setCards($this->dataObject, $value);

        $this->assertSame($expected, $this->dataObject->getData('gift_cards'));
    }

    /**
     * @return array
     */
    public function setCardsDataProvider()
    {
        return [
            # Variation 1
            [
                [1, 2, 3.0003],
                "[1,2,3.0003]"
            ],
            # Variation 2
            [
                [null],
                "[null]"
            ],
            # Variation 3
            [
                "text",
                "\"text\""
            ],
            # Variation 4
            [
                -999.99,
                "-999.99"
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCardsDataProvider()
    {
        return [
            # Variation 1
            [
                '[1,2,3]',
                [1, 2, 3]
            ],
            # Variation 2
            [
                '{"key":[1,2,3.0003,null]}',
                ["key" => [1, 2, 3.0003, null]]
            ],
            # Variation 3
            [
                '{"key":["text"]}',
                ["key" => ["text"]]
            ],
            # Variation 4
            [
                '{}',
                []
            ],
            # Variation 5
            [
                null,
                []
            ],
        ];
    }
}
