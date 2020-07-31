<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Form\Element;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\File as FileCore;

class File extends FileCore
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        $data = []
    )
    {
        $this->directoryList = $directoryList;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getElementHtml()
    {
        $html = '';
        $this->addClass('input-file');
        $html .= parent::getElementHtml();
        if ($this->getValue()) {
            $url = $this->_getUrl();
            if (!preg_match("/^http\:\/\/|https\:\/\//", $url)) {
                $url = $this->getDirectoryPath() . $url;
            }
            $html .= '<br /><a href="' . $url . '">' . $this->_getUrl() . '</a> ';
        }
        $html .= $this->_getDeleteCheckbox();
        return $html;
    }

    protected function _getDeleteCheckbox()
    {
        $html = '';
        if ($this->getValue()) {
            $label = (string)new \Magento\Framework\Phrase('Delete File');
            $html .= '<span class="delete-image">';
            $html .= '<input type="checkbox" name="' . parent::getName() . '_delete" value="1" class="checkbox" id="' . $this->getHtmlId() . '_delete"' . ($this->getDisabled() ? ' disabled="disabled"' : '') . '/>';
            $html .= '<label for="' . $this->getHtmlId() . '_delete"' . ($this->getDisabled() ? ' class="disabled"' : '') . '> ' . $label . '</label>';
            $html .= $this->_getHiddenInput();
            $html .= '</span>';
        }
        return $html;
    }

    protected function _getHiddenInput()
    {
        return '<input type="hidden" name="' . parent::getName() . '" value="' . $this->getValue() . '" />';
    }

    protected function _getUrl()
    {
        return $this->getValue();
    }

    public function getName()
    {
        return $this->getData('name');
    }

    public function getDirectoryPath()
    {
        $mediaDirectory = $this->directoryList->getUrlPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

        return '/' . $mediaDirectory . '/' . \Riki\SubscriptionCourse\Controller\Adminhtml\Course\Save::UPLOAD_TARGET . '/';
    }
}
