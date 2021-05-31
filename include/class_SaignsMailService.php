<?

class SaignsMailService
{
	var $hostname;
	var $port;
	var $password;
	var $globals;



	public function __construct( $hostname , $port , $password )
	{
		$this->hostname = $hostname;
		$this->port = $port;
		$this->password = $password;
	}



	public function set_globals( $as )
	{
		$this->globals = $as;
	}



	public function status()
	{
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, $this->hostname, $this->port);

		$buffer = "STATUS\n";
		$buffer .= $this->password."\n";
		socket_send($socket, $buffer, strlen($buffer), 0);
		$result;
		socket_recv($socket, $result, 4, MSG_WAITALL);
		socket_close($socket);
		return intval($result);
	}



	public function send( $headers = array() , $attachments = array() )
	{
		$headers = array_merge($this->globals, $headers);

		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		$result = socket_connect($socket, $this->hostname, $this->port);

		if ( !$result )
		{
			return FALSE;
		}

		$buffer = "MAIL\n";
		$buffer .= $this->password."\n";
		foreach ( $headers as $p => $v )
		{
			$buffer .= strlen($v)."\n";
			$buffer .= "header\n";
			$buffer .= $p."\n";
			$buffer .= $v;
		}
		if ( is_array($attachments) )
		{
			foreach ( $attachments as $p => $v )
			{
				if ( strpos($v, 'http://') === 0 )
				{
					$buffer .= strlen($v)."\n";
					$buffer .= "url\n";
					$buffer .= $p."\n";
					$buffer .= $v;
				}
				else if ( file_exists($v) )
				{
					$buffer .= filesize($v)."\n";
					$buffer .= "file\n";
					$buffer .= $p."\n";
					$buffer .= file_get_contents($v);
				}
				else
				{
					$buffer .= strlen($v)."\n";
					$buffer .= "file\n";
					$buffer .= $p."\n";
					$buffer .= $v;
				}
			}
		}
		$buffer .= "0\n";
		socket_send($socket, $buffer, strlen($buffer), 0);
		socket_close($socket);

		return TRUE;
	}

}

?>