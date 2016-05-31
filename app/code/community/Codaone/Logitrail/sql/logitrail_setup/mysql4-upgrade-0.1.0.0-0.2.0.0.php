<?php

$attributes = array(
array(
    "attributeName"  => 'Barcode', // Name of the attribute
    "attributeCode"  => 'barcode', // Code of the attribute
    "attributeGroup" => 'General',         // Group to add the attribute to
    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)
 
    // Configuration:
    "data" => array(
        'type'      => 'varchar',       // Attribute type
        'input'     => 'text',          // Input type
        'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,    // Attribute scope
        'required'  => false,           // Is this attribute required?
        'user_defined' => true,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'unique' => false,
        'used_in_product_listing' => false,
        'label' => 'Barcode'
    )
),

array(
    "attributeName"  => 'Width', // Name of the attribute
    "attributeCode"  => 'width', // Code of the attribute
    "attributeGroup" => 'General',         // Group to add the attribute to
    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)
 
    // Configuration:
    "data" => array(
         'type'      => 'int',       // Attribute type
        'input'     => 'text',          // Input type
        'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,    // Attribute scope
        'required'  => false,           // Is this attribute required?
        'user_defined' => true,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'unique' => false,
        'used_in_product_listing' => false,
        'label' => 'Width (mm)'
    )
),

array(
    "attributeName"  => 'Height', // Name of the attribute
    "attributeCode"  => 'height', // Code of the attribute
    "attributeGroup" => 'General',         // Group to add the attribute to
    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)
 
    // Configuration:
    "data" => array(
         'type'      => 'int',       // Attribute type
        'input'     => 'text',          // Input type
        'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,    // Attribute scope
        'required'  => false,           // Is this attribute required?
        'user_defined' => true,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'unique' => false,
        'used_in_product_listing' => false,
        'label' => 'Height (mm)'
    )
),

array(
    "attributeName"  => 'Length', // Name of the attribute
    "attributeCode"  => 'length', // Code of the attribute
    "attributeGroup" => 'General',         // Group to add the attribute to
    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)
 
    // Configuration:
    "data" => array(
        'type'      => 'int',       // Attribute type
        'input'     => 'text',          // Input type
        'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,    // Attribute scope
        'required'  => false,           // Is this attribute required?
        'user_defined' => true,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'unique' => false,
        'used_in_product_listing' => false,
        'label' => 'Length (mm)'
    )
));

 
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup'); 
$installer->startSetup();

foreach($attributes as $attribute) {
    $installer->addAttribute('catalog_product', $attribute['attributeCode'], $attribute['data']);
 
    foreach($attribute['attributeSetIds'] as $attributeSetId) {
        $installer->addAttributeToGroup('catalog_product', $attributeSetId, $attribute['attributeGroup'], $attribute['attributeCode']);
    }
}
 
$installer->endSetup();

