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
namespace Riki\TmpRma\Ui\Component\Layout;

use Magento\Framework\View\Element\UiComponent\DataSourceInterface;
use Magento\Framework\View\Element\UiComponentInterface;

/**
 * Class Tabs
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Ui
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Tabs extends \Magento\Ui\Component\Layout\Tabs
{

    /**
     * {@inheritdoc}
     *
     * @param array                $topNode       topNode
     * @param UiComponentInterface $component     component
     * @param string               $componentType componentType
     *
     * @return void
     */
    protected function addChildren(
        array &$topNode,
        UiComponentInterface $component,
        $componentType
    ) {
        $childrenAreas = [];
        $collectedComponents = [];

        foreach ($component->getChildComponents() as $childComponent) {
            if ($childComponent instanceof DataSourceInterface) {
                continue;
            }
            if ($childComponent instanceof \Magento\Ui\Component\Wrapper\Block) {
                $this->addWrappedBlock($childComponent, $childrenAreas);
                continue;
            }

            $name = $childComponent->getName();
            $config = $childComponent->getData('config');
            $collectedComponents[$name] = true;
            if (isset($config['is_collection'])
                && $config['is_collection'] === true
            ) {
                $label = $childComponent->getData('config/label');
                $this->component->getContext()->addComponentDefinition(
                    'collection',
                    [
                        'component' => 'Magento_Ui/js/form/components/collection',
                        'extends' => $this->namespace
                    ]
                );

                /**
                 * Type Hinting
                 *
                 * @var UiComponentInterface $childComponent
                 * @var array $structure
                 */
                list($childComponent, $structure) = $this->prepareChildComponents(
                    $childComponent,
                    $name
                );

                $childrenStructure = $structure[$name]['children'];
                $componentItem = 'Riki_TmpRma/js/form/components/collection/item';
                $structure[$name]['children'] = [
                    $name . '_collection' => [
                        'type' => 'collection',
                        'config' => [
                            'active' => 1,
                            'removeLabel' => __('Remove %1', $label),
                            'addLabel' => __('Add New %1', $label),
                            'removeMessage' => $childComponent->getData(
                                'config/removeMessage'
                            ),
                            'itemTemplate' => 'item_template',
                        ],
                        'children' => [
                            'item_template' => ['type' => $this->namespace,
                                'isTemplate' => true,
                                'component' => $componentItem,
                                'childType' => 'group',
                                'config' => [
                                    'label' => __('New %1', $label),
                                ],
                                'children' => $childrenStructure
                            ]
                        ]
                    ]
                ];
            } else {
                /**
                 * Type Hinting
                 *
                 * @var UiComponentInterface $childComponent
                 * @var array $structure
                 */
                list($childComponent, $structure) = $this->prepareChildComponents(
                    $childComponent,
                    $name
                );
            }

            $tabComponent = $this->createTabComponent($childComponent, $name);

            $childrenAreas[$name] = [
                'type' => $tabComponent->getComponentName(),
                'dataScope' => 'data.' . $name,
                'config' => $config,
                'insertTo' => [
                    $this->namespace . '.sections' => [
                        'position' => $this->getNextSortIncrement()
                    ]
                ],
                'children' => $structure,
            ];
        }

        $this->structure[static::AREAS_KEY]['children'] = $childrenAreas;
        $topNode = $this->structure;
    }
}
