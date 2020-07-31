<?php

namespace Riki\Subscription\Model\Profile;

class SubProfileItems  extends \Magento\Framework\Model\AbstractExtensibleModel implements \Riki\Subscription\Api\Data\ProductCartInterface
{
    /**
     * @var array
     */
    protected $_cartProducts = [];
    protected $_newItems     = [];

    /**
     * Returns the profile ID.
     *
     * @return int|null Profile ID. Otherwise, null.
     */
    public function getProfileId()
    {
        return $this->getData(self::KEY_PROFILE_ID);
    }

    /**
     * Sets the profile ID.
     *
     * @param int $profileId
     *
     * @return $this
     */
    public function setProfileId($profileId)
    {
        return $this->setData(self::KEY_PROFILE_ID, $profileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentItems()
    {
        return $this->_cartProducts;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentItems(array $currentItems = null)
    {
        $this->_cartProducts = $currentItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewItems()
    {
        return $this->_newItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setNewItems(array $newItems = null)
    {
        $this->_newItems = $newItems;
    }

    /**
     * Returns the product cart delete id
     *
     * @return int
     */
    public function getProductCartDeleteId()
    {
        return $this->getData(self::KEY_PRODUCT_CART_DELETE_ID);
    }

    /**
     * Sets the product cart delete id
     *
     * @param int $productCartId
     *
     * @return $this
     */
    public function setProductCartDeleteId($productCartId)
    {
        return $this->setData(self::KEY_PRODUCT_CART_DELETE_ID, $productCartId);
    }

    /**
     * Returns all item delete flag
     *
     * @return mixed
     */
    public function getAllItemDelete()
    {
        return $this->getData(self::KEY_ALL_ITEM_DELETE);
    }

    /**
     * Sets the  all item delete flag
     *
     * @param mixed $allItemDelete
     *
     * @return $this
     */
    public function setAllItemDelete($allItemDelete)
    {
        return $this->setData(self::KEY_ALL_ITEM_DELETE, $allItemDelete);
    }
}
