<?php

$z=new Zipper();

class Zipper{
	public $zip;
	function __construct(){
		$this->zip=new ZipArchive();
	}
	public function enzip($dir){
		$this->zip->open(__DIR__.'/'.basename($dir).'.zip', ZipArchive::CREATE|ZipArchive::OVERWRITE);
		$this->add_dir($dir);
		$this->zip->close();
	}
	public function add_dir($dir){
		foreach(glob($dir.'/*') as $f){
			if(is_dir($f)){$this->add_dir($f);}
			else{$this->zip->addFile($f);}
		}
	}
	public function unzip($f){
		$this->zip->open(__DIR__.'/'.$f);
		$this->zip->extractTo(__DIR__);
		$this->zip->close();
	}
}
$msgs=[];
if(!empty($_POST['enzip'])){
	foreach($_POST['enzip'] as $dir){$z->enzip($dir);$msgs[]=$dir.'を圧縮';}
}
if(!empty($_POST['unzip'])){
	foreach($_POST['unzip'] as $f){$z->unzip($f);$msgs[]=$f.'を解凍';}
}

?><!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Zipper</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
</head>

<body>
<form action="zipper.php" method="post">
 	<fieldset>
 		<legend>圧縮</legend>
		<?php foreach(glob('*',GLOB_ONLYDIR) as $dir): ?>
		<label><input type="checkbox" name="enzip[]" value="<?=$dir?>"><?=$dir?></label>
		<?php endforeach; ?>
 	</fieldset>
	<fieldset>
 		<legend>解凍</legend>
		<?php foreach(glob('*.zip') as $f): ?>
		<label><input type="checkbox" name="unzip[]" value="<?=$f?>"><?=$f?></label>
		<?php endforeach; ?>
 	</fieldset>
	<button type="submit">実行</button>
</form>
<?php
if(!empty($msgs)){
	foreach($msgs as $msg){
		echo '<p class="message">'.$msg.'</p>';
	}
}
?>
</body>
</html>