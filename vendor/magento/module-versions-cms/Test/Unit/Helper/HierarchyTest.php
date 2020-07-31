<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\Store\Model\ScopeInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;

class HierarchyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Hierarchy
     */
    protected $hierarchy;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ScopeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $context = $this->objectManagerHelper->getObject(
            \Magento\Framework\App\Helper\Context::class,
            ['scopeConfig' => $this->scopeConfigMock]
        );

        $this->hierarchy = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Helper\Hierarchy::class,
            [
                'context' => $context,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * @param bool $value
     * @return void
     * @dataProvider boolDataProvider
     */
    public function testIsEnabled($value)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(Hierarchy::XML_PATH_HIERARCHY_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        $this->assertSame($value, $this->hierarchy->isEnabled());
    }

    /**
     * @param bool $value
     * @return void
     * @dataProvider boolDataProvider
     */
    public function testIsMetadataEnabled($value)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(Hierarchy::XML_PATH_METADATA_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        $this->assertSame($value, $this->hierarchy->isMetadataEnabled());
    }

    /**
     * @return array
     */
    public function boolDataProvider()
    {
        return [[false], [true]];
    }

    /**
     * @return void
     */
    public function testGetMetadataFields()
    {
        $expectedResult = [
            'meta_first_last',
            'meta_next_previous',
            'meta_chapter',
            'meta_section',
            'meta_cs_enabled',
            'pager_visibility',
            'pager_frame',
            'pager_jump',
            'menu_visibility',
            'menu_layout',
            'menu_brief',
            'menu_excluded',
            'menu_levels_down',
            'menu_ordered',
            'menu_list_type',
            'top_menu_visibility',
            'top_menu_excluded'
        ];

        $this->assertSame($expectedResult, $this->hierarchy->getMetadataFields());
    }

    /**
     * @param mixed $source
     * @param array $target
     * @param bool $metaDataEnabled
     * @param array $expectedResult
     * @return void
     * @dataProvider copyMetaDataDataProvider
     */
    public function testCopyMetaData($source, $target, $metaDataEnabled, $expectedResult)
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('isSetFlag')
            ->with(Hierarchy::XML_PATH_METADATA_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($metaDataEnabled);

        $this->assertEquals($expectedResult, $this->hierarchy->copyMetaData($source, $target));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function copyMetaDataDataProvider()
    {
        return [
            [
                'source' => 'some string',
                'target' => ['some value'],
                'metaDataEnabled' => true,
                'expectedResult' => ['some value']
            ],
            [
                'source' => [
                    'pager_visibility' => Hierarchy::METADATA_VISIBILITY_PARENT
                ],
                'target' => [],
                'metaDataEnabled' => false,
                'expectedResult' => [
                    'pager_visibility' => Hierarchy::METADATA_VISIBILITY_PARENT,
                    'pager_frame' => '0',
                    'pager_jump' => '0'
                ]
            ],
            [
                'source' => [
                    'pager_visibility' => Hierarchy::METADATA_VISIBILITY_NO
                ],
                'target' => [],
                'metaDataEnabled' => false,
                'expectedResult' => [
                    'pager_visibility' => Hierarchy::METADATA_VISIBILITY_NO,
                    'pager_frame' => '0',
                    'pager_jump' => '0',
                ]
            ],
            [
                'source' => [
                    'menu_visibility' => Hierarchy::METADATA_VISIBILITY_NO
                ],
                'target' => [],
                'metaDataEnabled' => false,
                'expectedResult' => [
                    'menu_visibility' => Hierarchy::METADATA_VISIBILITY_NO
                ]
            ],
            [
                'source' => [
                    'menu_visibility' => Hierarchy::METADATA_VISIBILITY_PARENT
                ],
                'target' => [],
                'metaDataEnabled' => false,
                'expectedResult' => [
                    'menu_visibility' => Hierarchy::METADATA_VISIBILITY_PARENT,
                    'menu_layout' => '',
                    'menu_brief' => '0',
                    'menu_levels_down' => '0',
                    'menu_ordered' => '0',
                    'menu_list_type' => ''
                ]
            ],
            [
                'source' => [
                    'meta_first_last' => 0,
                    'meta_next_previous' => 0,
                    'meta_chapter' => 0,
                    'meta_section' => 0,
                    'meta_cs_enabled' => 0,
                    'pager_visibility' => 0,
                    'pager_frame' => 0,
                    'pager_jump' => 0,
                    'menu_visibility' => 0,
                    'menu_layout' => '',
                    'menu_brief' => 0,
                    'menu_excluded' => 0,
                    'menu_levels_down' => 0,
                    'menu_ordered' => 0,
                    'menu_list_type' => '',
                    'top_menu_visibility' => 0,
                    'top_menu_excluded' => 0
                ],
                'target' => ['some_value' => 1],
                'metaDataEnabled' => true,
                'expectedResult' => [
                    'some_value' => 1,
                    'meta_first_last' => 0,
                    'meta_next_previous' => 0,
                    'meta_chapter' => 0,
                    'meta_section' => 0,
                    'meta_cs_enabled' => 0,
                    'pager_visibility' => 0,
                    'pager_frame' => 0,
                    'pager_jump' => 0,
                    'menu_visibility' => 0,
                    'menu_layout' => '',
                    'menu_brief' => 0,
                    'menu_excluded' => 0,
                    'menu_levels_down' => 0,
                    'menu_ordered' => 0,
                    'menu_list_type' => '',
                    'top_menu_visibility' => 0,
                    'top_menu_excluded' => 0
                ]
            ],
            [
                'source' => [
                    'meta_first_last' => 0,
                    'meta_next_previous' => 0,
                    'meta_chapter' => 0,
                    'meta_section' => 0,
                    'meta_cs_enabled' => 0,
                    'pager_visibility' => 0,
                    'pager_frame' => 0,
                    'pager_jump' => 0,
                    'menu_visibility' => 0,
                    'menu_layout' => '',
                    'menu_brief' => 0,
                    'menu_excluded' => 0,
                    'menu_levels_down' => 0,
                    'menu_ordered' => 0,
                    'menu_list_type' => '',
                    'top_menu_visibility' => 0,
                    'top_menu_excluded' => 0
                ],
                'target' => ['some_value' => 1],
                'metaDataEnabled' => false,
                'expectedResult' => [
                    'some_value' => 1,
                    'pager_visibility' => 0,
                    'pager_frame' => 0,
                    'pager_jump' => 0,
                    'menu_visibility' => 0,
                    'menu_layout' => '',
                    'menu_brief' => 0,
                    'menu_excluded' => 0,
                    'menu_levels_down' => 0,
                    'menu_ordered' => 0,
                    'menu_list_type' => '',
                    'top_menu_visibility' => 0,
                    'top_menu_excluded' => 0
                ]
            ]
        ];
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @param array|null $expectedResult
     * @return void
     * @dataProvider getParentScopeDataProvider
     */
    public function testGetParentScope($scope, $scopeId, $expectedResult)
    {
        /** @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject $storeMock */
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->with($scopeId)
            ->willReturn($storeMock);

        $this->assertSame($expectedResult, $this->hierarchy->getParentScope($scope, $scopeId));
    }

    /**
     * @return array
     */
    public function getParentScopeDataProvider()
    {
        return [
            [
                'scope' => 'someScope',
                'scopeId' => 2,
                'expectedResult' => null
            ],
            [
                'scope' => Node::NODE_SCOPE_STORE,
                'scopeId' => 2,
                'expectedResult' => [
                    Node::NODE_SCOPE_WEBSITE,
                    1
                ]
            ],
            [
                'scope' => Node::NODE_SCOPE_WEBSITE,
                'scopeId' => 2,
                'expectedResult' => [
                    Node::NODE_SCOPE_DEFAULT,
                    Node::NODE_SCOPE_DEFAULT_ID
                ]
            ]
        ];
    }
}
