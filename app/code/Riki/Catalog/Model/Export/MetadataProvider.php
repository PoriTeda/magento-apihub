<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Filters;
use Magento\Ui\Component\Filters\Type\Select;
use Magento\Ui\Component\Listing\Columns;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * MetadataProvider.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MetadataProvider extends \Magento\Ui\Model\Export\MetadataProvider
{


    /**
     * Returns row data
     *
     * @param DocumentInterface $document DocumentInterface
     * @param array             $fields   Array
     * @param array             $options  Array
     *
     * @return array
     */
    public function getRowDataProduct($document, $fields, $options)
    {
        $row = [];
        foreach ($fields as $column) {
            if (isset($options[$column])) {
                $key = $document->getData($column);
                if (isset($options[$column][$key])) {
                    $row[] = $options[$column][$key];
                } else {
                    $row[] = '';
                }
            } else {
                $value = '';
                if (is_array($document->getData($column))) {
                    $value = implode(",", $document->getData($column));
                } else {
                    $value = $document->getData($column);
                }
                $row[] = $value;
            }
        }
        return $row;
    }

    /**
     * Retrieve Headers row array for Export
     *
     * @param UiComponentInterface $component UiComponentInterface
     *
     * @return string[]
     */
    public function getHeadersProduct(UiComponentInterface $component)
    {
        $row = [];
        foreach ($this->getColumns($component) as $column) {
            if ($column->getData('name') && 'ids' != $column->getData('name')) {

                $columnName = $column->getData('name');
                if ('tax_class_id' == $columnName) {
                    $columnName = 'tax_class_name';
                }
                $row[] = $columnName;
            }
        }
        return $row;
    }

    /**
     * Returns columns list
     *
     * @param UiComponentInterface $component UiComponentInterface
     *
     * @return UiComponentInterface[]
     */
    protected function getColumns(UiComponentInterface $component)
    {
        if ($component->getContext()->getNamespace() !== 'serial_code_listing') {
            return parent::getColumns($component);
        }
        if (!isset($this->columns[$component->getName()])) {
            $columns = $this->getColumnsComponent($component);
            foreach ($columns->getChildComponents() as $column) {
                if ($column->getData('config/dataType') == 'actions') {
                    continue;
                }
                $this->columns[$component->getName()][$column->getName()] = $column;
            }
        }
        return $this->columns[$component->getName()];
    }
}
