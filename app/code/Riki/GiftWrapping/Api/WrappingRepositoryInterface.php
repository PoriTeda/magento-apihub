<?php

namespace Riki\GiftWrapping\Api;

interface WrappingRepositoryInterface
{
    /**
     * Get List Gift Wrapping with price include tax
     *
     * @return string
     */
    public function getList();
}
