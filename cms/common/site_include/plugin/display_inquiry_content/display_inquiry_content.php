<?php
SOY2::import("domain.cms.Entry");
DisplayInquiryContentPlugin::register();

class DisplayInquiryContentPlugin{

	const PLUGIN_ID = "display_inquiry_content";

	private $formId;
	private $connects = array();							//お問い合わせと記事の連携設定
	private $labelId;										//記事の自動作成時に紐付けるラベル
	private $isPublished = 0;								//記事の自動作成時に注文状態をどのようにして作成するか？
	private $lastInquiryTime = 0;							//直近でいつお問い合わせがあったか？
	private $lastEntryImportTime = 0;						//直近でいつお問い合わせを記事として登録したか？

	function getId(){
		return self::PLUGIN_ID;
	}

	function init(){

		CMSPlugin::addPluginMenu(self::PLUGIN_ID,array(
			"name"=>"お問い合わせ内容表示プラグイン",
			"description"=>"お問い合わせ内容を記事に格納して、ブロックでお問い合わせ内容の一部を出力する",
			"author"=>"齋藤毅",
			"modifier"=>"Tsuyoshi Saito",
			"url"=>"https://saitodev.co",
			"mail"=>"tsuyoshi@saitodev.co",
			"version"=>"0.9"
		));

		if(CMSPlugin::activeCheck(self::PLUGIN_ID)){

			CMSPlugin::addPluginConfigPage(self::PLUGIN_ID,array(
				$this,"config"));

			CMSPlugin::setEvent('onOutput', self::PLUGIN_ID, array($this, "onOutput"), array("filter" => "all"));
			CMSPlugin::setEvent('onEntryOutput', self::PLUGIN_ID, array($this, "display"));
		}
	}

	function onOutput($arg){

		//アプリケーションページでお問い合わせフォームの完了ページを読み込んでいることを確認
		if(isset($_GET["complete"]) && isset($_GET["trackid"]) && get_class($arg["webPage"]->page) === "ApplicationPage" && $arg["webPage"]->page->getApplicationId() === "inquiry"){

			//プラグインで登録しているお問い合わせフォームか？調べる
			preg_match('/app:formid="(.*)"/', $arg["webPage"]->page->getTemplate(), $tmp);
			if(isset($tmp[1])){
				$formId = trim($tmp[1]);
				SOY2::import("site_include.plugin.display_inquiry_content.util.DisplayInquiryContentUtil");
				if($this->getFormId() == DisplayInquiryContentUtil::getIdByFormId($formId)){
					//お問い合わせ完了のページを見ている場合はプラグインに最終お問い合わせ時間を記録する
					$this->lastInquiryTime = time();
					CMSPlugin::savePluginConfig($this->getId(), $this);
				}
			}
		}

		//時々最終お問い合わせの日を確認しにいくためのフラグ
		$r = (int)mt_rand(1,10);

		//最終お問い合わせ時刻が常に0(他サイトにフォームを設置している)の場合は常に最終お問い合わせ時刻を調べに行く
		if($r < 2 || $this->lastInquiryTime === 0){
			SOY2::import("site_include.plugin.display_inquiry_content.util.DisplayInquiryContentUtil");
			$this->lastInquiryTime = DisplayInquiryContentUtil::getLastInquiryTime($this->getFormId());
		}

		//お問い合わせのデータベースから記事を登録する
		if($this->lastEntryImportTime < $this->lastInquiryTime && count($this->connects)){
			SOY2::import("site_include.plugin.display_inquiry_content.util.DisplayInquiryContentUtil");
			$v = DisplayInquiryContentUtil::getInquiryContentsAndDateByFormIdAfterSpecifiedTime($this->getFormId(), $this->lastEntryImportTime);
			$numbers = $v[0];
			$contents = $v[1];
			$datas = $v[2];
			$dates = $v[3];
			if(count($datas)){
				$dao = SOY2DAOFactory::create("cms.EntryDAO");
				$attrDao = SOY2DAOFactory::create("cms.EntryAttributeDAO");
				$entryLabelDao = SOY2DAOFactory::create("cms.EntryLabelDAO");

				for($i = 0; $i < count($datas); $i++){

					//$error = true;
					$error = false;

					$dao->begin();

					//記事の新規作成
					$entry = new Entry();
					$entry->setTitle("お問い合わせ" . date("Y-m-d H:i:s", $dates[$i]));
					$entry->setContent(str_replace("\n", "<br>", htmlspecialchars($contents[$i], ENT_QUOTES, "UTF-8")));
					$entry->setCdate($dates[$i]);
					$entry->setUdate($dates[$i]);
					$entry->setIsPublished($this->isPublished);

					try{
						$entryId = $dao->insert($entry);
					}catch(Exception $e){
						$error = true;
					}

					//ラベルの設定
					if(!$error && is_numeric($this->labelId) && (int)$this->labelId > 0){
						$entryLabel = new EntryLabel();
						$entryLabel->setEntryId($entryId);
						$entryLabel->setLabelId($this->labelId);
						try{
							$entryLabelDao->insert($entryLabel);
						}catch(Exception $e){
							$error = true;
						}
					}

					if(!$error){
						//カスタムフィールドを登録
						$data = $datas[$i];
						foreach($this->connects as $columnId => $fieldId){
							$attr = new EntryAttribute();
							$attr->setEntryId($entryId);
							$attr->setFieldId($fieldId);

							//通常のカラム
							if(isset($data[$columnId])){
								$attr->setValue(htmlspecialchars($data[$columnId], ENT_QUOTES, "UTF-8"));
							//お問い合わせ番号
							}else if($columnId == "tracking_number"){
								$attr->setValue($numbers[$i]);
							//お問い合わせ日時
							}else if($columnId = "create_date"){
								$attr->setValue($dates[$i]);
							}else{
								continue;
							}

							try{
								$attrDao->insert($attr);
							}catch(Exception $e){
								$error = true;
								break;
							}
						}
					}

					//エラーがあった時点で登録を止める
					if($error) break;

					$dao->commit();

					//最終登録日を記録
					$this->lastEntryImportTime = $dates[$i];
					CMSPlugin::savePluginConfig($this->getId(), $this);
				}
			}
		}
	}

	function display($arg){
		if(isset($this->connects["create_date"])){
			$entryId = $arg["entryId"];
			$htmlObj = $arg["SOY2HTMLObject"];

			try{
				$v = (int)SOY2DAOFactory::create("cms.EntryAttributeDAO")->get($entryId, $this->connects["create_date"])->getValue();
			}catch(Exception $e){
				$v = null;
			}

			$htmlObj->createAdd($this->connects["create_date"] . "_inquiry_date", "DateLabel", array(
				"soy2prefix"=>"cms",
				"text" => $v
			));
		}
	}

	/**
	 * 設定画面表示
	 * @return HTML
	 */
	function config(){
		SOY2::import("site_include.plugin.display_inquiry_content.config.DisplayInquiryContentConfigPage");
		$form = SOY2HTMLFactory::createInstance("DisplayInquiryContentConfigPage");
		$form->setPluginObj($this);
		$form->execute();
		return $form->getObject();
	}

	function getFormId(){
		return $this->formId;
	}
	function setFormId($formId){
		$this->formId = $formId;
	}

	function getConnects(){
		return $this->connects;
	}
	function setConnects($connects){
		$this->connects = $connects;
	}

	function getLabelId(){
		return $this->labelId;
	}
	function setLabelId($labelId){
		$this->labelId = $labelId;
	}

	function getIsPublished(){
		return $this->isPublished;
	}
	function setIsPublished($isPublished){
		$this->isPublished = $isPublished;
	}

	public static function register(){
		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new DisplayInquiryContentPlugin();
		}
		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));
	}
}
