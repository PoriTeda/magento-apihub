<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
// @todo refactor handle status when I have time
namespace Riki\TmpRma\Helper;

/**
 * Class Status
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Status extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STATUS_REQUESTED = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_CLOSED = 4;

    /**
     * Deps
     *
     * @var array
     */
    protected $deps;

    /**
     * Authorization
     *
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Status constructor.
     *
     * @param \Magento\Framework\AuthorizationInterface $authorization authorization
     * @param \Magento\Framework\App\Helper\Context     $context       context
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->authorization = $authorization;

        parent::__construct($context);

        $this->deps = [
            self::STATUS_REQUESTED => [
                'before' => [],
                'after' => [
                    self::STATUS_REJECTED,
                    self::STATUS_APPROVED
                ]
            ],
            self::STATUS_REJECTED => [
                'before' => [
                    self::STATUS_REQUESTED
                ],
                'after' => [
                    self::STATUS_REQUESTED,
                    self::STATUS_CLOSED
                ]
            ],
            self::STATUS_APPROVED => [
                'before' => [
                    self::STATUS_REQUESTED
                ],
                'after' => [
                    self::STATUS_CLOSED
                ]
            ],
            self::STATUS_CLOSED => [
                'before' => [
                    self::STATUS_REJECTED,
                    self::STATUS_APPROVED
                ],
                'after' => []
            ]
        ];
    }

    /**
     * Get deps array
     *
     * @return array
     */
    public function getDeps()
    {
        return $this->deps;
    }

    /**
     * Get label of status
     *
     * @param string|int $status status
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($status)
    {
        $label = [
            self::STATUS_REQUESTED => __('Requested'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_CLOSED => __('Closed'),
        ];

        return isset($label[$status]) ? $label[$status] : $status;
    }


    /**
     * Get available status of status
     *
     * [value => label]
     *
     * @param string|int $status status
     *
     * @return array
     */
    public function getAvailableOptions($status = null)
    {
        $status = (int)$status;
        $deps = $this->getDeps();

        if (!$status) {
            $status = key($deps);
            return [
                $status => $this->getLabel($status)
            ];
        }

        if (!isset($deps[$status])) {
            return [];
        }

        $options = [
            $status => $this->getLabel($status)
        ];
        $auth = [
            self::STATUS_REJECTED => 'Riki_TmpRma::rma_actions_reject',
            self::STATUS_APPROVED => 'Riki_TmpRma::rma_actions_approve',
            self::STATUS_CLOSED => 'Riki_TmpRma::rma_actions_close'
        ];
        foreach ($deps[$status]['after'] as $val) {
            if (isset($auth[$val])
                && !$this->authorization->isAllowed($auth[$val])
            ) {
                continue;
            }
            $options[$val] = $this->getLabel($val);
        }

        return $options;
    }

    /**
     * Get all status
     *
     * [value => label]
     *
     * @return array
     */
    public function getOptions()
    {
        $options = [];
        $deps = $this->getDeps();
        $auth = [
            self::STATUS_REJECTED => 'Riki_TmpRma::rma_actions_reject',
            self::STATUS_APPROVED => 'Riki_TmpRma::rma_actions_approve',
            self::STATUS_CLOSED => 'Riki_TmpRma::rma_actions_close'
        ];
        foreach ($deps as $status => $dep) {
            if (isset($auth[$status])
                && !$this->authorization->isAllowed($auth[$status])
            ) {
                continue;
            }
            $options[$status] = $this->getLabel($status);
        }

        return $options;
    }

    /**
     * Get depend after
     *
     * @param string|int $status status
     *
     * @return array
     */
    public function getDepAfter($status)
    {
        $deps = $this->getDeps();

        return isset($deps[$status])
            ? $deps[$status]['after']
            : [];
    }

    /**
     * Get depend label after
     *
     * @param string|int $status status
     *
     * @return array
     */
    public function getDepAfterLabel($status)
    {
        $labels = [];
        $dep = $this->getDepAfter($status);
        foreach ($dep as $status) {
            $labels[] = $this->getLabel($status);
        }

        return $labels;
    }

    /**
     * Get depend after
     *
     * @param string|int $status status
     *
     * @return array
     */
    public function getDepBefore($status)
    {
        $deps = $this->getDeps();

        return isset($deps[$status])
            ? $deps[$status]['before']
            : [];
    }

    /**
     * Get depend label after
     *
     * @param string|int $status status
     *
     * @return array
     */
    public function getDepBeforeLabel($status)
    {
        $labels = [];
        $dep = $this->getDepBefore($status);
        foreach ($dep as $status) {
            $labels[] = $this->getLabel($status);
        }

        return $labels;
    }

    /**
     * Is status1 able to update to status2 ?
     *
     * @param string|int $status1 status1
     * @param string|int $status2 status2
     *
     * @return bool
     */
    public function isUpdatable($status1, $status2)
    {
        $status1 = (int)$status1;
        $status2 = (int)$status2;

        if ($status1 == $status2) {
            return true;
        }

        $deps = $this->getDeps();
        if (!isset($deps[$status1]) && $status2 == key($deps)) {
            return true;
        }

        $after = $this->getDepAfter($status1);

        if (empty($after)) {
            return false;
        }

        return in_array($status2, $after);
    }
}
