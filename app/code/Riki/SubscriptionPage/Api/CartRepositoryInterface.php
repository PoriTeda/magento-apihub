<?php
namespace Riki\SubscriptionPage\Api;

interface CartRepositoryInterface
{
    /**
     * Get grand total of a cart depend on cartData
     *
     * @param string $cartData
     *
     * @return string
     */
    public function emulate($cartData);

    /**
     *  @param int $courseId
     * @param string[] $selectedMain
     * @param int|null $storeId
     * @return mixed
     */
    public function automaticallyMachine($courseId , $selectedMain, $storeId = null);

    /**
     * @param string $request
     * @return mixed
     */
    public function loadMoreMachine($request);
}