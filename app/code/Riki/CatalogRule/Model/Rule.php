<?php
namespace Riki\CatalogRule\Model;

class Rule extends \Magento\CatalogRule\Model\Rule
{
    const APPLY_SPOT_ONLY = 1;
    const APPLY_SUBSCRIPTION_ONLY = 2;
    const APPLY_SPOT_SUBSCRIPTION = 3;
}
