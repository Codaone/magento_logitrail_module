<?php

class Codaone_Logitrail_Adminhtml_LogitrailbackendController extends Mage_Adminhtml_Controller_Action
{
    public function addproductAction() {
        $helper = Mage::helper('logitrail');
        $productId = Mage::app()->getRequest()->getParam('prid');
        $result = Mage::getModel('logitrail/logitrail')->addProducts(array($productId));
        if ($result === true) {
             Mage::getSingleton('core/session')->addSuccess($this->__('Product successfully added to Logitrail')); 
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Error: Adding product failed: ') . $helper->escapeHtml($result));
        }

    Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')
        ->getUrl("adminhtml/catalog_product/edit", array('id'=>$productId)));
    }

    public function masscreateAction() {
         $helper = Mage::helper('logitrail');
         $products = $this->getRequest()->getPost('product');
         $result = Mage::getModel('logitrail/logitrail')->addProducts($products);

       if ($result === true) {
             Mage::getSingleton('core/session')->addSuccess($this->__('Products successfully added to Logitrail')); 
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Error: Adding products failed: ') . $helper->escapeHtml($result));
        }

    Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')
        ->getUrl("adminhtml/catalog_product/"));
    }

}
