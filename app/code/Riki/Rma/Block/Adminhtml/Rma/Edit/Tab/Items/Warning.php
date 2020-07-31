<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Warning extends \Magento\Backend\Block\Template
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Validator\Factory
     */
    protected $validatorFactory;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Warning constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->functionCache = $functionCache;
        $this->validatorFactory = $validatorFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get current rma
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->dataHelper->getCurrentRma();
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return false;
        }

        return parent::getTemplate();
    }


    /**
     * Get warning messages
     *
     * @return array
     */
    public function getMessages()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return [];
        }

        if ($this->functionCache->has($this->getRma()->getId())) {
            return $this->functionCache->load($this->getRma()->getId());
        }

        $validator = $this->validatorFactory->createValidator('rma', 'approval');
        $validator->isValid($this->getRma());
        $messages = $validator->getMessages();
        $result = (!$messages || !isset($messages['warning']))
            ? []
            : is_array($messages['warning']) ? $messages['warning'] : $messages; // should use constant
        $this->functionCache->store($result, $this->getRma()->getId());

        return $result;
    }
}