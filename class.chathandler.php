<?php
error_reporting(E_ALL);
date_default_timezone_set('America/New_York');
require_once("/var/www/html/MyScripts/dwij/simplejson.php");
$logs = "/var/www/html/MyScripts/dwij/logs.txt";

class ChatHandler {
	function send($message) {
		global $clientSocketArray;
		$messageLength = strlen($message);
		foreach($clientSocketArray as $clientSocket)
		{
			@socket_write($clientSocket,$message,$messageLength);
		}
		return true;
	}

	function unseal($socketData) {
		$length = ord($socketData[1]) & 127;
		if($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		$socketData = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i%4];
		}
		return $socketData;
	}

	function seal($socketData) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$socketData;
	}

	function doHandshake($received_header,$client_socket_resource, $host_name, $port) {
		$headers = array();
		$lines = preg_split("/\r\n/", $received_header);
		foreach($lines as $line)
		{
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$buffer  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $host_name\r\n" .
		"WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_socket_resource,$buffer,strlen($buffer));
	}
	
	function newConnectionACK($client_ip_address) {
		$message = 'New client ' . $client_ip_address.' joined';
		$messageArray = array('message'=>$message,'message_type'=>'chat-connection-ack');
		$ACK = $this->seal(toJSON($messageArray));
		return $ACK;
	}
	
	function connectionDisconnectACK($client_ip_address) {
		$message = 'Client ' . $client_ip_address.' disconnected';
		$messageArray = array('message'=>$message,'message_type'=>'chat-connection-ack');
		$ACK = $this->seal(toJSON($messageArray));
		return $ACK;
	}
	
	function createChatBoxMessage($data) {
		$messageObj = fromJSON($data);
		
		/*
		$db=mysql_connect ("192.168.4.13", "root", "mysql3101051118") or die ('Cannot connect to MySQL: ' . mysql_error());
		$q1 = "SELECT `msgWelcome` FROM `ramshyam_clients`.`login` WHERE `client_did_no`='".$messageObj->dnid."' AND `admin`='Y'";
		$r1 = mysql_query($q1);
		$w1 = mysql_fetch_row($r1);
		$welcomeGreeting = $w1[0];
		*/
		$dnidList = array();
		$dnidList['2062747877'] = "Domicile|Thank you for calling Domicile, My name is xxx, How may I assist you?";
		$dnidList['3125613282'] = "MIS|Thank you for calling MIS.<br>How may I help you today?";
		$dnidList['7472822072'] = "SoftEngineer|Thank you for calling SoftEngineer.<br>How may I help you today?";		
		$exploding = explode("|", $dnidList[$messageObj->dnid]);
		$clientName = $exploding[0];
		$welcomeGreeting = $exploding[1];
		
		$timeEST = $messageObj->dat . " " . $messageObj->tim;
		$timePST = date('d-M-Y, H:i:s',strtotime('-3 hour',strtotime($timeEST)));		

		$message = "
						<center>
							<span style=\"font-size: 42px;text-align: center;\">".$clientName."</span>
							<br>
							<span style=\"font-size: 42px;text-align: center;\">".$welcomeGreeting."</span>
							<br /><br />
							Queue No: " . $messageObj->qnum . "&nbsp&nbsp&nbsp&nbsp&nbsp
							Exten No: " . $messageObj->to . "
							<br>
							<hr>
							Caller Id: <div class='chat-box-message'>" . $messageObj->callerid . "</div>&nbsp&nbsp
							Dialled No: <div class='chat-box-message'>" . $messageObj->dnid . "</div>&nbsp
							<br />
							Date, Time: <div class='chat-box-message'>" . $timePST . " PST</div>
						</center>
				";
		file_put_contents($GLOBALS['logs'], "\n\n" . $message, FILE_APPEND);

		$messageArray = array('message'=>$message,'to'=>$messageObj->to);
		$chatMessage = $this->seal(toJSON($messageArray));
		return $chatMessage;
	}
}
?>
