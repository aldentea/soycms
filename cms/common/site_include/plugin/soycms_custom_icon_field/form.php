<style>
.src_display{
	border: #000000 solid 1px;
	padding: 2px;
	width: 300px;
}

.icon_table{

}
.icon_table tr th{
	padding: 10px;
	width: 125px;
	height: auto;
	border-left: #000000 solid 1px;
	border-bottom: #000000 solid 1px;
}


.icon_table tr td{
	padding: 10px;
	border-left: #000000 solid 1px;
	border-bottom: #000000 solid 1px;
	border-right: #000000 solid 1px;
	width: auto;
	height: auto;
}

.icon_table_td{
	border-top: #000000 solid 1px;
}

.icon_table tr td input{
	margin: 0 18px 0 2px;
}
</style>


<h4>使用方法</h4>
<pre class="src_display">&lt;!-- cms:id="custom_icon_field" /--&gt;</pre>
<p>記事を表示するブロック内に上記のコードを加えると、記事を投稿したときに設定したアイコンを表示することが出来ます</p>
<br />
<div>
<form method="post" enctype="multipart/form-data">
<div class="table-responsive">
<table class="table">
	<tr>
		<th class="icon_table_td">アイコンディレクトリ</th>
		<td class="icon_table_td"><?php echo UserInfoUtil::getSiteDirectory().$this->iconDirecotry; ?></td>
	</tr>
	<tr>
		<th>登録アイコン一覧</th>
		<td><?php
				$files = @scandir(UserInfoUtil::getSiteDirectory().$this->iconDirecotry);
				$getUrl = UserInfoUtil::getSiteURL();
				if(!$files)$files=array();
				$hasFiles = false;
				foreach($files as $file){
					if($file[0] == ".")continue;
					$hasFiles = true;
					echo '<label for="'. md5($file) .'"><img src="'.htmlspecialchars(substr($getUrl, 0, strrpos($getUrl, "/"))."/".$this->iconDirecotry."/".$file,ENT_QUOTES,'UTF-8').'" />';
					echo '<input id="'. md5($file) .'" type="checkbox" name="deletes[]" value="'. htmlspecialchars($file,ENT_QUOTES,'UTF-8'). '" />';
				}
		?>&nbsp;</td>
	</tr>
</table>
</div>
<?php if($hasFiles){ ?>
<p><span>チェックを入れたものを削除する</span><input type="submit" name="delete" onclick="return confirm('削除してもよろしいですか？');" value="削除"/></p><br />
<?php } ?>

<h4>アイコンの追加 <?php if(isset($message)){echo "<font color =\"#ff0000\">" .$message . "</font>";} ?></h4>
<div style="padding: 0 0 0 20px;">
	<input type="file" name="file" id="file" style="display:inline-block"> <input type="submit" value="アップロード" class="btn btn-primary">
</div>
</form>
</div>