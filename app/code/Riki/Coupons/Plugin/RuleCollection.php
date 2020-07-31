<?php
/**
 * Coupons
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Coupons
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Coupons\Plugin;

use \Magento\SalesRule;
use \Magento\Framework;

/**
 * Class RuleCollection
 *
 * @category  RIKI
 * @package   Riki\Coupons\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class RuleCollection extends \Amasty\Coupons\Plugin\RuleCollection
{
    /**
     * Property
     *
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;
    /**
     * Property
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * RuleCollection constructor.
     * @param SalesRule\Model\Coupon $coupon
     * @param \Riki\Coupons\Helper\Coupon $couponHelper
     * @param Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     * @param Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param Framework\Registry $registry
     */
    public function __construct(
        \Magento\SalesRule\Model\Coupon $coupon,
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
        $this->couponHelper = $couponHelper;
        parent::__construct($coupon, $scopeConfig, $couponUsage, $timezone, $registry);
    }

    /**
     * Extend setValidationFilter
     *
     * @param mixed    $subject         $subject
     * @param \Closure $proceed         $proceed
     * @param string   $websiteId       $websiteId
     * @param string   $customerGroupId $customerGroupId
     * @param string   $couponCode      $couponCode
     * @param null     $now             $now
     *
     * @return mixed
     */
    public function aroundSetValidationFilter(
        $subject,
        \Closure $proceed,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null
    ) {
        $uniqueCodesArray = $this->couponHelper->getUniqueCodes();
        $couponCodeArray = explode(',', $couponCode);
        $uniqueCode = array_intersect($uniqueCodesArray, $couponCodeArray);
        if ($uniqueCode) {
            $currentApplyCoupon = $this->registry
                ->registry('riki_coupons_current_coupon');
            if (count($uniqueCode) > 1 && $currentApplyCoupon) {
                $match = false;
                foreach ($currentApplyCoupon as $code) {
                    if (in_array($code, $uniqueCode)) {
                        $couponCode = $code;
                        $match = true;
                        break;
                    }
                }

                if (!$match) {
                    $couponCode = current($uniqueCode);
                }
            } else {
                $couponCode = current($uniqueCode);
            }
        }

        $result = $proceed($websiteId, $customerGroupId, $couponCode, $now);

        if ($couponCode === '' || strpos($couponCode, ',') === false) {
            return $result;
        }

        $select = $subject->getSelect();
        $where = $select->getPart(\Magento\Framework\DB\Select::WHERE);
        foreach ($where as $key => $cond) {
            $needle = sprintf("rule_coupons.code = '%s'", $couponCode);
            if (strpos($cond, $needle) !== false) {
                $code = implode("','", explode(',', $couponCode));
                $replace = sprintf("rule_coupons.code IN ('%s')", $code);
                $cond = str_replace($needle, $replace, $cond);
                $where[$key] = $cond;
                break;
            }
        }
        $select->setPart(\Magento\Framework\DB\Select::WHERE, $where);

        return $result;
    }
}
