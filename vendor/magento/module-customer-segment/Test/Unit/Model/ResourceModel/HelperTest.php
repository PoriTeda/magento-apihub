<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\ResourceModel;

class HelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helper;

    protected function setUp()
    {
        $this->resourceMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new \Magento\CustomerSegment\Model\ResourceModel\Helper(
            $this->resourceMock,
            ''
        );
    }

    /**
     * Check getSqlOperator() for all allowed operators
     *
     * @param string $operator
     * @param string $expected
     * @dataProvider dataProviderGetSqlOperator
     * @return void
     */
    public function testGetSqlOperator(
        $operator,
        $expected
    ) {
        $this->assertEquals($expected, $this->helper->getSqlOperator($operator));
    }

    /**
     * Data provider for testGetSqlOperator test case
     *
     * @return array
     */
    public function dataProviderGetSqlOperator()
    {
        return [
            [
                'operator' => '==',
                'expected' => '=',
            ],
            [
                'operator' => '!=',
                'expected' => '<>',
            ],
            [
                'operator' => '{}',
                'expected' => 'LIKE',
            ],
            [
                'operator' => '!{}',
                'expected' => 'NOT LIKE',
            ],
            [
                'operator' => '()',
                'expected' => 'IN',
            ],
            [
                'operator' => '!()',
                'expected' => 'NOT IN',
            ],
            [
                'operator' => '[]',
                'expected' => 'FIND_IN_SET(%s, %s)',
            ],
            [
                'operator' => '![]',
                'expected' => 'FIND_IN_SET(%s, %s) IS NULL',
            ],
            [
                'operator' => 'between',
                'expected' => 'BETWEEN %s AND %s',
            ],
            [
                'operator' => 'finset',
                'expected' => 'finset',
            ],
            [
                'operator' => '!finset',
                'expected' => '!finset',
            ],
            [
                'operator' => '>',
                'expected' => '>',
            ],
            [
                'operator' => '<',
                'expected' => '<',
            ],
            [
                'operator' => '>=',
                'expected' => '>=',
            ],
            [
                'operator' => '<=',
                'expected' => '<=',
            ],
        ];
    }

    /**
     * Check getSqlOperator() method in case when operator is not allowed
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @exceptionMessage Unknown operator specified
     */
    public function testGetSqlOperatorWithException()
    {
        $this->helper->getSqlOperator('.');
    }
}
