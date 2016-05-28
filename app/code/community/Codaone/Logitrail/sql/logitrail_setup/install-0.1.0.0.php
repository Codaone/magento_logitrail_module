<?php

$installer = $this;
 $installer->startSetup();

 $table_names = array(
    'sales/quote',
    'sales/order');

    foreach ($table_names as $table_name) {

        $installer->getConnection()
        ->addColumn($installer->getTable($table_name), 'logitrail_order_id', array(
            'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable'  => true,
            'length'    => 255,
            'after'     => null, // column name to insert new column after
            'comment'   => 'Logitrail Order ID'));   
    }

 $installer->endSetup();

