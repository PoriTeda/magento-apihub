<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\GiftCard\Api\Data\GiftCardOptionInterface;
use Magento\GiftCard\Model\Giftcard\OptionFactory as GiftcardOptionFactory;
use Magento\GiftCard\Model\ProductOptionProcessor;

class ProductOptionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductOptionProcessor
     */
    protected $processor;

    /**
     * @var DataObject | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObject;

    /**
     * @var DataObjectFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObjectFactory;

    /**
     * @var DataObjectHelper | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObjectHelper;

    /**
     * @var GiftcardOptionFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftcardOptionFactory;

    /**
     * @var GiftCardOptionInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftcardOption;

    protected function setUp()
    {
        $this->dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(
                [
                    'getData',
                    'addData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactory = $this->getMockBuilder(\Magento\Framework\DataObject\Factory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->dataObject);

        $this->dataObjectHelper = $this->getMockBuilder(\Magento\Framework\Api\DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftcardOption = $this->getMockBuilder(
            \Magento\GiftCard\Api\Data\GiftCardOptionInterface::class
        )
            ->setMethods([
                'getData',
            ])
            ->getMockForAbstractClass();

        $this->giftcardOptionFactory = $this->getMockBuilder(
            \Magento\GiftCard\Model\Giftcard\OptionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftcardOptionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->giftcardOption);

        $this->processor = new ProductOptionProcessor(
            $this->dataObjectFactory,
            $this->dataObjectHelper,
            $this->giftcardOptionFactory
        );
    }

    /**
     * @param array|string $options
     * @param array $requestData
     * @dataProvider dataProviderConvertToBuyRequest
     */
    public function testConvertToBuyRequest(
        $options,
        $requestData
    ) {
        $productOptionMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductOptionInterface::class)
            ->getMockForAbstractClass();

        $productOptionExtensionMock = $this->getMockBuilder(
            \Magento\Catalog\Api\Data\ProductOptionExtensionInterface::class
        )
            ->setMethods([
                'getGiftcardItemOption',
            ])
            ->getMockForAbstractClass();

        $productOptionMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($productOptionExtensionMock);

        $productOptionExtensionMock->expects($this->any())
            ->method('getGiftcardItemOption')
            ->willReturn($this->giftcardOption);

        $this->giftcardOption->expects($this->any())
            ->method('getData')
            ->willReturn($options);

        $this->dataObject->expects($this->any())
            ->method('addData')
            ->with($requestData)
            ->willReturnSelf();

        $this->assertEquals($this->dataObject, $this->processor->convertToBuyRequest($productOptionMock));
    }

    /**
     * @return array
     */
    public function dataProviderConvertToBuyRequest()
    {
        return [
            [
                ['option'],
                ['option'],
            ],
            [[], []],
            ['', []],
        ];
    }

    /**
     * @param array|string $options
     * @param string|null $expected
     * @dataProvider dataProviderConvertToProductOption
     */
    public function testConvertToProductOption(
        $options,
        $expected
    ) {
        if (!empty($options) && is_array($options)) {
            $this->dataObject->expects($this->any())
                ->method('getData')
                ->willReturnMap([
                    ['giftcard_amount', null, $options['giftcard_amount']],
                    ['giftcard_sender_name', null, $options['giftcard_sender_name']],
                    ['giftcard_recipient_name', null, $options['giftcard_recipient_name']],
                    ['giftcard_sender_email', null, $options['giftcard_sender_email']],
                    ['giftcard_recipient_email', null, $options['giftcard_recipient_email']],
                    ['giftcard_message', null, $options['giftcard_message']],
                ]);
        } else {
            $this->dataObject->expects($this->any())
                ->method('getData')
                ->willReturnMap([
                    ['giftcard_amount', null, null],
                    ['giftcard_sender_name', null, null],
                    ['giftcard_recipient_name', null, null],
                    ['giftcard_sender_email', null, null],
                    ['giftcard_recipient_email', null, null],
                    ['giftcard_message', null, null],
                ]);
        }

        $this->dataObjectHelper->expects($this->any())
            ->method('populateWithArray')
            ->with(
                $this->giftcardOption,
                $options,
                \Magento\GiftCard\Api\Data\GiftCardOptionInterface::class
            )
            ->willReturnSelf();

        $result = $this->processor->convertToProductOption($this->dataObject);

        if (!empty($expected)) {
            $this->assertArrayHasKey($expected, $result);
            $this->assertSame($this->giftcardOption, $result[$expected]);
        } else {
            $this->assertEmpty($result);
        }
    }

    /**
     * @return array
     */
    public function dataProviderConvertToProductOption()
    {
        return [
            [
                'options' => [
                    'giftcard_amount' => 1,
                    'giftcard_sender_name' => 'sender',
                    'giftcard_recipient_name' => 'recipient',
                    'giftcard_sender_email' => 'sender@example.com',
                    'giftcard_recipient_email' => 'recipient@example.com',
                    'giftcard_message' => 'message',
                ],
                'expected' => 'giftcard_item_option',
            ],
            [
                'options' => [],
                'expected' => null,
            ],
            [
                'options' => 'is not array',
                'expected' => null,
            ],
        ];
    }
}
