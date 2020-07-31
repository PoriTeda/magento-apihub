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

namespace Riki\Coupons\Helper;

use Riki\Framework\Helper\Cache\FunctionCache;
use Magento\SalesRule\Api as SalesRuleApi;
use Magento\Framework\Api as FrameworkApi;

/**
 * Class Coupon
 *
 * @category  RIKI
 * @package   Riki\Coupons\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Coupon extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * InvalidCoupons
     *
     * @var array
     */
    protected $invalidCoupons = [];
    /**
     * SearchCriteriaBuilder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * CouponRepositoryInterface
     *
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface
     */
    protected $couponRepository;
    /**
     * RuleRepositoryInterface
     *
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $ruleRepository;
    /**
     * FunctionCache
     *
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * Coupon constructor.
     *
     * @param FunctionCache $functionCache
     * @param SalesRuleApi\RuleRepositoryInterface $ruleRepository
     * @param FrameworkApi\SearchCriteriaBuilder $criteriaBuilder
     * @param SalesRuleApi\CouponRepositoryInterface $couponRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->functionCache = $functionCache;
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->couponRepository = $couponRepository;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * Get list coupons by list codes
     *
     * @param array $codes codes
     *
     * @return \Magento\SalesRule\Api\Data\CouponSearchResultInterface
     */
    public function getCouponsByCodes($codes)
    {
        if (!is_array($codes)) {
            $codes = explode(',', $codes);
        }
        if ($this->functionCache->has($codes)) {
            return $this->functionCache->load($codes);
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('code', $codes, 'in')
            ->create();

        $result = $this->couponRepository->getList($searchCriteria);
        $this->functionCache->store($result, $codes);

        return $result;
    }

    /**
     * Get active coupons
     *
     * @param string|array $codes codes
     *
     * @return array
     */
    public function getActiveCouponsByCodes($codes)
    {
        if (!is_array($codes)) {
            $codes = explode(',', $codes);
        }
        if ($this->functionCache->has($codes)) {
            return $this->functionCache->load($codes);
        }

        $coupons = [];
        $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $result = $this->getCouponsByCodes($codes);
        foreach ($result->getItems() as $coupon) {
            try {
                $rule = $this->ruleRepository->getById($coupon['rule_id']);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->addInvalidCoupon($coupon['code']);
                continue;
            }

            if (!empty($rule->getToTime()) && $rule->getToTime() < $currentTime) {
                $this->addInvalidCoupon($coupon['code']);
                continue;
            }

            if (!empty($rule->getFromTime()) && $rule->getFromTime() > $currentTime) {
                $this->addInvalidCoupon($coupon['code']);
                continue;
            }

            if (!$rule->getIsActive()) {
                $this->addInvalidCoupon($coupon['code']);
                continue;
            }

            $coupons[] = $coupon;
        }

        $this->functionCache->store($coupons, $codes);

        return $coupons;
    }

    /**
     * Get applied coupon of quote
     *
     * @param \Magento\Quote\Model\Quote $quote quote
     *
     * @return array
     */
    public function getAppliedCouponByQuote(\Magento\Quote\Model\Quote $quote)
    {
        $rulesApply = (string)$quote->getAppliedRuleIds();
        $couponCode = (string)$quote->getCouponCode();

        $rulesApply = $rulesApply ? explode(',', $rulesApply) : [];
        $couponCode = $couponCode ? explode(',', $couponCode) : [];


        $couponApply = [];
        $coupons = $this->getCouponsByCodes($couponCode);
        foreach ($coupons->getItems() as $coupon) {
            if (!in_array($coupon['rule_id'], $rulesApply)) {
                continue;
            }
            $couponApply[] = $coupon['code'];
        }

        return $couponApply;
    }

    /**
     * Get validate coupon (remove duplicate, remove same rule, ...)
     *
     * @param string $coupon coupon
     *
     * @return string
     */
    public function getValidCouponCode($coupon)
    {
        if ($this->functionCache->has($coupon)) {
            return $this->functionCache->load($coupon);
        }

        $couponCodes = array_map('trim', array_unique(explode(',', $coupon)));
        $coupons = $this->getActiveCouponsByCodes($couponCodes);
        if (!$coupons) {
            return '';
        }

        $codes = [];
        foreach ($coupons as $coupon) {
            if (isset($codes[$coupon['rule_id']])) {
                $this->addInvalidCoupon($codes[$coupon['rule_id']]);
            }
            $codes[$coupon['rule_id']] = $coupon['code'];
        }

        $result = implode(',', $codes);

        $this->functionCache->store($result, $coupon);

        return $result;
    }

    /**
     * Add a invalid coupon into storage
     *
     * @param string $coupon coupon
     *
     * @return $this
     */
    public function addInvalidCoupon($coupon)
    {
        if (!in_array($coupon, $this->invalidCoupons)) {
            $this->invalidCoupons[] = $coupon;
        }
        return $this;
    }

    /**
     * Get invalid coupons
     *
     * @return array
     */
    public function getInvalidCoupons()
    {
        return $this->invalidCoupons;
    }

    /**
     * Get unique coupon codes
     *
     * @return array|mixed|null
     */
    public function getUniqueCodes()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $code = $this->scopeConfig->getValue(
            'amcoupons/general/unique_codes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$code) {
            return [];
        }

        $uniqueCodes = [];
        $codes = explode(',', $code);
        $result = $this->getActiveCouponsByCodes($codes);
        foreach ($result as $item) {
            $uniqueCodes[] = $item['code'];
        }

        $this->functionCache->store($uniqueCodes);

        return $uniqueCodes;
    }
}