<?php
namespace Riki\AdvancedInventory\Api\Data\OutOfStock;

interface QueueExecuteInterface
{
    const WAITING = 1;
    const SUCCESS = 2;
    const ERROR = 3;
}