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
namespace Riki\TmpRma\Model\Config\Source;

/**
 * Class Reason
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model\Config
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Reason extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * ReasonSource
     *
     * @var \Riki\Rma\Model\Config\Source\Reason
     */
    protected $reasonSource;
    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * ReasonRepository
     *
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;
    /**
     * RmaFactory
     *
     * @var \Riki\TmpRma\Model\RmaFactory
     */
    protected $rmaFactory;
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Reason constructor.
     *
     * @param \Psr\Log\LoggerInterface                $logger           logger
     * @param \Riki\TmpRma\Model\RmaFactory           $rmaFactory       factory
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository repository
     * @param \Riki\Rma\Model\Config\Source\Reason    $reasonSource     source
     * @param \Magento\Framework\Registry             $registry         registry
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Riki\TmpRma\Model\RmaFactory $rmaFactory,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Riki\Rma\Model\Config\Source\Reason $reasonSource,
        \Magento\Framework\Registry $registry
    ) {
        $this->logger = $logger;
        $this->rmaFactory = $rmaFactory;
        $this->reasonRepository = $reasonRepository;
        $this->registry = $registry;
        $this->reasonSource = $reasonSource;
    }

    /**
     * Get current rma
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getCurrentRma()
    {
        if (!$this->registry->registry('_current_rma_id')) {
            return null;
        }

        $rma = $this->rmaFactory->create()
            ->load($this->registry->registry('_current_rma_id'));

        return $rma->getData('reason_id') ? $rma : null;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function prepare()
    {
        $options = $this->reasonSource->toArray();

        $rma = $this->getCurrentRma();
        if ($rma && !in_array($rma->getData('reason_id'), $options)) {
            try {
                $reason = $this->reasonRepository
                    ->getById($rma->getData('reason_id'));
                $options[$rma->getData('reason_id')] = sprintf(
                    '%s - %s',
                    $reason->getData('code'),
                    $reason->getDescription()
                );
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->critical($e);
            }
        }

        return ['' => ' '] + $options;
    }
}
