<?php
/**
 * Point Helper
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentExporter\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory as RewardCollection;
use Riki\Loyalty\Model\Reward;
use Magento\Framework\App\Helper\Context;
use Magento\GiftWrapping\Model\WrappingRepository;
/**
 * Class Point Helper
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Point extends AbstractHelper
{
    /**
     * @var RewardCollection
     */
    protected $rewardCollection;
    /**
     * @var WrappingRepository
     */
    protected $giftwrappingRepository;
    /**
     * Point constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        Context $context,
        RewardCollection $collection,
        WrappingRepository $wrappingRepository

    )
    {
        parent::__construct($context);
        $this->rewardCollection = $collection;
        $this->giftwrappingRepository = $wrappingRepository;
    }

    /**
     * @param $orderItemIds
     * @return int
     */
    public function getEarntPoint($orderItemIds)
    {
        $collectionPoint = $this->rewardCollection->create()
                            ->addFieldToFilter('order_item_id',array('in'=>$orderItemIds))
                            ->addFieldToFilter('status',Reward::STATUS_SHOPPING_POINT);
        $totalPoint = 0;
        if($collectionPoint->getSize()) {
            foreach($collectionPoint as $point)
            {
                $totalPoint += $point->getPoint();
            }

        }
        return $totalPoint;
    }

    /**
     * @param $wrappingId
     * @throws \Exception
     */
    public function getGiftWrapping($wrappingId)
    {
        try {
            $this->giftwrappingRepository->get($wrappingId);
        }
        catch(\Exception $e)
        {
            throw $e;
        }

    }
}