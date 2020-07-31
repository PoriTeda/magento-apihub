<?php

namespace Riki\Subscription\Api\Data;

/**
 * @api
 */
interface ProductCartInterface
{
    const KEY_PROFILE_ID             = 'profile_id';
    const KEY_PRODUCT_CART_DELETE_ID = 'product_cart_delete_id';
    const KEY_ALL_ITEM_DELETE        = 'all_item_delete';

    /**
     * Returns the profile ID.
     *
     * @return int|null Profile ID. Otherwise, null.
     */
    public function getProfileId();

    /**
     * Sets the profile ID.
     *
     * @param int $profileId
     *
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * Gets current list item in cart
     *
     * @return \Riki\Subscription\Api\Data\Profile\ProductInterface[]
     */
    public function getCurrentItems();

    /**
     * Sets list item in cart
     *
     * @param \Riki\Subscription\Api\Data\Profile\ProductInterface[] $currentItems
     *
     * @return $this
     */
    public function setCurrentItems(array $currentItems = null);

    /**
     * Gets new list item in cart
     *
     * @return \Riki\Subscription\Api\Data\Profile\ProductInterface[]
     */
    public function getNewItems();

    /**
     * Sets new list item in cart
     *
     * @param \Riki\Subscription\Api\Data\Profile\ProductInterface[] $newItems
     *
     * @return $this
     */
    public function setNewItems(array $newItems = null);


    /**
     * Returns the product cart delete id
     *
     * @return int
     */
    public function getProductCartDeleteId();

    /**
     * Sets the product cart delete id
     *
     * @param int $productCartId
     *
     * @return $this
     */
    public function setProductCartDeleteId($productCartId);

    /**
     * Returns all item delete flag
     *
     * @return mixed
     */
    public function getAllItemDelete();

    /**
     * Sets the  all item delete flag
     *
     * @param mixed $allItemDelete
     *
     * @return $this
     */
    public function setAllItemDelete($allItemDelete);
}
