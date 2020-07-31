<?php

namespace Riki\Subscription\Api\WebApi;

interface SubProfileItemInterface
{
    const RESPONSE_CODE          = 200;
    const MESSAGE_TYPE_SUCCESS   = 'success';
    const MESSAGE_TYPE_WARNING   = 'warning';
    const MESSAGE_TYPE_NOTICE    = 'notice';
    const MESSAGE_TYPE_ERROR     = 'error';
    const MESSAGE_TYPE_EXCEPTION = 'exception';

    /**
     * Add item to profile
     *
     * @param \Riki\Subscription\Api\Data\ProductCartInterface $productCart
     *
     * @return string
     */
    public function add(\Riki\Subscription\Api\Data\ProductCartInterface $productCart);

    /**
     * Update item in profile
     *
     * @param \Riki\Subscription\Api\Data\Profile\ProductInterface $product
     *
     * @return string
     */
    public function update(\Riki\Subscription\Api\Data\Profile\ProductInterface $product);

    /**
     * Delete item in profile
     *
     * @param \Riki\Subscription\Api\Data\ProductCartInterface $productCart
     *
     * @return string
     */
    public function delete(\Riki\Subscription\Api\Data\ProductCartInterface $productCart);
}
