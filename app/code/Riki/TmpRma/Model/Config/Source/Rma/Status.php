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
namespace Riki\TmpRma\Model\Config\Source\Rma;

/**
 * Class Status
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model\Config
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Status extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * StatusHelper
     *
     * @var \Riki\TmpRma\Helper\Status
     */
    protected $statusHelper;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Status constructor.
     *
     * @param \Riki\TmpRma\Helper\Status  $statusHelper helper
     * @param \Magento\Framework\Registry $registry     registry
     */
    public function __construct(
        \Riki\TmpRma\Helper\Status $statusHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->statusHelper = $statusHelper;
        $this->registry = $registry;
    }

    /**
     * Get current rma
     *
     * @return mixed
     */
    public function getRma()
    {
        return $this->registry->registry('_current_rma');
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function prepare()
    {
        $rma = $this->getRma();
        $status = $rma ? $rma->getStatus() : 0;

        return $this->statusHelper->getAvailableOptions($status);
    }
}
