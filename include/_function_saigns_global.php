<?



function ms()
{
	return str_replace(".", "", sprintf("%0.3f", microtime(true)));
}



function encrypt_decrypt( $action , $string , $secret )
{
	$output = false;
	// hash
	$key = hash('sha256', $secret);
	// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a
	// warning
	$iv = substr(hash('sha256', $secret), 0, 16);

	if ( $action == 'encrypt' )
	{
		$output = openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv);
		$output = base64_encode($output);
	}
	else if ( $action == 'decrypt' )
	{
		$output = openssl_decrypt(base64_decode($string), "AES-256-CBC", $key, 0, $iv);
	}
	return $output;
}



function encrypt( $data , $secret = "69&3F}fj5LYmnGVC" )
{
	return SaignsTools::encrypt_decrypt("encrypt", $data, $secret);
}



function decrypt( $data , $secret = "69&3F}fj5LYmnGVC" )
{
	return SaignsTools::encrypt_decrypt("decrypt", $data, $secret);
}



function startsWith( $haystack , $needle , $case = true )
{
	if ( $case )
	{
		return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
	}
	return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
}



function endsWith( $haystack , $needle , $case = true )
{
	if ( $case )
	{
		return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
	}
	return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
}



function mkdirs( $folder )
{
	if ( !is_dir($folder) )
	{
		mkdir($folder, 0777, true);
	}
}



function delete_folder( $dir )
{
	if ( is_dir($dir) )
	{
		$dh = opendir($dir);
		while ( $datei = readdir($dh) )
		{
			if ( $datei[0] == "." ) continue;
			delete_folder($dir."/".$datei);
		}
		closedir($dh);
		rmdir($dir);
	}
	else if ( file_exists($dir) )
	{
		unlink($dir);
	}
}



function shrink_string( $value , $max , $tilde = '...' )
{
	if ( strlen($value) > $max )
	{
		$value = substr($value, 0, $max);
		$value .= $tilde;
	}
	return $value;
}



function get_server_protocol()
{
	return 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
}



function get_base_href( $uri = false )
{
	return get_server_protocol().'://'.$_SERVER['SERVER_NAME'].($uri ? $_SERVER['REQUEST_URI'] : '');
}



function scale_to_width( $size , $max )
{
	return $size[0] < $max || $max == -1 ? $size : array(
		$max,
		($size[1] / $size[0]) * $max);
}



function scale_to_height( $size , $max )
{
	return $size[1] < $max || $max == -1 ? $size : array(
		($size[0] / $size[1]) * $max,
		$max);
}



function scale_to_size( $size , $max )
{
	$size = scale_to_width($size, $max[0]);
	$size = scale_to_height($size, $max[1]);
	return $size;
}



function valid_chars( $text , $chars )
{
	for ( $i = 0 ; $i < strlen($text) ; $i++ )
	{
		if ( strpos($chars, $text[$i]) === FALSE )
		{
			return FALSE;
		}
	}
	return TRUE;
}



function is_alphanum( $input )
{
	return valid_chars($input, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
}

if ( !function_exists("str_contains") )
{



	function str_contains( $haystack , $needle )
	{
		return strpos($haystack, $needle) !== FALSE;
	}
}



function stri_contains( $haystack , $needle )
{
	return stripos($haystack, $needle) !== FALSE;
}



function str_contains_db_quote( $haystack )
{
	if ( is_array($haystack) )
	{
		foreach ( $haystack as $s7 )
		{
			if ( strpos($s7, "'") !== FALSE )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	else
	{
		return strpos($haystack, "'") !== FALSE;
	}
}



function generate_session( $length = 8 )
{
	$chars = "aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789";
	$buffer = "";
	for ( $z = 0 ; $z < $length ; $z++ )
		$buffer .= substr($chars, rand(0, strlen($chars) - 1), 1);
	return $buffer;
}



function generate_salt( $max = 22 )
{
	$salt = "";
	$salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
	for ( $i = 0 ; $i < $max ; $i++ )
	{
		$salt .= $salt_chars[array_rand($salt_chars)];
	}
	return $salt;
}



function crypt_password( $password )
{
	return crypt($password, '$2y$07$'.generate_salt());
}



function polaroid( $width , $img , $top , $left , $rot , $z )
{
	ob_start();
	$height = $width * 1.195;
	$swidth = $width * 0.865;
	$padding = round(($width - $swidth) / 2);
	$sheight = $swidth * 1.028;
	$final_width = $width - ($padding * 2);
	$final_height = $height - ($padding * 2);
	?>
	<div style="position: absolute; margin-left: <?=$left?>px; margin-top: <?=$top?>px; z-index: <?=$z?>;">
		<div class="shadow" style="width: <?=$final_width?>px; height: <?=$final_height?>px; background-color: #f0f0f0; padding: <?=$padding?>px; border: 2px solid #678; transform:rotate(<?=$rot?>deg); -ms-transform:rotate(<?=$rot?>deg); -webkit-transform:rotate(<?=$rot?>deg);" class="cornerp">
			<div style="width: <?=$swidth?>px; height: <?=$sheight?>px; background: transparent; border: 2px solid #678;">
				<img src="<?=$img?>" style="width: <?=$swidth?>px; height: <?=$sheight?>px; user-select: none;">
			</div>
		</div>
	</div>
	<?
	return ob_get_clean();
}



function callstack( $break = "<br>" )
{
	$stack = '';
	$i = 1;
	$trace = debug_backtrace();
	unset($trace[0]); // Remove call to this function from stack trace
	unset($trace[1]);
	foreach ( $trace as $node )
	{
		$stack .= "#$i ".$node['file']."(".$node['line']."): ";
		if ( isset($node['class']) )
		{
			$stack .= $node['class']."->";
		}
		$stack .= $node['function']."()".$break;
		$i++;
	}
	return $stack;
}

?>