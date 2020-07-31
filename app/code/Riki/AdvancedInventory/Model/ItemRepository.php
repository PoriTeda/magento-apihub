<?php

namespace Riki\AdvancedInventory\Model;

use Magento\Framework\Exception\NoSuchEntityException;

class ItemRepository implements \Riki\AdvancedInventory\Api\ItemRepositoryInterface
{

    /** @var \Wyomind\AdvancedInventory\Model\Item  */
    protected $itemModel;

    /** @var array  */
    protected $loaded = [];

    /**
     * ItemRepository constructor.
     * @param \Wyomind\AdvancedInventory\Model\Item $itemModel
     */
    public function __construct(
        \Wyomind\AdvancedInventory\Model\Item $itemModel
    )
    {
        $this->itemModel = $itemModel;
    }

    /**
     * @param $productId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getByProductId($productId)
    {
        if (!isset($this->loaded[$productId])) {
            $item = $this->itemModel->loadByProductId($productId);

            if ($item && $item->getId()) {
                $this->loaded[$productId] = $item;
            } else {
                throw new NoSuchEntityException(__('Requested item doesn\'t exist'));
            }
        }

        return $this->loaded[$productId];


    }
}
