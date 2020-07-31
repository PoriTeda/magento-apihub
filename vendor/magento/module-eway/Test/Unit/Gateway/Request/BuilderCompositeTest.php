<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\BuilderComposite;
use Magento\Framework\ObjectManager\TMap;
use Magento\Framework\ObjectManager\TMapFactory;
use Magento\Payment\Gateway\Request\BuilderInterface;

class BuilderCompositeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BuilderComposite
     */
    private $builder;

    /**
     * @var TMapFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tMapFactoryMock;

    /**
     * @var TMap|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tMapMock;

    /**
     * @var BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerBuilderMock;

    /**
     * @var BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cardBuilderMock;

    protected function setUp()
    {
        $this->tMapFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\ObjectManager\TMapFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->tMapMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\TMap::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerBuilderMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();

        $this->cardBuilderMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();
    }

    public function testBuildEmpty()
    {
        $this->tMapFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with(
                [
                    'array' => [],
                    'type' => BuilderInterface::class
                ]
            )
            ->willReturn($this->tMapMock);
        $this->tMapMock
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $this->builder = new BuilderComposite($this->tMapFactoryMock, []);
        $this->assertEquals([], $this->builder->build([]));
    }

    /**
     * @param array $customer
     * @param array $card
     * @param array $expectedRequest
     * @dataProvider buildDataProvider
     */
    public function testBuild($customer, $card, $expectedRequest)
    {
        $this->customerBuilderMock
            ->expects($this->once())
            ->method('build')
            ->willReturn($customer);
        $this->cardBuilderMock
            ->expects($this->once())
            ->method('build')
            ->willReturn($card);
        $this->tMapFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with(
                [
                    'array' => [
                        'customer' => \Magento\Payment\Gateway\Request\BuilderInterface::class,
                        'card' => \Magento\Payment\Gateway\Request\BuilderInterface::class,
                    ],
                    'type' => BuilderInterface::class
                ]
            )
            ->willReturn($this->tMapMock);
        $this->tMapMock
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->customerBuilderMock, $this->cardBuilderMock]));

        $this->builder = new BuilderComposite(
            $this->tMapFactoryMock,
            [
                'customer' => \Magento\Payment\Gateway\Request\BuilderInterface::class,
                'card' => \Magento\Payment\Gateway\Request\BuilderInterface::class,
            ]
        );
        $this->assertEquals($expectedRequest, $this->builder->build([]));
    }

    /**
     * 1) Usual behavior
     * 2) array_replace_recursive card number replacement
     *
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                [
                    'Customer' => [
                        'FirstName' => 'John',
                        'LastName' => 'Smith'
                    ]
                ],
                [
                    'Customer' => [
                        'CardDetails' => [
                            'Name' => 'John Smith',
                            'Number' => '4444333322221111',
                            'CVN' => '123'
                        ]
                    ]
                ],
                [
                    'Customer' => [
                        'FirstName' => 'John',
                        'LastName' => 'Smith',
                        'CardDetails' => [
                            'Name' => 'John Smith',
                            'Number' => '4444333322221111',
                            'CVN' => '123'
                        ]
                    ]
                ]
            ],
            [
                [
                    'Customer' => [
                        'FirstName' => 'John',
                        'LastName' => 'Smith',
                        'CardDetails' => [
                            'Number' => '4444333322221111',
                        ]
                    ]
                ],
                [
                    'Customer' => [
                        'CardDetails' => [
                            'Name' => 'John Smith',
                            'Number' => '5105105105105100',
                            'CVN' => '123'
                        ]
                    ]
                ],
                [
                    'Customer' => [
                        'FirstName' => 'John',
                        'LastName' => 'Smith',
                        'CardDetails' => [
                            'Name' => 'John Smith',
                            'Number' => '5105105105105100',
                            'CVN' => '123'
                        ]
                    ]
                ]
            ]
        ];
    }
}
