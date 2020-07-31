<?php
namespace Riki\Rma\Block\Adminhtml\MassAction\Grid\Column\Renderer;

use Magento\Backend\Block\Context;

class RmaNumber extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * RmaNumber constructor.
     * @param Context $context
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        array $data = []
    )
    {
        $this->rmaRepository = $rmaRepository;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {

        try {
            $rma = $this->rmaRepository->get($row->getRmaId());
            return $this->escapeHtml($rma->getIncrementId());
        } catch (\Exception $e) {
            return '';
        }
    }
}
