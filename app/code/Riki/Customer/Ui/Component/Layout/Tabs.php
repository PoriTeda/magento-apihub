<?php

namespace Riki\Customer\Ui\Component\Layout;

class Tabs extends \Magento\Ui\Component\Layout\Tabs
{
    protected function addNavigationBlock()
    {
        $pageLayout = $this->component->getContext()->getPageLayout();

        $navName = 'tabs_nav';
        if ($pageLayout->hasElement($navName)) {
            $navName = $this->component->getName() . '_tabs_nav';
        }

        /** @var \Magento\Ui\Component\Layout\Tabs\Nav $navBlock */
        if (isset($this->navContainerName)) {
            $navBlock = $pageLayout->addBlock(
                \Magento\Ui\Component\Layout\Tabs\Nav::class,
                $navName,
                $this->navContainerName
            );
        } else {
            $navBlock = $pageLayout->addBlock(\Magento\Ui\Component\Layout\Tabs\Nav::class, $navName, 'content');
        }
        $navBlock->setTemplate('Magento_Ui::layout/tabs/nav/default.phtml');
        $navBlock->setData('data_scope', $this->namespace);

        $this->component->getContext()->addComponentDefinition(
            'nav',
            [
                'component' => 'Riki_Customer/js/form/element/tab_group',
                'config' => [
                    'template' => 'ui/tab'
                ],
                'extends' => $this->namespace
            ]
        );
    }
}
