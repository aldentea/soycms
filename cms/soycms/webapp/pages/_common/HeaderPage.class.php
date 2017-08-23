<?php

class HeaderPage extends CMSWebPageBase{

	var $title = "";

	function setTitle($title){
		$this->title = $title;
	}

    function __construct(){
		parent::__construct();

		HTMLHead::addLink("bootstrap.min.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/vendor/bootstrap/css/bootstrap.min.css") . "?" . SOYCMS_BUILD_TIME
		));
		HTMLHead::addLink("metisMenu.min.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/vendor/metisMenu/metisMenu.min.css") . "?" . SOYCMS_BUILD_TIME
		));
		HTMLHead::addLink("sb-admin-2.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/dist/css/sb-admin-2.css") . "?" . SOYCMS_BUILD_TIME
		));
		HTMLHead::addLink("soycms_cp.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/dist/css/soycms_cp.css") . "?" . SOYCMS_BUILD_TIME
		));
		HTMLHead::addLink("morris.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/vendor/morrisjs/morris.css") . "?" . SOYCMS_BUILD_TIME
		));
		HTMLHead::addLink("font-awesome.min.css", array(
			"type" => "text/css",
			"rel" => "stylesheet",
			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/vendor/font-awesome/css/font-awesome.min.css") . "?" . SOYCMS_BUILD_TIME
		));
// 		HTMLHead::addLink("jquery-ui.min.css",array(
// 			"rel" => "stylesheet",
// 			"type" => "text/css",
// 			"href" => SOY2PageController::createRelativeLink("./webapp/pages/files/vendor/jquery-ui/jquery-ui.min.css") . "?" . SOYCMS_BUILD_TIME
// 			//"href" => SOY2PageController::createRelativeLink("./css/jquery-ui/themes/base/minified/jquery-ui.min.css")."?".SOYCMS_BUILD_TIME
// 		));

    }

    function execute(){
    	$this->createAdd("header", "HTMLHead", array(
			"title" => $this->title,
			"isEraseHead" => false
		));
    }
}
?>