<?php
namespace Riki\Subscription\Api\WebApi;
/**
 * @api
 */
interface SubProfileCartProductsInterface
{
    /**
     * @return int
     */
    public function getCartID();

    /**
     * @param int $id
     * @return $this
     */
    public function setCartID($id);

    /**
     * @return int
     */
    public function getProductID();

    /**
     * @param int $id
     * @return $this
     */
    public function setProductID($id);

    /**
     * @return int
     */
    public function getProductQty();

    /**
     * @param int $qty
     * @return $this
     */
    public function setProductQty($qty);
}