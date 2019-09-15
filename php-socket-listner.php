<?php
error_reporting(E_ALL);
//CONNECTION TO THE WEB SOCKET SERVER TO SEND EVENTS TO THE BROWSER
$socketWS = fsockopen("127.0.0.1","8090", $errnox, $errstrx, 10);
if (!$socketWS){
     echo "$errstrx ($errnox)\n";
	 
}

$head = "GET / HTTP/1.1\r\nUpgrade: WebSocket\r\nConnection: Upgrade\r\nOrigin: localhost\r\nHost: localhost\r\nSec-WebSocket-Key: asdasdaas76da7sd6asd6as7d\r\n";
fputs($socketWS, $head);
$wretsWS = fgets($socketWS,4096);
//echo $wretsWS;





//CONNECTION TO THE AMI SERVER TO LISTEN TO EVENTS FROM ASTERISK
$socket = fsockopen("127.0.0.1","5038", $errno, $errstr, 10);
if (!$socket)
{
     echo "$errstr ($errno)\n";	 
}
else
{
	 fputs($socket, "action: login\r\n");
	 fputs($socket, "username: astm\r\n");
	 fputs($socket, "secret: astm\r\n\r\n");
	 fputs($socket, "action: Waitevent\r\n");
	 $wrets=fgets($socket,128);
	 //echo $wrets . "\n"; 
	 
	 while(true)
	 {
		  if($buffer = fgets($socket,4096))
		  {
			   if(strpos($buffer, "AgentCalled"))
			   {
					$found = true;
					$k = 1;
			   }
			   
			   if(isset($found))
			   {
					if($k==15)
					{
						 $found = false;
						 $jsonString2Send = '{"to":'.$exten.',"dnid":"'.$dnid.'","callerid":"'.$callerID.'","qnum":"'.$queueNo.'","tim":"'.$tm.'","dat":"'.$dt.'"}';
						 fputs($socketWS, $jsonString2Send);
					}
					
					if (preg_match("/^Queue/", $buffer))
					{
						 $LINE_queueNo 	= explode(":",$buffer);
						 $queueNo = str_replace("\n", "", trim($LINE_queueNo[1]));
						 //echo "\n\nqueue found: " . $eventKeyword . "\n\n" . "Value is:" . $queueNo . "\n\n";
					}
					if (preg_match("/^AgentName/", $buffer))
					{
						 $LINE_Exten 	= explode(":",$buffer);
						 $exten = str_replace("\n", "", trim($LINE_Exten[1]));
					}
					if (preg_match("/^CallerIDNum/", $buffer))
					{
						 $LINE_callerID 	= explode(":",$buffer);
						 $callerID = str_replace("\n", "", trim($LINE_callerID[1]));
					}
					if (preg_match("/^CallerIDName/", $buffer))
					{
						 $LINE_dnid 	= explode(":",$buffer);
						 $dnid = str_replace("\n", "", trim($LINE_dnid[1]));
					}
					$dt = date("d-m-Y");
					$tm = date("H:i:s");
					$k++;
			   }
			   
			   
		  }
	 }
}


	
	
	
fclose($socket);
fclose($socketWS);


