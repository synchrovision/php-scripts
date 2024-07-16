<?php
class SimpleBrokenLinkFinder{
	public static function search($root_dir,$target_dir=''){
		$results=[];
		$dir=rtrim($root_dir.'/'.trim($target_dir,'/'),'/');
		foreach(scandir($dir) as $fname){
			if($fname[0]==='.' || $fname[0]==='_'){continue;}
			$f=$dir.'/'.$fname;
			if(is_dir($f)){
				if(in_array($fname,['wp-content','wp-includes','wp-admin','node_modules'],true)){continue;}
				if($fname==='vendor' && file_exists($f.'/autoload.php')){continue;}
				$results=array_merge_recursive($results,self::search($root_dir,$target_dir.'/'.$fname));
				continue;
			}
			$ext=strrchr($fname,'.');
			if(preg_match('/^\.(html?|php)$/',$ext)){
				preg_match_all('/(src|srcset|href)\s*=\s*([\'"])(.+?)\2/m',file_get_contents($f),$matches,\PREG_SET_ORDER);
				foreach(array_column($matches,3) as $url){
					if($root_path=self::get_root_path($url,$target_dir)){
						if(!file_exists($root_dir.$root_path)){
							$results[$root_path][$target_dir.'/'.$fname]=$target_dir.'/'.$fname;
						}
					}
				}
			}
		}
		return $results;
	}
	public static function get_root_path($path,$base_dir){
		if(
			$path[0]==='#' ||
			preg_match('/^\w+:/',$path) ||
			strpos($path,'//')===0 ||
			strpos($path,'$')!==false || 
			preg_match('/%\d*s/',$path) || 
			strpos($path,'{')!==false || 
			strpos($path,'[')!==false || 
			strpos($path,'<?')!==false || 
			strpos($path,'+')!==false || 
			strpos($path,'"')!==false || 
			strpos($path,"'")!==false
		){return false;}
		if(strpos($path,'?')!==false){$path=strstr($path,'?',true);}
		if(strpos($path,'#')!==false){$path=strstr($path,'#',true);}
		if(empty($path)){return false;}
		if($path[0]!=='/'){
			$path='/'.$base_dir.'/'.$path;
			$path=str_replace('//','/',$path);
			$path=str_replace('/./','/',$path);
			while(strpos($path,'/../')){
				$path=preg_replace('@/[^/]+/\.\./@m','/',$path);
			}
		}
		return rtrim($path,'/');
	}
}
$url=sprintf("%s://%s/",$_SERVER['HTTPS']?'https':'http',$_SERVER['HTTP_HOST']);
$dir=trim(dirname($_SERVER['REQUEST_URI']),'/');
$level=empty($dir)?0:(substr_count($dir,'/')+1);
$results=SimpleBrokenLinkFinder::search($level?dirname(__DIR__,$level):__DIR__,$dir);
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Broken Links</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</head>
<body>
	<h2 class="h2 p-4 text-center">Broken Links</h2>
	<div class="p-4">
		<table class="table">
			<thead class="table-light">
				<tr>
					<th>Missing URL</th>
					<th>in Page</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($results as $url=>$pages): ?>
				<tr>
					<th><?=$url?></th>
					<td>
						<?php foreach($pages as $page): ?>
						<a href="<?=$url.$page?>" target="_blank"><?=$page?></a>　
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</body>
</html>