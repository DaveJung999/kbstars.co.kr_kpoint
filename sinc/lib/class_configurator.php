<?php
/* 04/03/09 By Sunmin Park
http://www.phpclasses.org/browse/package/1489.html
ex)
$test = Configurator::open('/usr/local/apache2/conf/httpd.conf');
$test->parse();
print_r($test->getDirectives());
*/
// error codes used by Configurator_error class
define('CONFIG_ERROR_NOFILE', 10);
define('CONFIG_ERROR_INVALID', 11);
define('CONFIG_ERROR_NOLOAD', 12);
define('CONFIG_ERROR_SYNTAX', 13);

class Configurator {
	function open($filename){
		if (!file_exists($filename)){
			return self::error(CONFIG_ERROR_NOFILE);
		} else {
			return new ConfiguratorDoc($filename);
		}
	}

	function create($filename){
		return new ConfiguratorDoc($filename, true);
	}

	// returns error object. Should be called statically
	function error($errorcode){
		return new ConfiguratorError($errorcode);
	}

	// returns true if object is error object
	function isError($object){
		if (!is_object($object)){
			return false;
		}
		if (get_class($object) == "ConfiguratorError"){
			return true;
		}
		return false;
	}

}

class ConfiguratorDoc {
	var $filename;
	var $new;
	var $directives = array();
	var $currenttag = array();

	function __construct($filename, $new = false){
		$this->filename = $filename;
		$this->new = $new;
	}

	// Config files use Apache-style directives
	// Example: UserName joe
	// Comments start with # and extend to rest of line
	function parse(){
		if ($this->new == true){
			return Configurator::error(CONFIG_ERROR_NOFILE);
		}
		$contents = @file($this->filename);
		if (!$contents){
			return Configurator::error(CONFIG_ERROR_NOLOAD);
		}
		foreach ($contents as $line){
			// Gets rid of comments
			$line = preg_replace('/#.*$/', '', $line);
			$line = ltrim($line);
			$line = rtrim($line);
			if ($line == ''){
				continue;
			} elseif (preg_match('/<([a-zA-Z]+)(.*)>/', $line, $tagopen)){
				$this->currenttag['info']['tagname'] = $tagopen['1'];
				$this->currenttag['info']['attributes'] = $tagopen['2'];
			} elseif (preg_match('/<\/[a-zA-Z]+>/', $line, $tagclose)){
				if ($this->taghandler != ''){
					 $this->taghandler($this->currenttag);
				}
				$this->directives[$this->currenttag['info']['tagname'] . $this->currenttag['info']['attributes']] = $this->currenttag['directives'];
				$this->currenttag = array();
			} else {
				$directive = explode(' ', $line);
				$directiveName = $directive['0'];
				unset ($directive['0']);
				$directiveValue = implode(' ', $directive);
				if ($this->currenttag != array()){
					$this->currenttag['directives'][$directiveName] = $directiveValue;
				} else {
					$this->directives[$directiveName] = $directiveValue;
				}
			}
		}
		if ($this->currenttag != array()){
			Configurator::error(CONFIG_ERROR_SYNTAX);
		}
	}

	function getDirectives(){
		return $this->directives;
	}

	function getDirective($directive){
		return $this->directives[$directive];
	}

	function getDirectiveFromGroup($group, $directive){
		return $this->directives[$group][$directive];
	}

	function setDirectives($directives){
		if (!is_array($directives)){
			return Configurator::error(CONFIG_ERROR_INVALID);
		} else {
			$this->directives = array_merge($this->directives, $directives);
		}
	}

	function setDirective($name, $value){
		$this->directives[$name] = $value;
	}

	function setDirectiveInGroup($group, $directive, $value){
		$this->directives[$group][$directive] = $value;
	}

	function parseToArray(){
		$this->parse();
		return $this->getDirectives();
	}

	function remove($directive){
		unset($this->directives[$directive]);
	}

	function removeFromGroup($group, $directive){
		unset($this->directives[$group][$directive]);
	}

	// writes current set of directives to file
	// NOTE: does not save comments
	function write(){
		$fp = fopen($this->filename, 'w');
		$filecontents = <<<CONTENTS
# <?php die('Don\'t be naughty'); ?>
# Configuration file
# Auto-generated file
CONTENTS;
		foreach ($this->directives as $name =>  $value){
			if (is_array($value)){
				$filecontents .= "\n\n<{$name}>";
				foreach ($value as $childname =>  $childvalue){
					$filecontents .= "\n\t{$childname} {$childvalue}";
				}
				$namearr = explode(' ', $name);
				$filecontents .= "\n</{$namearr['0']}>";
			} else {
				$filecontents .= "\n\n{$name} {$value}";
			}
		}
		fwrite($fp, $filecontents);
		fclose($fp);
		$this->new = false;
	}

	function writeFromArray($directives){
		$this->setDirectives($directives);
		$this->write();
	}

}

class ConfiguratorError {
	var $code;

	function __construct($code){
		$this->code = $code;
		switch ($code){
			case CONFIG_ERROR_NOFILE:
				die('Configurator Fatal Error: config file could not be found.');
				break;
			case CONFIG_ERROR_NOLOAD:
				die('Configurator Fatal Error: config file could not be loaded. Please check your file permissions.');
				break;
			case CONFIG_ERROR_INVALID:
				break;
			case CONFIG_ERROR_SYNTAX:
				die('Configurator Fatal Error: syntax error in config file. Perhaps an unclosed tag?');
				break;
		}
	}

	function message(){
		switch ($this->code){
			case CONFIG_ERROR_NOFILE:
				return 'Configurator Fatal Error: config file could not be found.';
				break;
			case CONFIG_ERROR_NOLOAD:
				return 'Configurator Fatal Error: config file could not be loaded. Please check your file permissions.';
				break;
			case CONFIG_ERROR_INVALID:
				return 'Configurator Warning: invalid argument. Operation not performed.';
				break;
			case CONFIG_ERROR_SYNTAX:
				return 'Configurator Fatal Error: syntax error in config file. Perhaps an unclosed tag?';
				break;
		}
	}
}

?>
