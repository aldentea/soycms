<?php

class CompletePage extends MainMyPagePageBase{
	
    function __construct() {
    	WebPage::__construct();
    	
    	$this->addLink("login_link", array(
    		"link" => soyshop_get_mypage_url() . "/login"
    	));
    }
}
?>