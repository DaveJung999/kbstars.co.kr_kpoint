<?php

/*
	CVS Revision. 1.1.1 ( Edit 2002. 1. 25 By Sunmin Park)
	2002. 1. 25 : 공식배포사이트에서 패치부분 그대로 수정함
*/

class FastTemplate {
	public $FILELIST	=	[];	//	Holds the array of filehandles
									//	FILELIST[HANDLE] == "fileName"

	public $DYNAMIC	=	[];	//	Holds the array of dynamic
									//	blocks, and the fileHandles they
									//	live in.

	public $PARSEVARS	=	[];	//	Holds the array of Variable
									//	handles.
									//	PARSEVARS[HANDLE] == "value"

	public $LOADED		=	[];	//	We only want to load a template
									//	once - when it's used.
									//	LOADED[FILEHANDLE] == 1 if loaded
									//	undefined if not loaded yet.

	public $HANDLE		=	[];	//	Holds the handle names assigned
									//	by a call to parse()

	public $ROOT		=	"";			//	Holds path-to-templates

	public $WIN32		=	false;		//	Set to true if this is a WIN32 server

	public $ERROR		=	"";			//	Holds the last error message

	public $LAST		=	"";			//	Holds the HANDLE to the last
									//	template parsed by parse()

	public $STRICT		=	true;		//	Strict template checking.
									//	Unresolved vars in templates will
									//	generate a warning when found.

//	************************************************************

	public function __construct ($pathToTemplates = "")
	{
		if(isset($pathToTemplates))
		{
			$this->set_root($pathToTemplates);
		}

	}	// end (new) FastTemplate ()


//	************************************************************
//	All templates will be loaded from this "root" directory
//	Can be changed in mid-process by re-calling with a new
//	value.

	public function set_root ($root)
	{
		$trailer = substr($root,-1);

		if(!$this->WIN32)
		{
			if( (ord($trailer)) != 47 )
			{
				$root = "{$root}". chr(47);
			}

			if(is_dir($root))
			{
				$this->ROOT = $root;
			}
			else
			{
				$this->ROOT = "";
				$this->error("Specified ROOT dir [{$root}] is not a directory", 1);
			}
		}
		else
		{
			// WIN32 box - no testing
			if( (ord($trailer)) != 92 )
			{
				$root = "{$root}" . chr(92);
			}
			$this->ROOT = $root;
		}

	}	// End set_root()


// **************************************************************
// Calculates current microtime
//	I throw this into all my classes for benchmarking purposes
//	It's not used by anything in this class and can be removed
//	if you don't need it.


	public function utime ()
	{
		return microtime(true);
	}

// **************************************************************
//	Strict template checking, if true sends warnings to STDOUT when
//	parsing a template with undefined variable references
//	Used for tracking down bugs-n-such. Use no_strict() to disable.

	public function strict ()
	{
		$this->STRICT = true;
	}

//	************************************************************
//	Silently discards (removes) undefined variable references
//	found in templates

	public function no_strict ()
	{
		$this->STRICT = false;
	}

//	************************************************************
//	A quick check of the template file before reading it.
//	This is -not- a reliable check, mostly due to inconsistencies
//	in the way PHP determines if a file is readable.

	public function is_safe ($filename)
	{
		if(!file_exists($filename))
		{
			$this->error("[{$filename}] does not exist",0);
			return false;
		}
		return true;
	}

//	************************************************************
//	Grabs a template from the root dir and
//	reads it into a (potentially REALLY) big string

	public function get_template ($template)
	{
		if(empty($this->ROOT))
		{
			$this->error("Cannot open template. Root not valid.",1);
			return false;
		}

		$filename	=	"{$this}->ROOT"."{$template}";

		$contents = @implode("",(@file($filename)));
		if( (!$contents) or (empty($contents)) )
		{
			$this->error("get_template() failure: [{$template}]",1);
		}

		return $contents;

	} // end get_template

//	************************************************************
//	Prints the warnings for unresolved variable references
//	in template files. Used if STRICT is true

	public function show_unknowns ($Line)
	{
		$unknown = [];
		if (preg_match("/({[A-Z0-9_]+})/",$Line,$unknown))
		{
			$UnkVar = $unknown['1'];
			if(!(empty($UnkVar)))
			{
				@error_log("[FastTemplate] Warning: no value found for variable: {$UnkVar} ",0);
			}
		}
	}	// end show_unknowns()

//	************************************************************
//	This routine get's called by parse() and does the actual
//	{VAR} to VALUE conversion within the template.

	public function parse_template ($template, $tpl_array)
	{
		foreach ($tpl_array as $key =>  $val)
		{
			if (!(empty($key)))
			{
				if(gettype($val) != "string")
				{
					settype($val,"string");
				}

				$key_escaped = preg_quote($key, '/');
				$template = preg_replace('/{'."{$key_escaped}".'}/', "{$val}", $template);
			}
		}

		if(!$this->STRICT)
		{
			// Silently remove anything not already found

			$template = preg_replace("/{[A-Z0-9_]+}/","",$template);
		}
		else
		{
			// Warn about unresolved template variables
			if (preg_match("/({[A-Z0-9_]+})/", $template))
			{
				$unknown = explode("\n",$template);
				foreach ($unknown as $Element =>  $Line)
				{
					$UnkVar = $Line;
					if(!(empty($UnkVar)))
					{
						$this->show_unknowns($UnkVar);
					}
				}
			}
		}
		return $template;

	}	// end parse_template();

//	************************************************************
//	The meat of the whole class. The magic happens here.

	public function parse ( $ReturnVar, $FileTags )
	{
		$append = false;
		$this->LAST = $ReturnVar;
		$this->HANDLE[$ReturnVar] = 1;

		if (gettype($FileTags) == "array")
		{
			unset($this->$ReturnVar);	// Clear any previous data

			foreach ( $FileTags as $key =>  $val )
			{
				if ( (!isset($this->$val)) || (empty($this->$val)) )
				{
					$this->LOADED["{$val}"] = 1;
					if(isset($this->DYNAMIC["{$val}"]))
					{
						$this->parse_dynamic($val,$ReturnVar);
					}
					else
					{
						$fileName = $this->FILELIST["{$val}"];
						$this->$val = $this->get_template($fileName);
					}
				}

				//	Array context implies overwrite

				$this->$ReturnVar = $this->parse_template($this->$val,$this->PARSEVARS);

				//	For recursive calls.

				$this->assign([ $ReturnVar =>  $this->$ReturnVar ]);

			}
		}	// end if FileTags is array()
		else
		{
			// FileTags is not an array

			$val = $FileTags;

			if( (substr($val,0,1)) == '.' )
			{
				// Append this template to a previous ReturnVar

				$append = true;
				$val = substr($val,1);
			}

			if ( (!isset($this->$val)) || (empty($this->$val)) )
			{
					$this->LOADED["{$val}"] = 1;
					if(isset($this->DYNAMIC["{$val}"]))
					{
						$this->parse_dynamic($val,$ReturnVar);
					}
					else
					{
						$fileName = $this->FILELIST["{$val}"];
						$this->$val = $this->get_template($fileName);
					}
			}

			if($append)
			{
				if(!isset($this->$ReturnVar)) { $this->$ReturnVar = ""; }
				$this->$ReturnVar .= $this->parse_template($this->$val,$this->PARSEVARS);
			}
			else
			{
				$this->$ReturnVar = $this->parse_template($this->$val,$this->PARSEVARS);
			}

			//	For recursive calls.

			$this->assign([ $ReturnVar =>  $this->$ReturnVar ]);

		}
		return;
	}	//	End parse()


//	************************************************************

	public function FastPrint ( $template = "" )
	{
		if(empty($template))
		{
			$template = $this->LAST;
		}

		if( (!(isset($this->$template))) || (empty($this->$template)) )
		{
			$this->error("Nothing parsed, nothing printed",0);
			return;
		}
		else
		{
			print $this->$template;
		}
		return;
	}

//	************************************************************

	public function fetch ( $template = "" )
	{
		if(empty($template))
		{
			$template = $this->LAST;
		}
		if( (!(isset($this->$template))) || (empty($this->$template)) )
		{
			$this->error("Nothing parsed, nothing printed",0);
			return "";
		}

		return($this->$template);
	}


//	************************************************************

	public function define_dynamic ($Macro, $ParentName)
	{
		//	A dynamic block lives inside another template file.
		//	It will be stripped from the template when parsed
		//	and replaced with the {$Tag}.

		$this->DYNAMIC["{$Macro}"] = $ParentName;
		return true;
	}

//	************************************************************

	public function parse_dynamic ($Macro,$MacroName)
	{
		// The file must already be in memory.

		$ParentTag = $this->DYNAMIC["{$Macro}"];
		if( (!$this->$ParentTag) or (empty($this->$ParentTag)) )
		{
			$fileName = $this->FILELIST[$ParentTag];
			$this->$ParentTag = $this->get_template($fileName);
			$this->LOADED[$ParentTag] = 1;
		}
		if($this->$ParentTag)
		{
			$template = $this->$ParentTag;
			$DataArray = explode("\n",$template);
			$newMacro = "";
			$newParent = "";
			$outside = true;
			$start = false;
			$end = false;
			foreach ($DataArray as $lineNum =>  $lineData)
			{
				$lineTest = trim($lineData);
				if("<!-- BEGIN DYNAMIC BLOCK: {$Macro} -->" == "{$lineTest}" )
				{
					$start = true;
					$end = false;
					$outside = false;
				}
				if("<!-- END DYNAMIC BLOCK: {$Macro} -->" == "{$lineTest}" )
				{
					$start = false;
					$end = true;
					$outside = true;
				}
				if( (!$outside) and (!$start) and (!$end) )
				{
					$newMacro .= "{$lineData}\n"; // Restore linebreaks
				}
				if( ($outside) and (!$start) and (!$end) )
				{
					$newParent .= "{$lineData}\n"; // Restore linebreaks
				}
				if($end)
				{
					$newParent .= '{' . "{$MacroName}}\n";
				}
				// Next line please
				if($end) { $end = false; }
				if($start) { $start = false; }
			}	// end While

			$this->$Macro = $newMacro;
			$this->$ParentTag = $newParent;
			return true;

		}	// $ParentTag NOT loaded - MAJOR oopsie
		else
		{
			@error_log("ParentTag: [{$ParentTag}] not loaded!",0);
			$this->error("ParentTag: [{$ParentTag}] not loaded!",0);
		}
		return false;
	}

//	************************************************************
//	Strips a DYNAMIC BLOCK from a template.

	public function clear_dynamic ($Macro="")
	{
		if(empty($Macro)) { return false; }

		// The file must already be in memory.

		$ParentTag = $this->DYNAMIC["{$Macro}"];

		if( (!$this->$ParentTag) or (empty($this->$ParentTag)) )
		{
			$fileName = $this->FILELIST[$ParentTag];
			$this->$ParentTag = $this->get_template($fileName);
			$this->LOADED[$ParentTag] = 1;
		}

		if($this->$ParentTag)
		{
			$template = $this->$ParentTag;
			$DataArray = explode("\n",$template);
			$newParent = "";
			$outside = true;
			$start = false;
			$end = false;
			foreach ($DataArray as $lineNum =>  $lineData)
			{
				$lineTest = trim($lineData);
				if("<!-- BEGIN DYNAMIC BLOCK: {$Macro} -->" == "{$lineTest}" )
				{
					$start = true;
					$end = false;
					$outside = false;
				}
				if("<!-- END DYNAMIC BLOCK: {$Macro} -->" == "{$lineTest}" )
				{
					$start = false;
					$end = true;
					$outside = true;
				}
				if( ($outside) and (!$start) and (!$end) )
				{
					$newParent .= "{$lineData}\n"; // Restore linebreaks
				}
				// Next line please
				if($end) { $end = false; }
				if($start) { $start = false; }
			}	// end While

			$this->$ParentTag = $newParent;
			return true;

		}	// $ParentTag NOT loaded - MAJOR oopsie
		else
		{
			@error_log("ParentTag: [{$ParentTag}] not loaded!",0);
			$this->error("ParentTag: [{$ParentTag}] not loaded!",0);
		}
		return false;
	}


//	************************************************************

	public function define ($fileList)
	{
		foreach ($fileList as $FileTag =>  $FileName)
		{
			$this->FILELIST["{$FileTag}"] = $FileName;
		}
		return true;
	}

//	************************************************************

	public function clear_parse ( $ReturnVar = "")
	{
		$this->clear($ReturnVar);
	}

//	************************************************************

	public function clear ( $ReturnVar = "" )
	{
		// Clears out hash created by call to parse()

		if(isset($ReturnVar))
		{
			if( (gettype($ReturnVar)) != "array")
			{
				unset($this->$ReturnVar);
				return;
			}
			else
			{
				foreach ($ReturnVar as $key =>  $val)
				{
					unset($this->$val);
				}
				return;
			}
		}

		// Empty - clear all of them

		foreach ($this->HANDLE as $key =>  $val)
		{
			$KEY = $key;
			unset($this->$KEY);
		}
		return;

	}	//	end clear()

//	************************************************************

	public function clear_all ()
	{
		$this->clear();
		$this->clear_assign();
		$this->clear_define();
		$this->clear_tpl();

		return;

	}	//	end clear_all

//	************************************************************

	public function clear_tpl ($fileHandle = "")
	{
		if(empty($this->LOADED))
		{
			// Nothing loaded, nothing to clear

			return true;
		}
		if(empty($fileHandle))
		{
			// Clear ALL fileHandles

			foreach ($this->LOADED as $key =>  $val)
			{
				unset($this->$key);
			}
			unset($this->LOADED);

			return true;
		}
		else
		{
			if( (gettype($fileHandle)) != "array")
			{
				if( (isset($this->$fileHandle)) || (isset($this->$fileHandle)) )
				{
					unset($this->LOADED[$fileHandle]);
					unset($this->$fileHandle);
					return true;
				}
			}
			else
			{
				foreach ($fileHandle as $Key =>  $Val)
				{
					unset($this->LOADED[$Key]);
					unset($this->$Key);
				}
				return true;
			}
		}

		return false;

	}	// end clear_tpl

//	************************************************************

	public function clear_define ( $FileTag = "" )
	{
		if(empty($FileTag))
		{
			unset($this->FILELIST);
			return;
		}

		if( (gettype($Files)) != "array")
		{
			unset($this->FILELIST[$FileTag]);
			return;
		}
		else
		{
			foreach ($FileTag as $Tag =>  $Val)
			{
				unset($this->FILELIST[$Tag]);
			}
			return;
		}
	}

//	************************************************************
//	Aliased function - used for compatibility with CGI::FastTemplate
	public function clear_assign ()
	{
		if(!(empty($this->PARSEVARS)))
		{
			foreach($this->PARSEVARS as $Ref =>  $Val)
			{
				unset($this->PARSEVARS["{$Ref}"]);
			}
		}
	}

//	************************************************************
//	Clears all variables set by assign()

	public function clear_assign_all ()
	{
		$this->clear_assign();
	}

//	************************************************************

	public function clear_href ($href)
	{
		if(isset($href))
		{
			if( (gettype($href)) != "array")
			{
				unset($this->PARSEVARS[$href]);
				return;
			}
			else
			{
				foreach ($href as $Ref =>  $val)
				{
					unset($this->PARSEVARS[$Ref]);
				}
				return;
			}
		}
		else
		{
			// Empty - clear them all

			$this->clear_assign();
		}
		return;
	}

//	************************************************************

	public function assign ($tpl_array, $trailer="")
	{
		if(gettype($tpl_array) == "array")
		{
			foreach ($tpl_array as $key =>  $val)
			{
				if (!(empty($key)))
				{
					//	Empty values are allowed
					//	Empty Keys are NOT

					$this->PARSEVARS["{$key}"] = $val;
				}
			}
		}
		else
		{
			// Empty values are allowed in non-array context now.
			if (isset($tpl_array))
			{
				$this->PARSEVARS["{$tpl_array}"] = $trailer;
			}
		}
	}

//	************************************************************
//	Return the value of an assigned variable.
//	Christian Brandel cbrandel@gmx.de

	public function get_assigned($tpl_name = "")
	{
		if(empty($tpl_name)) { return false; }
		if(isset($this->PARSEVARS["{$tpl_name}"]))
		{
			return ($this->PARSEVARS["{$tpl_name}"]);
		}
		else
		{
			return false;
	}
	}

//	************************************************************

	public function error ($errorMsg, $die = 0)
	{
		$this->ERROR = $errorMsg;
		echo "ERROR: {$this}->ERROR <BR> \n";
		echo "본 에러 메시지가 계속될 경우 사이트 질문란에 문의주시면 감사하겠습니다.<br><br>";
		if ($die == 1)
		{
			exit;
		}

		return;

	} // end error()


//	************************************************************



//	************************************************************

} // End class.FastTemplate.php3

?>
