<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class Comment extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (empty($row->getData('comment'))) {
            return parent::render($row);
        }

        return $this->getHtml($row);
    }

    /**
     * Get html
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getHtml(\Magento\Framework\DataObject $row)
    {
        $html = $this->_highlight($row->getData('comment'));

        $comments = explode(\Riki\Rma\Plugin\Rma\Model\Rma\Status\History::SEPARATOR, $row->getData('comment'));

        return count($comments) == 1
            ? $html
            : $html .  '... <a href="' . $this->getRmaUrl($row) .'">' . __('view more') . '</a>';
    }

    /**
     * Get filter value
     *
     * @return string
     */
    protected function getFilterText()
    {
        $filterText = base64_decode($this->getRequest()->getParam('filter', ''));
        foreach (explode('&', $filterText) as $text) {
            if (strpos($text, 'comment=') === 0) {
                return str_replace('comment=', '', urldecode($text));
            }
        }

        return '';
    }

    /**
     * Highlight filter text
     *
     * @param string $subject
     * @return mixed|string
     */
    protected function _highlight($subject = '')
    {
        if (empty($subject)) {
            return $subject;
        }

        $filterText = $this->getFilterText();

        $comments = explode(\Riki\Rma\Plugin\Rma\Model\Rma\Status\History::SEPARATOR, $subject);
        $html = '';
        if ($filterText) {
            foreach ($comments as $comment) {
                if (strpos(strtolower($comment), strtolower($filterText)) !== false) {
                    $html = str_ireplace($filterText, '<span style="background-color: #ffff00">' . $filterText . '</span>', $comment);
                    break;
                }
            }
        }
        if (!$html) {
            $html = end($comments);
        }

        return $html;
    }

    /**
     * Get url of edit rma page
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getRmaUrl(\Magento\Framework\DataObject $row)
    {
        return $this->getUrl('adminhtml/rma/edit', ['id' => $row->getData('entity_id')]);
    }
}