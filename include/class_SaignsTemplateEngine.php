<?php

class SaignsTemplateEngine
{
	var $sContent;
	var $iLength;
	var $iCursor;
	var $sPath;
	var $hVars = array();
	var $iBlockDepth = 0;
	var $sSwitchCondition;
	var $sTempfile;
	var $bWriteTempfile;
	static $EE = "');\n ";
	static $ES = " \necho('";



	function __construct( $path )
	{
		$this->sPath = $path;
		$this->iCursor = 0;
		$this->sContent = '';
		$this->iLength = 0;
		$this->bWriteTempfile = TRUE;
		$this->hVars = array();
	}



	private function str_starts_with( $str , $needle )
	{
		return substr($str, 0, strlen($needle)) === $needle;
	}



	private function str_ends_width( $str , $needle )
	{
		$length = strlen($needle);
		return !$length || substr($str, -$length) === $needle;
	}



	private function starts_with( $search )
	{
		$len = strlen($search);
		for ( $i = 0 ; $i < $len ; $i++ )
		{
			if ( $this->sContent[$this->iCursor + $i] != $search[$i] ) return false;
		}
		return true;
	}



	private function read_to( $search )
	{
		$buffer = '';
		while ( $this->iCursor < $this->iLength )
		{
			$buffer .= $this->sContent[$this->iCursor];
			if ( $this->str_ends_width($buffer, $search) )
			{
				break;
			}
			$this->iCursor++;
		}
		return substr($buffer, 0, strlen($buffer) - strlen($search));
	}



	function assign( $as )
	{
		$this->hVars = $as;
	}



	function add_assign( $key , $value = '' )
	{
		if ( is_array($key) )
		{
			$this->hVars = array_merge($this->hVars, $key);
		}
		else
		{
			$this->hVars[$key] = $value;
		}
	}



	function clear_assign()
	{
		$this->hVars = array();
	}



	function parse( $content )
	{
		$this->sContent = $content;
		$this->iCursor = 0;
		$this->iLength = strlen($this->sContent);
		$output = SaignsTemplateEngine::$ES;

		while ( $this->iCursor < $this->iLength )
		{
			if ( $this->sContent[$this->iCursor] == '\'' )
			{
				$output .= "\\'";
				$this->iCursor += 1;
				continue;
			}

			if ( $this->sContent[$this->iCursor] == '{' )
			{
				/* IF CONDITION */
				if ( $this->starts_with('{if') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 3;
					$if_condition = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$if_condition.' ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{elsif') )
				{
					$this->iCursor += 6;
					$if_condition = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' } elseif ( '.$if_condition.' ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{else}') )
				{
					$this->iCursor += 5;
					$output .= SaignsTemplateEngine::$EE.' } else { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/if}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 4;
					$output .= SaignsTemplateEngine::$EE.' } '.SaignsTemplateEngine::$ES;
				}

				/* VARIABLE */
				else if ( $this->starts_with('{$') )
				{
					$this->iCursor += 2;
					$varname = $this->read_to('}');
					$pointer = strpos($varname, '.');
					if ( $pointer !== FALSE )
					{
						$t1 = substr($varname, 0, $pointer);
						$t2 = substr($varname, $pointer + 1);

						$output .= SaignsTemplateEngine::$EE.' echo($'.$t1.'[\''.$t2."']); ".SaignsTemplateEngine::$ES;
					}
					else
					{
						$output .= SaignsTemplateEngine::$EE.' echo($'.$varname."); ".SaignsTemplateEngine::$ES;
					}
				}

				/* LANGUAGE */
				else if ( $this->starts_with('{#') && class_exists("SaignsLanguage") )
				{
					$this->iCursor += 2;
					$varname = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE." echo(SaignsLanguage::get('".$varname."')  ".SaignsTemplateEngine::$ES;
				}

				/* WHILE */
				else if ( $this->starts_with('{while') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 6;
					$loopcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' $rownum = 0; while ( '.$loopcontent.' ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/while}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 7;
					$output .= SaignsTemplateEngine::$EE.' $rownum++; } '.SaignsTemplateEngine::$ES;
				}

				/* FOREACH */
				else if ( $this->starts_with('{foreach') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 8;
					$loopcontent = $this->read_to('}');

					$output .= SaignsTemplateEngine::$EE.' $rownum = 0; foreach ( '.$loopcontent.' ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/foreach}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 9;
					$output .= SaignsTemplateEngine::$EE.' $rownum++; } '.SaignsTemplateEngine::$ES;
				}

				/* NOEACH */
				else if ( $this->starts_with('{noeach}') )
				{
					$this->iCursor += 8;
					$output .= SaignsTemplateEngine::$EE.' if ( $rownum == 0 ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/noeach}') )
				{
					$this->iCursor += 8;
					$output .= SaignsTemplateEngine::$EE.' } '.SaignsTemplateEngine::$ES;
				}

				/* FOR */
				else if ( $this->starts_with('{for') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 4;
					$loopcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' $rownum = 0; for ( '.$loopcontent.' ) { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/for}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 5;
					$output .= SaignsTemplateEngine::$EE.' $rownum++; } '.SaignsTemplateEngine::$ES;
				}

				/* DO WHILE */
				else if ( $this->starts_with('{dowhile') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 8;
					$loopcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' $rownum = 0; do { '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/dowhile}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 9;
					$output .= SaignsTemplateEngine::$EE.' $rownum++; } while ( '.$loopcontent.' ); '.SaignsTemplateEngine::$ES;
				}

				/* SWTICH CASE */
				else if ( $this->starts_with('{switch ') )
				{
					$this->iBlockDepth++;
					$this->iCursor += 8;
					$this->sSwitchCondition = $this->read_to('}');
				}
				else if ( $this->starts_with('{/switch}') )
				{
					$this->iBlockDepth--;
					$this->iCursor += 9;
				}
				else if ( $this->starts_with('{case ') )
				{
					$this->iCursor += 6;
					$switchcondition = $this->read_to('}');
					$this->iCursor += 1;
					$switchcontent = $this->read_to('{/case}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$this->sSwitchCondition.' == '.$switchcondition.' ) { echo(\''.$switchcontent.'\'); } '.SaignsTemplateEngine::$ES;
				}

				/* CONTINUE */
				else if ( $this->starts_with('{continue}') )
				{
					$this->iCursor += 9;
					$output .= SaignsTemplateEngine::$EE.' continue; '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{continue ') )
				{
					$this->iCursor += 9;
					$ifcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$ifcontent.' ) { continue; } '.SaignsTemplateEngine::$ES;
				}

				/* BREAK */
				else if ( $this->starts_with('{break}') )
				{
					$this->iCursor += 6;
					$output .= SaignsTemplateEngine::$EE.' break; '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{break ') )
				{
					$this->iCursor += 6;
					$ifcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$ifcontent.' ) { break; } '.SaignsTemplateEngine::$ES;
				}

				/* RETURN */
				else if ( $this->starts_with('{return}') )
				{
					if ( $this->iBlockDepth > 0 )
					{
						for ( $i = $this->iBlockDepth ; $i > 0 ; $i-- )
						{
							$output .= SaignsTemplateEngine::$EE.' } '.SaignsTemplateEngine::$ES;
						}
					}
					break;
				}

				/* COMMENT */
				else if ( $this->starts_with('{*}') )
				{
					$this->iCursor += 3;
					$output .= SaignsTemplateEngine::$EE.' /* '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{/*}') )
				{
					$this->iCursor += 3;
					$output .= SaignsTemplateEngine::$EE.' */ '.SaignsTemplateEngine::$ES;
				}

				/* PHP */
				else if ( $this->starts_with('{p}') )
				{
					$this->iCursor += 3;
					$output .= SaignsTemplateEngine::$EE.' '.$this->read_to('{/p}').' '.SaignsTemplateEngine::$ES;
				}

				/* SIMPLE ECHO */
				else if ( $this->starts_with('{=') )
				{
					$this->iCursor += 2;
					$output .= SaignsTemplateEngine::$EE.' echo('.$this->read_to('}').');'.SaignsTemplateEngine::$ES;
				}

				/* PRINT HTTP REQUEST */
				else if ( $this->starts_with('{g ') && function_exists("g") )
				{
					$this->iCursor += 3;
					$temp = $this->read_to('}');
					$temp = str_replace("'","\\'",$temp);
					$output .= g($temp);
				}

				/* SELECTED & CHECKBOX */
				else if ( $this->starts_with('{selected ') )
				{
					$this->iCursor += 9;
					$ifcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$ifcontent.' ) { echo(" selected "); } '.SaignsTemplateEngine::$ES;
				}
				else if ( $this->starts_with('{checked ') )
				{
					$this->iCursor += 8;
					$ifcontent = $this->read_to('}');
					$output .= SaignsTemplateEngine::$EE.' if ( '.$ifcontent.' ) { echo(" checked "); } '.SaignsTemplateEngine::$ES;
				}

				/* DEFAULT OUTPUT */
				else
				{
					$output .= $this->sContent[$this->iCursor];
				}
			}
			else
			{
				$output .= $this->sContent[$this->iCursor];
			}
			$this->iCursor++;
		}
		$output .= "');";

		$this->sContent = $output;
	}



	public function execute()
	{
		$vars = '';

		// Zeilenweise die internen Variablen erzeugen und zuweisen
		foreach ( $this->hVars as $param => $value )
		{
			if ( strlen($param) > 0 ) $vars .= '$'.$param.' = $this->hVars[\''.$param.'\'];'."\r\n";
		}

		ob_start();
		try
		{
			if ( $this->bWriteTempfile && $this->sTempfile != "" )
			{
				if ( !is_dir($this->sPath."/_temp") ) mkdir($this->sPath."/_temp", 0777, TRUE);
				file_put_contents($this->sPath."/_temp/".$this->sTempfile, "<?php\r\n".$vars."\r\n\r\n".$this->sContent."\r\n?>");
			}
			if ( class_exists("SaignsTiming") ) SaignsTiming::start("TemplateEngine eval ".$this->sTempfile);
			eval($vars.$this->sContent);
			if ( class_exists("SaignsTiming") ) SaignsTiming::end("TemplateEngine eval ".$this->sTempfile);
		}
		catch ( ParseError $e )
		{
			echo ("<hr>".highlight_string($e->getMessage()."\r\n\r\n".$this->sContent, true)."<hr>");
			exit();
		}
		$content = ob_get_clean();

		return $content;
	}



	public function fetch( $file )
	{
		$filepath = $this->sPath.'/'.$file;
		$content = "";
		$this->sTempfile = str_replace("/", "_", $filepath);

		if ( file_exists($filepath.'.html') && is_readable($filepath.'.html') )
		{
			$this->sTempfile .= '_html.tpl';
			$content = file_get_contents($filepath.'.html');
		}
		else if ( file_exists($filepath.'.css') && is_readable($filepath.'.css') )
		{
			$this->sTempfile .= '_css.tpl';
			$content = file_get_contents($filepath.'.css');
		}
		else if ( file_exists($filepath.'.js') && is_readable($filepath.'.js') )
		{
			$this->sTempfile .= '_js.tpl';
			$content = file_get_contents($filepath.'.js');
		}
		else
		{
			$this->sTempfile = '';
			$content = 'template '.$file.' ('.$filepath.') missing';
		}

		if ( class_exists("SaignsTiming") ) SaignsTiming::start("TemplateEngine parse ".$this->sTempfile);
		$this->parse($content);
		if ( class_exists("SaignsTiming") ) SaignsTiming::end("TemplateEngine parse ".$this->sTempfile);

		return $this->execute();
	}



	/**
	 * Benutzt den ??bergebenen String als Template und f??hrt diesen aus
	 * @param unknown $content
	 * @return string
	 */
	public function fetch_content( $content )
	{
		$this->sTempfile = md5($content).'.tpl';
		$this->parse($content);

		return $this->execute();
	}

}



function f( $file , $params = array() )
{
	global $templates;
	if ( count($params) > 0 )
	{
		$templates->add_assign($params);
	}
	return $templates->fetch($file);
}



function fc( $content , $params = array() )
{
	global $templates;
	if ( count($params) > 0 )
	{
		$templates->add_assign($params);
	}

	return $templates->fetch_content($content);
}



function js( $file )
{
	return '<script type="text/javascript">'.f('js/'.$file).'</script>';
}



function css( $file )
{
	return '<style type="text/css">'.f('css/'.$file).'</style>';
}



function css_import_all( $dir = 'templates/css' )
{
	$buffer = "";
	foreach ( glob($dir.'/*.{css}', GLOB_BRACE) as $file )
	{
		$file = str_replace(array(
			$dir."/",
			".css"), "", $file);

		$content = f('css/'.$file);
		$content = str_replace("\r", '', $content);
		$content = str_replace("\t", '', $content);
		$content = str_replace("\n", '', $content);

		$buffer .= $content;
	}
	return '<style type="text/css">'.$buffer.'</style>';
}

$templates = new SaignsTemplateEngine('templates');

?>