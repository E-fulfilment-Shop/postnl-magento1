<?php
/**
 *                  ___________       __            __   
 *                  \__    ___/____ _/  |_ _____   |  |  
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/       
 *          ___          __                                   __   
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_ 
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |  
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|  
 *                  \/                           \/               
 *                  ________       
 *                 /  _____/_______   ____   __ __ ______  
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \ 
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/ 
 *                        \/                       |__|    
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL: 
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2013 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
 
/**
 * Observer to edit the sales > shipments grid
 */
class TIG_PostNL_Model_Adminhtml_Observer_ShipmentGrid extends Varien_Object
{
    /**
     * The block we want to edit
     */
    const SHIPMENT_GRID_BLOCK_NAME = 'adminhtml/sales_shipment_grid';
    
    /**
     * variable name for shipment grid filter
     */
    const SHIPMENT_GRID_FILTER_VAR_NAME = 'sales_shipment_gridfilter';
    
    /**
     * variable name for shipment grid sorting
     */
    const SHIPMENT_GRID_SORT_VAR_NAME = 'sales_shipment_gridsort';
    
    /**
     * variable name for shipment grid sorting direction
     */
    const SHIPMENT_GRID_DIR_VAR_NAME = 'sales_shipment_griddir';
    
    /**
     * XML path to 'shipping grid columns' setting
     */
    const XML_PATH_SHIPPING_GRID_COLUMNS = 'postnl/cif_labels_and_confirming/shipping_grid_columns';
    
    /**
     * XML path to default selected mass action setting
     */
    const XML_PATH_SHIPPING_GRID_MASSACTION_DEFAULT = 'postnl/cif_labels_and_confirming/shipping_grid_massaction_default';
    
    /**
     * get an array of optional columns to display
     * 
     * @return boolean
     */
    public function getOptionalColumnsToDisplay()
    {
        $columnsToDisplay = Mage::getStoreConfig(
                                        self::XML_PATH_SHIPPING_GRID_COLUMNS, 
                                        Mage_Core_Model_App::ADMIN_STORE_ID
                                    );
        
        $columnsToDisplay = explode(',', $columnsToDisplay);
        
        return $columnsToDisplay;
    }
    
    /**
     * Observer that adds columns to the grid and allows these to be sorted and filtered properly
     * 
     * @param Varien_Event_Observer $observer
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     * 
     * @event adminhtml_block_html_before
     * 
     * @observer postnl_adminhtml_shipmentgrid
     * 
     * @todo see if replacing the collection can be avoided
     */
    public function modifyGrid(Varien_Event_Observer $observer)
    {
        /**
         * check if the extension is active
         */
        if (!Mage::helper('postnl')->isEnabled()) {
            return $this;
        }
        
        /**
         * Checks if the current block is the one we want to edit.
         * 
         * Unfortunately there is no unique event for this block
         */
        $block = $observer->getBlock();
        $shipmentGridClass = Mage::getConfig()->getBlockClassName(self::SHIPMENT_GRID_BLOCK_NAME);
       
        if (get_class($block) !== $shipmentGridClass) {
            return $this;
        }
        
        $currentCollection = $block->getCollection();
        $select = $currentCollection->getSelect();
        
        /**
         * replace the collection as the default collection has a bug preventing it from being reset.
         * Without being able to reset it, we can't edit it. Therefore we are forced to replace it altogether
         * 
         * TODO see if this can be avoided in any way
         */
        $collection = Mage::getResourceModel('postnl/order_shipment_grid_collection');
        $collection->setSelect($select)
                   ->setPageSize($currentCollection->getPageSize())
                   ->setCurPage($currentCollection->getCurPage());
        
        $this->setCollection($collection);
        $this->setBlock($block);
        
        $this->_joinCollection($collection);
        $this->_addColumns($block);
        $this->_addMassaction($block);
        $this->_applySortAndFilter($collection);
        
        $block->setCollection($this->getCollection());
        return $this;
    }
    
    /**
     * Adds additional columns to the grid
     * 
     * @param Mage_Adminhtml_Block_Sales_Shipment_Grid $block
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _addColumns($block)
    {
        $helper = Mage::helper('postnl');
        
        /**
         * Get an array of which optional columns should be shown
         */
        $columnsToDisplay = $this->getOptionalColumnsToDisplay();
        
        /**
         * This variable is the column ID of each column that the next column will follow.
         * By changing this variable after each column is added we guarantee the correct
         * column order will be followed regardless of which optional columns are shown
         */
        $after = 'total_qty';
        if (in_array('shipping_description', $columnsToDisplay)) {
            $block->addColumnAfter(
                'shipping_description',
                array(
                    'header'    => $helper->__('Shipping Method'),
                    'align'     => 'left',
                    'index'     => 'shipping_description',
                ),
                $after
            );
            
            $after = 'shipping_description';
        }
        
        $block->addColumnAfter(
            'confirm_date',
            array(
                'type'     => 'date',
                'header'   => $helper->__('Send Date'),
                'align'    => 'left',
                'index'    => 'confirm_date',
                'renderer' => 'postnl_adminhtml/widget_grid_column_renderer_confirmDate',
                'frame_callback' => array($this, 'decorateConfirmDate'),
            ),
            $after
        );
        
        $after = 'confirm_date';
        
        if (in_array('labels_printed', $columnsToDisplay)) {
            $block->addColumnAfter(
                'labels_printed',
                array(
                    'header'   => $helper->__('Labels printed'),
                    'align'    => 'left',
                    'type'     => 'options',
                    'index'    => 'labels_printed',
                    'options'  => array(
                        1 => Mage::helper('postnl')->__('Yes'),
                        0 => Mage::helper('postnl')->__('No'),
                    ),
                    'renderer'       => 'postnl_adminhtml/widget_grid_column_renderer_yesNo',
                    'frame_callback' => array($this, 'decorateLabelsPrinted'),
                ),
                $after
            );
            
            $after = 'labels_printed';
        }
        
        $block->addColumnAfter(
            'barcode',
            array(
                'header'   => $helper->__('Track & Trace'),
                'align'    => 'left',
                'index'    => 'main_barcode',
                'renderer' => 'postnl_adminhtml/widget_grid_column_renderer_barcode',
            ),
            $after
        );
        
        $after = 'barcode';
        
        if (in_array('shipping_phase', $columnsToDisplay)) {
            $block->addColumnAfter(
                'shipping_phase',
                array(
                    'header'   => $helper->__('Shipping Phase'),
                    'align'    => 'left',
                    'index'    => 'shipping_phase',
                    'type'     => 'options',
                    'options'  => Mage::helper('postnl/cif')->getShippingPhases(),
                    'renderer' => 'postnl_adminhtml/widget_grid_column_renderer_shippingPhase',
                    'frame_callback' => array($this, 'decorateShippingPhase'),
                ),
                $after
            );
            
            $after = 'shipping_phase';
        }
        
        $actionColumn = $block->getColumn('action');
        $actions = $actionColumn->getActions();
        
        $actions[] = array(
            'caption'   => $helper->__('Print label'),
            'url'       => array('base' => 'postnl/adminhtml_shipment/printLabel'),
            'field'     => 'shipment_id',
            'is_postnl' => true, //custom flag for renderer
        );
        
        $actions[] = array(
            'caption'   => $helper->__('Confirm'),
            'url'       => array('base' => 'postnl/adminhtml_shipment/confirm'),
            'field'     => 'shipment_id',
            'is_postnl' => true, //custom flag for renderer
        );
        
        $actionColumn->setActions($actions)
                     ->setWidth('150px')
                     ->setData('renderer', 'postnl_adminhtml/widget_grid_column_renderer_action');
        
        $block->sortColumnsByOrder();
        
        return $this;
    }

    /**
     * Decorates the confirm_sate column
     * 
     * @param string | null $value
     * @param Mage_Sales_Model_Order_Shipment $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param boolean $isExport
     * 
     * @return string
     */
    public function decorateConfirmDate($value, $row, $column, $isExport)
    {
        $postnlShipmentClass = Mage::getConfig()->getModelClassName('postnl_core/shipment');
        
        $class = '';
        if ($row->getData('confirm_status') == $postnlShipmentClass::CONFIRM_STATUS_CONFIRMED) {
            $class = 'grid-severity-notice';
        }
        
        if ($row->getData('confirm_status') == $postnlShipmentClass::CONFIRM_STATUS_CONFIRM_EXPIRED) {
            $class = 'grid-severity-critical';
        }
        
        if ($row->getData('confirm_status') == $postnlShipmentClass::CONFIRM_STATUS_UNCONFIRMED
            && date('Ymd', Mage::getModel('core/date')->timestamp()) == date('Ymd', strtotime($value))
        ) {
            $class = 'grid-severity-major';
        } elseif ($row->getData('confirm_status') == $postnlShipmentClass::CONFIRM_STATUS_UNCONFIRMED) {
            $class = 'grid-severity-minor';
        }
        
        return '<span class="'.$class.'"><span>'.$value.'</span></span>';
    }
    
    /**
     * Decorates the labels_printed column
     * 
     * @param string | null $value
     * @param Mage_Sales_Model_Order_Shipment $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param boolean $isExport
     * 
     * @return string
     */
    public function decorateLabelsPrinted($value, $row, $column, $isExport)
    {
        switch ($row->getData($column->getIndex())) {
            case null: //rows with no value (non-PostNL shipments)
                $class = '';
                break;
            case 0:
                $class = 'grid-severity-critical';
                break;
            case 1:
                $class = 'grid-severity-notice';
                break;
            default:
                $class = '';
                break;
        }
        return '<span class="'.$class.'"><span>'.$value.'</span></span>';
    }
    
    /**
     * Decorates the shipping_phase column
     * 
     * @param string | null $value
     * @param Mage_Sales_Model_Order_Shipment $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param boolean $isExport
     * 
     * @return string
     */
    public function decorateShippingPhase($value, $row, $column, $isExport)
    {
        $postnlShipmentClass = Mage::getConfig()->getModelClassName('postnl_core/shipment');
        
        $class = '';
        switch ($row->getData($column->getIndex())) {
            case null: //rows with no value (non-PostNL shipments) or unconfirmed shipments
                $class = '';
                break;
            case $postnlShipmentClass::SHIPPING_PHASE_COLLECTION:   //no break
            case $postnlShipmentClass::SHIPPING_PHASE_SORTING:      //no break;
            case $postnlShipmentClass::SHIPPING_PHASE_DISTRIBUTION: //no break;
            case $postnlShipmentClass::SHIPPING_PHASE_DELIVERED:
                $class = 'grid-severity-notice';
                break;
            case $postnlShipmentClass::SHIPPING_PHASE_NOT_APPLICABLE:
                $class = 'grid-severity-critical';
                break;
            default:
                $class = '';
                break;
        }
        return '<span class="'.$class.'"><span>'.$value.'</span></span>';
    }

    /**
     * Adds a massaction to confirm the order and print the shipping labels
     * 
     * @param Mage_Adminhtml_Block_Sales_Shipment_Grid $block
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _addMassaction($block)
    {
        $massactionBlock = $block->getMassactionBlock();
        
        /**
         * Build all the mass action option arrays
         */
        $printAndConfirmOptions = array(
            'label'=> Mage::helper('postnl')->__('PostNL - Print shipping labels & confirm shipment'),
            'url'  => Mage::helper('adminhtml')->getUrl('postnl/adminhtml_shipment/massPrintLabelsAndConfirm'),
        );
        
        $printOptions = array(
            'label'=> Mage::helper('postnl')->__('PostNL - Print shipping labels'),
            'url'  => Mage::helper('adminhtml')->getUrl('postnl/adminhtml_shipment/massPrintLabels'),
        );
        
        $confirmOptions = array(
            'label'=> Mage::helper('postnl')->__('PostNL - Confirm shipments'),
            'url'  => Mage::helper('adminhtml')->getUrl('postnl/adminhtml_shipment/massConfirm'),
        );
        
        /**
         * Check which mass action should be selected by default
         */
        $defaultSelectedOption = Mage::getStoreConfig(
            self::XML_PATH_SHIPPING_GRID_MASSACTION_DEFAULT, 
            Mage_Core_Model_App::ADMIN_STORE_ID
        );
        
        /**
         * Add the additional 'selected' parameter to the chosen mass action
         */
        switch ($defaultSelectedOption) {
            case 'postnl_print_labels_and_confirm':
                $printAndConfirmOptions['selected'] = true;
                break;
            case 'postnl_print_labels':
                $printOptions['selected'] = true;
                break;
            case 'postnl_confirm_shipments':
                $confirmOptions['selected'] = true;
                break;
            // no default
        }
        
        /**
         * Add the mass actions to the grid
         */
        $massactionBlock->addItem(
            'postnl_print_labels_and_confirm',
            $printAndConfirmOptions
        );
        
        $massactionBlock->addItem(
            'postnl_print_labels',
            $printOptions
        );
        
        $massactionBlock->addItem(
            'postnl_confirm_shipments',
            $confirmOptions
        );
        
        return $this;
    }
    
    /**
     * Adds additional joins to the collection that will be used by newly added columns
     * 
     * @param Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _joinCollection($collection)
    {
        $resource = Mage::getSingleton('core/resource');
        
        $select = $collection->getSelect();
        
        /**
         * Join sales_flat_order table
         */
        $select->joinInner(
            array('order' => $resource->getTableName('sales/order')),
            '`main_table`.`order_id`=`order`.`entity_id`',
            array(
                'shipping_method'      => 'order.shipping_method',
                'shipping_description' => 'order.shipping_description',
            )
        );
        
        /**
         * join sales_flat_order_address table
         */
        $select->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "`main_table`.`order_id`=`shipping_address`.`parent_id` AND `shipping_address`.`address_type`='shipping'",
            array(
                'postcode'   => 'shipping_address.postcode',
                'country_id' => 'shipping_address.country_id',
            )
        );
        
        /**
         * Join tig_postnl_shipment table
         */
        $select->joinLeft(
            array('postnl_shipment' => $resource->getTableName('postnl_core/shipment')),
            '`main_table`.`entity_id`=`postnl_shipment`.`shipment_id`',
            array(
                'confirm_date'   => 'postnl_shipment.confirm_date',
                'main_barcode'   => 'postnl_shipment.main_barcode',
                'confirm_status' => 'postnl_shipment.confirm_status',
                'labels_printed' => 'postnl_shipment.labels_printed',
                'shipping_phase' => 'postnl_shipment.shipping_phase',
            )
        );
        
        return $this;
    }
    
    /**
     * Applies sorting and filtering to the collection
     * 
     * @param Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _applySortAndFilter($collection)
    {
        $session = Mage::getSingleton('adminhtml/session');
        
        $filter = $session->getData(self::SHIPMENT_GRID_FILTER_VAR_NAME);
        $filter = Mage::helper('adminhtml')->prepareFilterString($filter);
        
        if ($filter) {
            $this->_filterCollection($collection, $filter);
        }
        
        $sort = $session->getData(self::SHIPMENT_GRID_SORT_VAR_NAME);
        
        if ($sort) {
            $dir = $session->getData(self::SHIPMENT_GRID_DIR_VAR_NAME);
            
            $this->_sortCollection($collection, $sort, $dir);
        }
        
        return $this;
    }
    
    /**
     * Adds new filters to the collection if these filters are based on columns added by this observer
     * 
     * @param Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection
     * @param array $filter Array of filters to be added
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _filterCollection($collection, $filter)
    {
        $block = $this->getBlock();
        
        foreach ($filter as $columnName => $value) {$column = $block->getColumn($columnName);
            
            $column->getFilter()->setValue($value);
            $this->_addColumnFilterToCollection($column);
        }
        
        return $this;
    }
    
    /**
     * Based on Mage_Adminhtml_Block_Widget_Grid::_addColumnFilterToCollection()
     * 
     * Adds a filter condition tot eh collection for a specified column
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _addColumnFilterToCollection($column)
    {
        if (!$this->getCollection()) {
            return $this;
        }
        
        $field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();
        if ($column->getFilterConditionCallback()) {
            call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
            
            return $this;
        }
        
        $cond = $column->getFilter()->getCondition();
        if ($field && isset($cond)) {
            $this->getCollection()->addFieldToFilter($field , $cond);
        }
        
        return $this;
    }
    
    /**
     * Sorts the collection by a specified column in a specified direction
     * 
     * @param Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection
     * @param string $sort The column that the collection is sorted by
     * @param string $dir The direction that is used to sort the collection
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _sortCollection($collection, $sort, $dir)
    {
        $block = $this->getBlock();
        $column = $block->getColumn($sort);
        if (!$column) {
            return $this;
        }
        
        $column->setDir($dir);
        $this->_setCollectionOrder($column);
        
        return $this;
    }
    
    /**
     * Sets sorting order by some column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * 
     * @return TIG_PostNL_Model_Adminhtml_ShipmentGridObserver
     */
    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return $this;
        }
        
        $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $collection->setOrder($columnIndex, strtoupper($column->getDir()));
        return $this;
    }
}
