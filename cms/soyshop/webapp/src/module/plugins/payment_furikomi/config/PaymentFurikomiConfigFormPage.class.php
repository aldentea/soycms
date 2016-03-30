<?php

class PaymentFurikomiConfigFormPage extends WebPage{
	
	private $config;
	
	function PaymentFurikomiConfigFormPage(){
		SOY2::import("module.plugins.payment_furikomi.util.PaymentFurikomiUtil");
	}
	
	function doPost(){
		if(isset($_POST["payment_furikomi"])){
			PaymentFurikomiUtil::saveConfigText($_POST["payment_furikomi"]);
			$this->config->redirect("updated");
		}
	}
	
	function execute(){
		WebPage::WebPage();
		
		$configText = PaymentFurikomiUtil::getConfigText();

		$this->addTextArea("account", array(
			"value" => $configText["account"],
			"name"  => "payment_furikomi[account]"
		));
		$this->addTextArea("text", array(
			"value" => $configText["text"],
			"name"  => "payment_furikomi[text]"
		));
		$this->addTextArea("mail", array(
			"value" => $configText["mail"],
			"name"  => "payment_furikomi[mail]"
		));
	}
	
	function setConfigObj($obj) {
		$this->config = $obj;
	}
}
?>