<?php
chdir(__DIR__);
if(php_sapi_name()!=='cli'){
	echo 'This program should be executed with CLI';
	return;
}
if(empty($argv[1])){
	echo 'require site url as first argument';
	return;
}
ini_set("memory_limit", "8G");

define('START_URL',$argv[1]);
define('HOST',parse_url(START_URL,PHP_URL_HOST));

global $results;
$results=[];
$csv=fopen('link_list.csv','w');

fputcsv($csv,['link_text','page_title','url','referer','code','redirect']);
rec_scanlink('should_listup','Top',START_URL,START_URL);
foreach($results as $row){
	fputcsv($csv,$row);
}

function rec_scanlink($cb,$link_text,$url,$ref){
	global $results;
	echo "{$url}\n";
	$ch=curl_init($url);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
	ob_start();
	curl_exec($ch);
	$contents=ob_get_clean();
	$info=curl_getinfo($ch);
	$results[$url]=[$link_text,empty($contents)?'(empty)':extract_title($contents),$url,$ref,$info['http_code'],$info['redirect_url']];
	if(strpos($contents,'<html')!==false){
		foreach(extract_links($contents) as $row){
			$row[1]=normalize_link($row[1],$url);
			if(empty($results[$row[1]]) && $cb($row[1])){
				rec_scanlink($cb,$row[0],$row[1],$url);
			}
		}
	}
}
function extract_links($html){
	$rtn=[];
	preg_match_all('/<a (.+?)>(.+?)<\/a>/us',$html,$aTagMatches);
	foreach($aTagMatches[1] as $i=>$atts){
		if(preg_match('/href=([\'"])(.*?)\1/',$atts,$hrefMatches)){
			$url=$hrefMatches[2];
			$text=$aTagMatches[2][$i];
			$label=trim(preg_replace('/\s+/',' ',strip_tags($text)));
			if(empty($label)){
				$label='？';
				if(preg_match('/alt=([\'"])(.*?)\1/us',$text,$altMatches) && !empty($altMatches[2])){
					$label=$altMatches[2];
				}
			}
			$rtn[]=[$label,$url];
		}				
	}
	return $rtn;
}
function extract_title($html){
	if(preg_match('/<title>\s*(.+)\s*<\/title>/su',$html,$matches)){
		return $matches[1];
	}
	return 'No Title';
}
function normalize_link($href,$baseUrl){
	if(preg_match('/^(javascript|mailto|tel):/',$href)){return $href;}
	if(substr($href,0,1)==='#'){return $href;}
	if(strpos(strstr($href,'?'),'://')!==false){
		$href=strstr($href,'?',true);
	}
	if(empty($href)){
		return $baseUrl;
	}
	else if(strpos($href,'://')!==false && strpos($href,'://')<=5){
		$url=$href;
	}
	else if($href[0]==='/'){
		if(preg_match('@^(.*://[^/]+)@',$baseUrl,$matches)){
			$url=$matches[1].$href;
		}
		else{
			echo "error : cannot extract host from {$baseUrl} \n";
		}
	}
	else{
		$url=dirname($baseUrl).'/'.$href;
	}
	$url=preg_replace('/#.+$/','',$url);
	$url=preg_replace('@/[^/]+/\.\./@','/',$url);
	$url=str_replace(['/./','//'],'/',$url);
	$url=preg_replace('@/[^/]+/\.\./@','/',$url);
	$url=str_replace(['/./','//'],'/',$url);
	$url=preg_replace('@/[^/]+/\.\./@','/',$url);
	$url=str_replace(['/./','//'],'/',$url);
	$url=str_replace(':/','://',$url);
	return $url;
}
function should_listup($url){
	if(strpos($url,'?')!==false){$url=strstr($url,'?',true);}
	if(strpos($url,'#')!==false){$url=strstr($url,'#',true);}
	$ext=strrchr($url,'.');
	return parse_url($url,PHP_URL_HOST)===HOST;
}