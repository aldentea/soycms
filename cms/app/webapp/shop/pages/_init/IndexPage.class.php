<?php
class IndexPage extends WebPage{

	function __construct(){
		WebPage::WebPage();
		
		$this->addLink("login_link", array(
			"link" => SOY2PageController::createRelativeLink("../soyshop?site_id=".SOYSHOP_ID)
		));
	}

}
?>