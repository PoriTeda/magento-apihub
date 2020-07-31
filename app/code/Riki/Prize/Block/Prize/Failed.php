<?php
namespace Riki\Prize\Block\Prize;

class Failed extends \Magento\Framework\View\Element\Template
{
    /**
     * Get prizes
     *
     * @return array
     */
    public function getPrizes()
    {
        return $this->hasData('prizes') ? $this->getData('prizes') : [];
    }
}
