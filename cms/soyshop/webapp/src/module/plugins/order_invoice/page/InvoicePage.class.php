<?php
class InvoicePage extends HTMLTemplatePage{

	protected $id;
	protected $logic;

	function setOrderId($id){
		$this->id = $id;
	}

	function build_invoice(){
		SOY2::import("module.plugins.order_invoice.common.OrderInvoiceCommon");
		SOY2::imports("module.plugins.order_invoice.component.*");

		$order = self::getOrder();

		/*** 注文情報 ***/
		$this->createAdd("continuous_print", "InvoiceListComponent", array(
			"list" => array($order),
			"itemOrderDao" => SOY2DAOFactory::create("order.SOYShop_ItemOrderDAO"),
			"userDao" => SOY2DAOFactory::create("user.SOYShop_UserDAO"),
			"itemDao" => SOY2DAOFactory::create("shop.SOYShop_ItemDAO"),
			"config" => OrderInvoiceCommon::getConfig()
		));

		SOY2Logic::createInstance("module.plugins.order_invoice.logic.LogLogic")->save($order);
	}

	private function getOrder(){
		return SOY2Logic::createInstance("logic.order.OrderLogic")->getById($this->id);
	}
}
