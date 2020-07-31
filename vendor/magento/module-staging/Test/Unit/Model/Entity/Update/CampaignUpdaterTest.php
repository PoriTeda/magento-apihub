<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Update\CampaignUpdater;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;

class CampaignUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IncludesRetriever|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $includesRetriever;

    /**
     * @var UpdateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateRepository;

    /**
     * @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $update;

    /**
     * @var CampaignUpdater
     */
    protected $campaignUpdater;

    public function setUp()
    {
        $this->includesRetriever = $this->getMockBuilder(\Magento\Staging\Model\Update\Includes\Retriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(\Magento\Staging\Model\UpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->update = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->campaignUpdater = new CampaignUpdater($this->includesRetriever, $this->updateRepository);
    }

    public function testUpdateCampaignStatus()
    {
        $this->update->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->includesRetriever->expects($this->once())
            ->method('getIncludes')
            ->with([1])
            ->willReturn([
                [
                    'includes' => 1,
                ],
                [
                    'includes' => 1,
                ],
            ]);
        $this->update->expects($this->once())
            ->method('setIsCampaign')
            ->with(true);
        $this->updateRepository->expects($this->once())
            ->method('save')
            ->with($this->update);
        $this->campaignUpdater->updateCampaignStatus($this->update);
    }
}
