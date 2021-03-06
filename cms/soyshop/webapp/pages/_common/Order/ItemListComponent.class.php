<?php

class ItemListComponent extends HTMLList{

	private $categories = array();
    private $detailLink;

    protected function populateItem($item, $idx) {
		
		$this->addLabel("index", array(
			"text" => $idx
		));

        $this->addLabel("item_id", array(
            "text" => $item->getId()
        ));

		$imagePath = soyshop_convert_file_path_on_admin($item->getAttribute("image_small"));
		if(!strlen($imagePath)) $imagePath = "/" . SOYSHOP_ID . "/themes/sample/noimage.jpg";
		$this->addImage("item_small_image", array(
            "src" => SOYSHOP_SITE_URL . "im.php?src=" . $imagePath . "&width=60",
        ));

        $this->addLabel("item_name", array(
            "text" => $item->getOpenItemName()
        ));

        $this->addLabel("item_code", array(
            "text" => $item->getCode()
        ));

		$this->addLabel("item_category", array(
            "text" => (isset($this->categories[$item->getCategory()])) ? $this->categories[$item->getCategory()] : "-"
        ));

        $this->addLabel("item_price", array(
            "text" => number_format((int)$item->getPrice())
        ));

        $this->addLabel("item_stock", array(
            "text" => number_format($item->getStock())
        ));

        // $this->addLabel("item_category", array(
        //     "text" => $text
        // ));

        $detailLink = $this->getDetailLink() . $item->getId();
        $this->addLink("detail_link", array(
            "link" => $detailLink
        ));

		//在庫切れ

		//iframeのchangeにあるindexを出力
		$this->addLabel("iframe_index", array(
			"text" => (isset($_GET["change"]) && is_numeric($_GET["change"])) ? $_GET["change"] : 0
		));
    }

	function setCategories($categories){
		$this->categories = $categories;
	}


    function getDetailLink() {
        return $this->detailLink;
    }
    function setDetailLink($detailLink) {
        $this->detailLink = $detailLink;
    }
}
