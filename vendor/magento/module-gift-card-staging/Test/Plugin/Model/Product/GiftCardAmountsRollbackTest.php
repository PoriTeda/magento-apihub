<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardStaging\Test\Plugin\Model\Product;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Product\SaveHandler;
use Magento\GiftCardStaging\Plugin\Model\Product\GiftCardAmountsRollback;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class GiftCardAmountsRollbackTest
 */
class GiftCardAmountsRollbackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GiftCardAmountsRollback
     */
    protected $plugin;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $versionManager;

    /**
     * @var SaveHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var string
     */
    protected $entityType = '';

    /**
     * @var ProductInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    /**
     * @var ProductExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productExtension;

    /**
     * @var \Magento\GiftCard\Model\Giftcard\Amount|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCardAmount;

    /**
     * @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $update;

    /**
     *
     */
    protected function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->subject = $this->getMockBuilder(SaveHandler::class)
            ->disableOriginalConstructor()->getMock();

        $this->entity = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getTypeId', 'getExtensionAttributes', 'getCreatedIn'])
            ->getMockForAbstractClass();

        $this->productExtension = $this->getMockBuilder(ProductExtensionInterface::class)
            ->setMethods(['getGiftcardAmounts'])
            ->getMockForAbstractClass();

        $this->giftCardAmount = $this->getMockBuilder(\Magento\GiftCard\Model\Giftcard\Amount::class)
            ->disableOriginalConstructor()->getMock();

        $this->update = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();

        $this->plugin = new GiftCardAmountsRollback($this->versionManager);
    }

    /**
     *
     */
    public function testBeforeExecuteWrongTypeId()
    {
        $this->entity->expects(static::once())
            ->method('getTypeId')
            ->willReturn('non_giftcard_type');

        $this->entity->expects(static::never())
            ->method('getExtensionAttributes');

        $this->plugin->beforeExecute(
            $this->subject,
            $this->entity
        );
    }

    /**
     *
     */
    public function testBeforeExecuteEmptyGiftCardAmounts()
    {
        $this->entity->expects(static::once())
            ->method('getTypeId')
            ->willReturn(Giftcard::TYPE_GIFTCARD);

        $this->entity->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->productExtension);

        $this->productExtension->expects(static::once())
            ->method('getGiftcardAmounts')
            ->willReturn(null);

        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion');

        $this->plugin->beforeExecute(
            $this->subject,
            $this->entity
        );
    }

    /**
     *
     */
    public function testBeforeExecuteNotIsRollback()
    {
        $this->entity->expects(static::once())
            ->method('getTypeId')
            ->willReturn(Giftcard::TYPE_GIFTCARD);

        $this->entity->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->productExtension);

        $this->productExtension->expects(static::once())
            ->method('getGiftcardAmounts')
            ->willReturn([$this->giftCardAmount]);

        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion')
            ->willReturn($this->update);

        $this->update->expects(static::once())
            ->method('getIsRollback')
            ->willReturn(false);

        $this->giftCardAmount->expects(static::never())
            ->method('setValue');

        $this->plugin->beforeExecute(
            $this->subject,
            $this->entity
        );
    }

    /**
     *
     */
    public function testBeforeExecute()
    {
        $updateId = '1496838300';
        $this->entity->expects(static::once())
            ->method('getTypeId')
            ->willReturn(Giftcard::TYPE_GIFTCARD);

        $this->entity->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->productExtension);

        $this->entity->expects(static::once())
            ->method('getCreatedIn')
            ->willReturn($updateId);

        $this->productExtension->expects(static::once())
            ->method('getGiftcardAmounts')
            ->willReturn([$this->giftCardAmount]);

        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion')
            ->willReturn($this->update);

        $this->update->expects(static::once())
            ->method('getIsRollback')
            ->willReturn(true);

        $this->update->expects(static::once())
            ->method('getId')
            ->willReturn($updateId);

        $this->giftCardAmount->expects(static::once())
            ->method('getWebsiteValue')
            ->willReturn('10');

        $this->giftCardAmount->expects(static::once())
            ->method('setValue')
            ->with('10.00');

        $this->plugin->beforeExecute(
            $this->subject,
            $this->entity
        );
    }
}
