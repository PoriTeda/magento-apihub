<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Model\ResourceModel;

class BannerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    private $_resourceModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_resource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_eventManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_bannerConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var \Magento\Framework\DB\Select\SelectRenderer
     */
    protected $selectRenderer;

    protected function setUp()
    {
        $this->connection = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            [],
            '',
            false,
            true,
            true,
            ['getTransactionLevel', 'fetchOne', 'select', 'prepareSqlCondition', '_connect', '_quote']
        );
        $this->selectRenderer = $this->getMockBuilder(\Magento\Framework\DB\Select\SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select = new \Magento\Framework\DB\Select($this->connection, $this->selectRenderer);

        $this->connection->expects($this->once())->method('select')->will($this->returnValue($select));
        $this->connection->expects($this->any())->method('_quote')->will($this->returnArgument(0));

        $this->_resource = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->_resource->expects($this->any())->method('getTableName')->will($this->returnArgument(0));
        $this->_resource->expects(
            $this->any()
        )->method(
            'getConnection'
        )->will(
            $this->returnValue($this->connection)
        );

        $this->_eventManager = $this->createPartialMock(\Magento\Framework\Event\ManagerInterface::class, ['dispatch']);

        $this->_bannerConfig = $this->createPartialMock(\Magento\Banner\Model\Config::class, ['explodeTypes']);

        $salesruleColFactory = $this->createPartialMock(
            \Magento\Banner\Model\ResourceModel\Salesrule\CollectionFactory::class,
            ['create']
        );

        $catRuleColFactory = $this->createPartialMock(
            \Magento\Banner\Model\ResourceModel\Catalogrule\CollectionFactory::class,
            ['create']
        );

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->_resource);

        $this->_resourceModel = new \Magento\Banner\Model\ResourceModel\Banner(
            $contextMock,
            $this->_eventManager,
            $this->_bannerConfig,
            $salesruleColFactory,
            $catRuleColFactory
        );
    }

    protected function tearDown()
    {
        $this->_resourceModel = null;
        $this->_resource = null;
        $this->_eventManager = null;
        $this->_bannerConfig = null;
        $this->connection = null;
    }

    public function testGetStoreContent()
    {
        $this->connection->expects(
            $this->once()
        )->method(
            'fetchOne'
        )->withAnyParameters(
        )->will(
            $this->returnValue('Dynamic Block Contents')
        );

        $this->_eventManager->expects(
            $this->once()
        )->method(
            'dispatch'
        )->with(
            'magento_banner_resource_banner_content_select_init',
            $this->arrayHasKey('select')
        );

        $this->assertEquals('Dynamic Block Contents', $this->_resourceModel->getStoreContent(123, 5));
    }

    public function testGetStoreContentFilterByTypes()
    {
        $bannerTypes = ['content', 'footer', 'header'];
        $this->_bannerConfig->expects(
            $this->once()
        )->method(
            'explodeTypes'
        )->with(
            $bannerTypes
        )->will(
            $this->returnValue(['footer', 'header'])
        );
        $this->_resourceModel->filterByTypes($bannerTypes);

        $this->connection->expects(
            $this->exactly(2)
        )->method(
            'prepareSqlCondition'
        )->will(
            $this->returnValueMap(
                [
                    ['banner.types', ['finset' => 'footer'], 'banner.types IN ("footer")'],
                    ['banner.types', ['finset' => 'header'], 'banner.types IN ("header")'],
                ]
            )
        );
        $this->connection->expects(
            $this->once()
        )->method(
            'fetchOne'
        )->withAnyParameters(
        )->will(
            $this->returnValue('Dynamic Block Contents')
        );

        $this->_eventManager->expects(
            $this->once()
        )->method(
            'dispatch'
        )->with(
            'magento_banner_resource_banner_content_select_init',
            $this->arrayHasKey('select')
        );

        $this->assertEquals('Dynamic Block Contents', $this->_resourceModel->getStoreContent(123, 5));
    }
}
