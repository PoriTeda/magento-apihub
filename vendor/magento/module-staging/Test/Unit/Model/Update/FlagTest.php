<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Update;

/**
 * Class FlagTest
 */
class FlagTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Model\Update\Flag
     */
    private $flag;

    protected function setUp()
    {
        $data = ['flag_code' => 'synchronize'];
        $this->createInstance($data);
    }

    private function createInstance(array $data = [])
    {
        $eventManager = $this->createPartialMock(\Magento\Framework\Event\Manager::class, ['dispatch']);
        $context = $this->createMock(\Magento\Framework\Model\Context::class);
        $context->expects($this->once())
            ->method('getEventDispatcher')
            ->will($this->returnValue($eventManager));
        $registry = $this->createMock(\Magento\Framework\Registry::class);

        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->setMethods(['beginTransaction'])
            ->getMockForAbstractClass();

        $connection->expects($this->any())
            ->method('beginTransaction')
            ->will($this->returnSelf());
        $appResource = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $appResource->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $dbContextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $dbContextMock->expects($this->once())->method('getResources')->willReturn($appResource);
        $resource = $this->getMockBuilder(\Magento\Framework\Flag\FlagResource::class)
            ->setMethods(['__wakeup', 'load', 'save', 'addCommitCallback', 'commit', 'rollBack'])
            ->setConstructorArgs(['context' => $dbContextMock])
            ->getMock();
        $resource->expects($this->any())
            ->method('addCommitCallback')
            ->will($this->returnSelf());

        $resourceCollection = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $json = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(null)
            ->getMock();

        $serialize = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Serialize::class)
            ->setMethods(null)
            ->getMock();

        $this->flag = new \Magento\Staging\Model\Update\Flag(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data,
            $json,
            $serialize
        );
    }

    public function tearDown()
    {
        unset($this->flag);
    }

    public function testPersistCurrentVersionId()
    {
        $versionId = 22;
        $this->flag->setCurrentVersionId($versionId);
        $this->assertEquals(['current_version' => $versionId], $this->flag->getFlagData());
    }

    public function testPersistMaxVersionInDb()
    {
        $maxVersionInDb = 5;
        $this->flag->setMaximumVersionsInDb($maxVersionInDb);
        $this->assertEquals(['maximum_versions_in_db' => $maxVersionInDb], $this->flag->getFlagData());
    }

    public function testRetrieveCurrentVersionId()
    {
        $versionId = 22;
        $this->flag->setFlagData(['current_version' => $versionId]);
        $this->assertEquals($versionId, $this->flag->getCurrentVersionId());
    }

    public function testRetrieveMaxVersionInDb()
    {
        $maxVersionInDb = 5;
        $this->flag->setFlagData(['maximum_versions_in_db' => $maxVersionInDb]);
        $this->assertEquals($maxVersionInDb, $this->flag->getMaximumVersionsInDb());
    }
}
