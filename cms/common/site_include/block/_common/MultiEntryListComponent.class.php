<?php

class MultiEntryListComponent extends HTMLList{

	private $url = array();
	private $blogTitle = array();
	private $blogUrl = array();

	private $dsn;

	public function setUrl($url){
		$this->url = $url;
	}

	public function setBlogTitle($blogTitle){
		$this->blogTitle = $blogTitle;
	}

	public function setBlogUrl($blogUrl){
		$this->blogUrl = $blogUrl;
	}

	public function getStartTag(){

		return parent::getStartTag();
	}

	/**
	 * 実行前後にDSNの書き換えを実行
	 */
	public function execute(){

		if($this->dsn)$old = SOY2DAOConfig::Dsn($this->dsn);

		parent::execute();

		if($this->dsn) SOY2DAOConfig::Dsn($old);
	}

	protected function populateItem($entity){
		//entry title
		$url = (isset($this->url[$entity->getId()])) ? $this->url[$entity->getId()] : "" ;

		$hTitle = htmlspecialchars($entity->getTitle(), ENT_QUOTES, "UTF-8");
		$entryUrl = ( strlen($url) > 0 ) ? $url.rawurlencode($entity->getAlias()) : "" ;

		if(strlen($entryUrl) > 0){
			$hTitle = "<a href=\"".htmlspecialchars($entryUrl, ENT_QUOTES, "UTF-8")."\">".$hTitle."</a>";
		}

		//blog title
		$blogUrl = (isset($this->blogUrl[$entity->getId()])) ? $this->blogUrl[$entity->getId()] : "";
		$blogTitle = (isset($this->blogTitle[$entity->getId()])) ? $this->blogTitle[$entity->getId()] : "";
		$hBlogTitle = htmlspecialchars($blogTitle, ENT_QUOTES, "UTF-8");

		if(strlen($blogUrl) > 0){
			$hBlogTitle = "<a href=\"".htmlspecialchars($blogUrl, ENT_QUOTES, "UTF-8")."\">".$hBlogTitle."</a>";
		}

		$this->createAdd("entry_id","CMSLabel",array(
			"text" => $entity->getId(),
			"soy2prefix" => "cms"
		));

		$this->createAdd("title","CMSLabel",array(
			"html" => $hTitle,
			"soy2prefix" => "cms"
		));
		$this->createAdd("content","CMSLabel",array(
			"html" => $entity->getContent(),
			"soy2prefix" => "cms"
		));
		$this->createAdd("more","CMSLabel",array(
			"html" => $entity->getMore(),
			"soy2prefix" => "cms"
		));
		$this->createAdd("create_date","DateLabel",array(
			"text" => $entity->getCdate(),
			"soy2prefix" => "cms"
		));

		$this->createAdd("create_time","DateLabel",array(
			"text" => $entity->getCdate(),
			"soy2prefix" => "cms",
			"defaultFormat"=>"H:i"
		));

		//entry_link追加
		$this->addLink("entry_link", array(
			"link" => $entryUrl,
			"soy2prefix" => "cms"
		));

		//リンクの付かないタイトル 1.2.6～
		$this->createAdd("title_plain","CMSLabel",array(
			"text" =>  $entity->getTitle(),
			"soy2prefix" => "cms"
		));

		//1.2.7～
		$this->addLink("more_link", array(
			"soy2prefix" => "cms",
			"link" => $entryUrl ."#more",
			"visible"=>(strlen($entity->getMore()) != 0)
		));

		$this->addLink("more_link_no_anchor", array(
			"soy2prefix" => "cms",
			"link" => $entryUrl,
			"visible"=>(strlen($entity->getMore()) != 0)
		));

		//Blog Title link
		$this->createAdd("blog_title", "CMSLabel", array(
			"html" => $hBlogTitle,
			"soy2prefix" => "cms"

		));

		//Blog Title plain
		$this->createAdd("blog_title_plain", "CMSLabel" , array(
			"text" => $blogTitle,
			"soy2prefix" => "cms"
		));

		//Blog link
		$this->addLink("blog_link", array(
			"link" => $blogUrl,
			"soy2prefix" => "cms"
		));

		//1.7.5~
		$this->createAdd("update_date","DateLabel",array(
			"text" => $entity->getUdate(),
			"soy2prefix" => "cms",
		));

		$this->createAdd("update_time","DateLabel",array(
			"text" => $entity->getUdate(),
			"soy2prefix" => "cms",
			"defaultFormat"=>"H:i"
		));

		$this->addLabel("entry_url", array(
			"text" => $entryUrl,
			"soy2prefix" => "cms",
		));

		CMSPlugin::callEventFunc('onEntryOutput', array("entryId" => $entity->getId(), "SOY2HTMLObject" => $this, "entry" => $entity));
	}


	public function getDsn() {
		return $this->dsn;
	}
	public function setDsn($dsn) {
		$this->dsn = $dsn;
	}
}
