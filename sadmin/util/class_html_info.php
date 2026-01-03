<?php
/**
* 02/12/24 - http://www.phpclasses.org/browse.html/package/919.html 
* Class for getting general informations about html content
* @author	Sven Wagener <sven.wagener@intertribe.de>
* @copyright Intertribe - Internetservices Germany
* @include 	 Funktion:_include_
*/
class html_info{
	
	var $string="";
	var $meta="";
	
	
	/**
	* Constructor of class html_info
	* @param string $html_string The whole HTML document as String
	* @desc Constructor of class html_info
	*/	
	function html_info($html_string){
		$this->string=$html_string;
	}
	
	/**
	* Returns the title
	* @return string $title the title of the HTML document
	* @desc Constructor of class html_info
	*/		
	function get_title(){
		$string=strtolower($this->string);
		preg_match_all("|<title>(.*)</title>|U",$string,$matches, PREG_PATTERN_ORDER);
		
		return $matches['1'][0];
	}
	
	/**
	* Returns the meta data
	* @return array $matches the title of the HTML document
	* @desc Returns the meta data of the HTML document in an array ($matches[$i]['name'] and $matches[$i]['content'])
	*/		
	function get_meta_data(){
		$string=strtolower($this->string);
		preg_match_all("|<meta (.*)>|U",$string,$matches, PREG_PATTERN_ORDER);
		
		$k=0;
		$tmp_match_array="";
		
		// Putting all matches in an array
		for($i=0;$i<count($matches);$i++){
			for($j=0;$j<count($matches[$i]);$j++){
				if($matches[$i][$j]!=""){
					$tmp_match_array[$k]=$matches[$i][$j];
					$k++;
				}
			}
		}
		
		$matches="";
		
		// Getting detailed information of meta data and putting in array
		$k=0;
		for($i=0;$i<count($tmp_match_array);$i++){
			
			// Getting name
			preg_match_all("|name\=\"(.*)\" |U",$tmp_match_array[$i],$name_matches, PREG_PATTERN_ORDER);
			// Checking if entry not exists
			$found=false;
			for($j=0;$j<count($matches);$j++){
				if($matches[$j]['name']==$name_matches['1'][0]){
					$found=true;
				}
			}
			if(!$found && $name_matches['1'][0]!=""){
				$matches[$k]['name']=$name_matches['1'][0];
				
				// Getting content
				preg_match_all("|content\=\"(.*)\"|U",$tmp_match_array[$i],$content_matches, PREG_PATTERN_ORDER);
				$matches[$k]['content']=$content_matches['1'][0];
				$k++;
			}
		}
		
		$this->meta=$matches;
		return $matches;
	}
	
	/**
	* Returns all images
	* @return array $match the pictures and all information in an array
	* @desc Returns all images in an array ($match[$i]['src'], $match[$i]['alt'], $match[$i]['width'] and $match[$i]['height'])
	*/		
	function get_images(){
		$string=strtolower($this->string);
		preg_match_all("|<img (.*)>|U",$string,$matches, PREG_PATTERN_ORDER);
		
		// Putting all matches in an array
		for($i=0;$i<count($matches);$i++){
			for($j=0;$j<count($matches[$i]);$j++){
				if($matches[$i][$j]!=""){
					$tmp_match_array[$k]=$matches[$i][$j];
					$k++;
				}
			}
		}
		$k=0;
		for($i=0;$i<count($tmp_match_array);$i++){
			$found=false;
			for($j=0;$j<count($match);$j++){
				if($this->get_tag_param("src",$tmp_match_array[$i])==$match[$j]['src']){
					$found=true;
				}
			}
			if(!$found && $this->get_tag_param("src",$tmp_match_array[$i])!=""){
				$match[$k]['src']=$this->get_tag_param("src",$tmp_match_array[$i]);
				$match[$k]['alt']=$this->get_tag_param("alt",$tmp_match_array[$i]);
				$match[$k]['width']=$this->get_tag_param("width",$tmp_match_array[$i]);
				$match[$k]['height']=$this->get_tag_param("height",$tmp_match_array[$i]);
				$k++;
			}
		}
		
		return $match;
	}
	
	/**
	* Returns all links
	* @return array $match the links and all information in an array
	* @desc Returns all links in an array ($match[$i]['href'] and $match[$i]['target'])
	*/		
	function get_links(){
		$string=strtolower($this->string);
		preg_match_all("|<a (.*)>|U",$string,$matches, PREG_PATTERN_ORDER);
		
		// Putting all matches in an array
		for($i=0;$i<count($matches);$i++){
			for($j=0;$j<count($matches[$i]);$j++){
				if($matches[$i][$j]!=""){
					$tmp_match_array[$k]=$matches[$i][$j];
					// echo $tmp_match_array[$k]."<br>\n";
					$k++;
				}
			}
		}
		
		$k=0;
		for($i=0;$i<count($tmp_match_array);$i++){
			$found=false;
			for($j=0;$j<count($match);$j++){
				if($this->get_tag_param("href",$tmp_match_array[$i])==$match[$j]['href']){
					$found=true;
				}
			}
			if(!$found && $this->get_tag_param("href",$tmp_match_array[$i])!=""){
				$match[$k]['href']=$this->get_tag_param("href",$tmp_match_array[$i]);
				$match[$k]['target']=$this->get_tag_param("target",$tmp_match_array[$i]);
				$k++;
			}
		}
		
		return $match;
	}
	
	/**
	* Returns all strings which are formated like the given parameter
	* @param boolean $bold if string have to be formatted bold choose true
	* @param boolean $italic if string have to be formatted italic choose true
	* @param boolean $underlined if string have to be formatted underlined choose true
	* @return array $strings the strings which have been found in an array
	* @desc Returns all strings in an array which are formated like the given parameter
	*/			
	function get_strings_formated($bold,$italic,$underlined){
		$i=0;
		if($bold){
			$tags[$i]['open']="<b>";
			$tags[$i]['close']="</b>";
			$i++;
		}
		if($italic){
			$tags[$i]['open']="<i>";
			$tags[$i]['close']="</i>";
			$i++;
		}
		if($underlined){
			$tags[$i]['open']="<u>";
			$tags[$i]['close']="</u>";
			$i++;
		}
		
		$strings=$this->get_strings_in_tags($tags,$this->string);
		
		return $strings;
	}
	
	/**
	* Returns all strings in $string which are given to the parameter $tags
	* @param array $tags the tags in an array ($tags[$i]['open'] and $tags[$i]['close'])
	* @param string $string the HTML string
	* @return array $strings the strings which have been found in an array
	* @desc Returns all strings in $string which are given to the parameter $tags
	*/		
	function get_strings_in_tags($tags,$string){
		for($i=0;$i<count($tags);$i++){
			$k=0;
			$pattern="|".$tags[$i]['open']."(.*)".$tags[$i]['close']."|U";
			preg_match_all($pattern,$string,$matches, PREG_PATTERN_ORDER);
			
			// Getting rest of all Tags
			for($j=0;$j<count($tags);$j++){
				if($tags[$j]['open']!=$tags[$i]['open'] && $tags[$j]['close']!=$tags[$i]['close']){
					$new_tags[$k]=$tags[$j];
					$k++;
				}
			}
			// Getting Strings from all matches
			for($j=0;$j<count($matches['1']);$j++){
				$new_string=$matches['1'][$j];
			}
			
			if(count($tags)==1){
				for($j=0;$j<count($matches['1']);$j++){
					$end_matches[$j]=strip_tags($matches['1'][$j]);
				}
				return $end_matches;
			}else{
				for($j=0;$j<count($matches['1']);$j++){
					$new_string=$matches['1'][$j];
					$end_matches=array_merge($this->get_strings_in_tags($new_tags,$new_string),$end_matches);
				}
			}
		}
		return $end_matches;
	}
	
	/**
	* Returns all strings in $string which are between the start and end tag
	* @param string $start_tag the starting tag
	* @param string $end_tag the end tag
	* @param string $string the string to search for
	* @return array $strings the strings which have been found pusched in an array
	* @desc Returns all strings in $string which are between the start and end tag
	*/	
	function get_strings_in_tag($start_tag,$end_tag,$string){
		$pattern="|".$start_tag."(.*)".$end_tag."|U";
		preg_match_all($pattern,$string,$matches, PREG_PATTERN_ORDER);
		for($j=0;$j<count($matches['1']);$j++){
			$array[$j]=$matches['1'][$j];
		}
		return $array;
	}
	
	/**
	* Returns all strings which are headed (<h1> ... </h1> etc) 
	* @param int $from_headnumber
	* @param int $till_headnumber
	* @return array $strings the strings which have been found pusched in an array
	* @desc Returns all strings which are headed (<h1> ... </h1> etc) 
	*/		
	function get_strings_headed($from_headnumber,$till_headnumber){
		$count_headers=$till_headnumber-$from_headnumber;
		$result_arr=array();
		
		for($i=$from_headnumber;$i<=$till_headnumber;$i++){
			$results=$this->get_strings_in_tag("<h$i>","</h$i>",$this->string);
	 		if($results!=""){
				$result_arr=array_merge($result_arr,$results);
			}
		}
		return $result_arr;
	}

	/**
	* Returns the content of the body
	* @return string $bodytext The content of the body
	* @desc Returns the content of the body
	*/	
	function get_body(){
		// Getting body parametres
		$pattern="|<body(.*)>|U";
		preg_match_all($pattern,$string,$matches, PREG_PATTERN_ORDER);		
				
		// Deleting body parameters
		$string=str_replace($matches['1'][0],"",$string);
		echo "<xmp>".$string."</xmp>";
		$pattern="|<body>(.*)</body>|U";
		
		// Getting text in body
		$matches="";
		preg_match_all($pattern,$string,$matches, PREG_SET_ORDER);		
		$string=$matches;

		for($i=0;$i<count($string);$i++){
			for($j=0;$j<count($string[$i]);$j++){
				echo "\{$string[$i][$j]}".$string[$i][$j]."<br>";	
			}
		}		
	}
	
	/**
	* Returns the content of the body without tags
	* @return string $bodytext the content of the body without tags
	* @desc Returns the content of the body without tags
	*/	
	function get_body_text(){
		$string=$this->string;	

		$string=strip_tags($string);
		$string=str_replace("\n","",$string);
		$string=str_replace("\r","",$string);
		$string=str_replace("\t","",$string);
		$string=str_replace("<!--","",$string);
		$string=str_replace("//-->","",$string);
		$string=str_replace("&nbsp;","",$string);
		
		return $string;
	}

	/**
	* Returns the frame urls
	* @return array $frame_urls the urls of the frame in an array
	* @desc Returns the frame urls
	*/			
	function get_frame_urls(){
	}
	
	function get_tag_param($param,$tag){
		preg_match_all("|$param\=\"(.*)\"|U",$tag,$matches, PREG_PATTERN_ORDER);
		if($matches['1'][0]==""){
			preg_match_all("|$param\=(.*)|U",$tag,$matches, PREG_PATTERN_ORDER);
		}
		if($matches['1'][0]==""){
			preg_match_all("|$param\=\'(.*)\'|U",$tag,$matches, PREG_PATTERN_ORDER);
		}
		return $matches['1'][0];
	}
}
?>