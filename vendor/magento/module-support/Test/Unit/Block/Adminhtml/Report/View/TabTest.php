<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class TabTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\View\Tab
     */
    protected $reportTabBlock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Report\DataConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataConverterMock;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Support\Block\Adminhtml\Report\View\Tab\Grid|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportGridBlockMock;

    protected function setUp()
    {
        $this->dataConverterMock = $this->getMockBuilder(\Magento\Support\Model\Report\DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $this->reportGridBlockMock = $this->getMockBuilder(\Magento\Support\Block\Adminhtml\Report\View\Tab\Grid::class)
            ->disableOriginalConstructor()
            ->setMethods(['setId', 'setGridData'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reportTabBlock = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\View\Tab::class,
            [
                'dataConverter' => $this->dataConverterMock
            ]
        );

        $this->reportTabBlock->setLayout($this->layoutMock);
        $this->setNewDependency();
    }

    /**
     * Set a new dependency mock object
     *
     * @deprecated
     */
    private function setNewDependency()
    {
        /**
         * @var \Magento\Framework\Encryption\EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject $encryptorMock
         */
        $encryptorMock = $this->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $encryptorMock->expects($this->any())->method('getHash')->willReturnCallback(
            function ($str) {
                return $this->getHash($str);
            }
        );

        $reflection = new \ReflectionClass(get_class($this->reportTabBlock));
        $reflectionProperty = $reflection->getProperty('encryptor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->reportTabBlock, $encryptorMock);
    }

    public function testGetGridsNoData()
    {
        $this->assertEquals([], $this->reportTabBlock->getGrids());
    }

    /**
     * @param array $inputData
     * @param array $ids
     * @param array $gridsData
     *
     * @dataProvider getGridsDataProvider
     */
    public function testGetGrids(
        array $inputData,
        array $ids,
        array $gridsData
    ) {
        $this->reportTabBlock->setData('grids_data', $inputData);

        $this->layoutMock->expects($this->any())
            ->method('createBlock')
            ->with(\Magento\Support\Block\Adminhtml\Report\View\Tab\Grid::class, '', [])
            ->willReturn($this->reportGridBlockMock);

        call_user_func_array(
            [
                $this->reportGridBlockMock->expects($this->any())
                    ->method('setId'),
                'withConsecutive'
            ],
            $ids
        )->willReturnSelf();
        call_user_func_array(
            [
                $this->reportGridBlockMock->expects($this->any())
                    ->method('setGridData'),
                'withConsecutive'
            ],
            $gridsData
        )->willReturnSelf();

        $result = $this->reportTabBlock->getGrids();
        $this->assertNotNull($result);
    }

    /**
     * @return array
     */
    public function getGridsDataProvider()
    {
        return [
            [
                'inputData' => [\Magento\Support\Model\Report\Group\General\VersionSection::class => [
                        'Magento Version' => [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 1
                        ]
                    ], \Magento\Support\Model\Report\Group\General\DataCountSection::class => [
                        'Data Count' => [
                            'error' => 'Something wrong happened'
                        ]
                    ], \Magento\Support\Model\Report\Group\General\CacheStatusSection::class => [
                        'Cache Status' => [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 11
                        ]
                    ]
                ],
                'ids' => [
                    ['grid_' . $this->getHash('Magento Version')],
                    ['grid_' . $this->getHash('Cache Status')]
                ],
                'gridsData' => [
                    [
                        [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 1
                        ]
                    ],
                    [
                        [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 11
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testGetTabLabel()
    {
        $this->assertEquals(__('Report'), $this->reportTabBlock->getTabLabel());
    }

    public function testGetTabTitle()
    {
        $this->assertEquals(__('Report'), $this->reportTabBlock->getTabTitle());
    }

    public function testCanShowTab()
    {
        $this->assertTrue($this->reportTabBlock->canShowTab());
    }

    public function testIsHidden()
    {
        $this->assertFalse($this->reportTabBlock->isHidden());
    }

    private function getHash($str)
    {
        return md5($str);
    }
}
