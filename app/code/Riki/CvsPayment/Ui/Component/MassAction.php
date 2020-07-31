<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Ui\Component;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Riki\CvsPayment\Api\ConstantInterface;

/**
 * Class MassAction
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Ui
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassAction extends \Magento\Ui\Component\MassAction
{
    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * MassAction constructor.
     *
     * @param \Magento\Framework\Registry $registry   registry
     * @param ContextInterface            $context    context
     * @param array                       $components params
     * @param array                       $data       params
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        $components,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare data
     *
     * @return void
     */
    public function prepare()
    {
        $flag = $this->registry
            ->registry(ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS);
        if (is_null($flag)) {
            unset($this->components['regenerate_slip']);
        }

        parent::prepare();
    }
}
