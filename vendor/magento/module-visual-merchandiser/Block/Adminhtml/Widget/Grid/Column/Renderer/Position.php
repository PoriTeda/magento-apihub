<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer;

class Position extends Renderer\Number
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $input = $this->_getInputValueElement($row);
        $top = (string) __('Top');
        $bottom = (string)  __('Bottom');

        $html = <<<HTML
<div class="position">
    <a href="#" class="move-top icon-backward"><span>{$top}</span></a>
    {$input}
    <a href="#" class="move-bottom icon-forward"><span>{$bottom}</span></a>
</div>
HTML;

        return $html;
    }
}
