<?php


namespace Riki\Wamb\Controller\Adminhtml;

abstract class Rule extends AbstractAction
{
    const ADMIN_RESOURCE = 'Riki_Wamb::Rule';

    /**
     * @var \Riki\Wamb\Model\RuleRepository
     */
    protected $ruleRepository;

    /**
     * Rule constructor.
     *
     * @param \Riki\Wamb\Model\RuleRepository $ruleRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Wamb\Model\RuleRepository $ruleRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->ruleRepository = $ruleRepository;
        parent::__construct($logger, $registry, $context);
    }
}
