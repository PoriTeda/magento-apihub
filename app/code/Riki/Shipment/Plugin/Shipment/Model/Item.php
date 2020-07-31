<?php
/**
 * Shipment Item plugin
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Shipment\Plugin\Shipment\Model;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * Class Item
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Item
{
    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * Item constructor.
     *
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment\Item $subject
     * @return array
     */
    public function beforeBeforeSave(\Magento\Sales\Model\Order\Shipment\Item $subject)
    {
        if ($subject->getId()) {
            return [];
        }
        try {
            $orderItem = $this->orderItemRepository->get($subject->getOrderItemId());
            $subject->setData('free_of_charge', $orderItem->getData('free_of_charge'));
            $subject->setData('booking_wbs', $orderItem->getData('booking_wbs'));
            $subject->setData('free_delivery_wbs', $orderItem->getData('free_delivery_wbs'));
        } catch(\Exception $e) {
            return [];
        }
    }
}