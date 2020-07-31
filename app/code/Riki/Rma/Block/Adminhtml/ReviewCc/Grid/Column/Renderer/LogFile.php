<?php
namespace Riki\Rma\Block\Adminhtml\ReviewCc\Grid\Column\Renderer;

class LogFile extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        /** @var \Riki\Rma\Model\ReviewCc\LogFile $logFile */
        $logFile = $row->getLogFile();

        if ($row->getData('status') == \Riki\Rma\Model\Config\Source\ReviewCc\Status::STATUS_DONE) {
            return $this->escapeHtml($logFile->getFileName());
        }

        return '';
    }
}
