<?php
/************************************************
 *THIS IS A CALL POP-UP SCRIPT CREATED BY DWIJ
 *TO SHOW A POPUP WITH CALLER INFORMATION WHEN
 *A NEW CALL IS RECEIVED.
 *THIS PAGE IS A SOCKET CLIENT WHICH CONNECTS
 *TO WEBSOCKET ON 192.168.4.9 PORT 8090
 *ANY INCOMING MESSAGE FROM THE WEBSOCKET IS
 *DISPLAYED.
 *THIS SCRIPT DOES NOTHING ELSE
 *THE MAIN MAGIC HAPPENS IN WEB SOCKET SERVER
 *SCRIPT WHICH IS "PHP-SOCKET.PHP" WHICH IS
 *STARTED ON BOOT VIA CRONTAB.
 *DATA IS GATHERED & POSTED HERE VIA FUNCTIONS
 *IN CLASS.CHATHANDLER.PHP
 ***********************************************/

// LAN ACCESS ONLY
if(substr($_SERVER['REMOTE_ADDR'], 0, 10) != "192.168.4.")	exit;
?>


<?php
include ('/Backups/scripts/class.phpmailer.php');


function restartServer() {
	$pidLISTNER = shell_exec("ps aux | grep [p]hp-socket-listner.php | grep 'dwij' | awk '{print $2}'");
	if(strlen(trim($pidLISTNER))>0)
		system("sudo kill " . $pidLISTNER);
		
	$pidSERVER = shell_exec("ps aux | grep [p]hp-socket-server.php | grep 'dwij' | awk '{print $2}'");
	if(strlen(trim($pidSERVER))>0)	
		system("sudo kill " . $pidSERVER);
		
	system("setsid php /var/www/html/MyScripts/dwij/php-socket-server.php >/var/log/callpopup.txt 2>&1 < /var/log/callpopup.txt &");
	system("setsid php /var/www/html/MyScripts/dwij/php-socket-listner.php >/var/log/callpopup.txt 2>&1 < /var/log/callpopup.txt &");

	echo '<center>Done.</center>';
}





if (isset($_GET['restart'])) {
  restartServer();
}


if( isset($_POST['Login']) && ($_POST['Login'] == "Login") )
{

	echo "
		<html>
		<head>
			<title>Call Pop-Up</title>
			<style>
			body{font-family:calibri;}
			.error {color:#FF0000;}
			#websocket-box {
				border: 1px solid #ecc5cc;
				padding: 5px 10px;
			}
			.chat-connection-ack{
				color: #26af26;
			}
			.chat-message {
				border-bottom-left-radius: 4px;
				border-bottom-right-radius: 4px;
			}
			#btnSend {
				background: #26af26;
				border: #26af26 1px solid;
				border-radius: 4px;
				color: #FFF;
				display: block;
				margin: 15px 0px;
				padding: 10px 50px;
				cursor: pointer;
			}
			#chat-box {
				background: #fff8f8;
				border: 1px solid #ffdddd;
				border-radius: 4px;
				border-bottom-left-radius:0px;
				border-bottom-right-radius: 0px;
				min-height: 300px;
				padding: 10px;
				overflow: auto;
			}
			.chat-box-html{
				color: #09F;
				margin: 10px 0px;
				font-size:2.5em;
			}
			.chat-box-message{
				color: #09F;
				padding: 5px 10px;
				background-color: #fff;
				border: 1px solid #ffdddd;
				border-radius:4px;
				display:inline-block;
			}
			.chat-input{
				border: 1px solid #ffdddd;
				border-top: 0px;
				width: 100%;
				box-sizing: border-box;
				padding: 10px 8px;
				color: #191919
			}
			.clear-all-button {
				background-color:#fe1a00;
				-webkit-border-top-left-radius:30px;
				-moz-border-radius-topleft:30px;
				border-top-left-radius:30px;
				-webkit-border-top-right-radius:30px;
				-moz-border-radius-topright:30px;
				border-top-right-radius:30px;
				-webkit-border-bottom-right-radius:30px;
				-moz-border-radius-bottomright:30px;
				border-bottom-right-radius:30px;
				-webkit-border-bottom-left-radius:30px;
				-moz-border-radius-bottomleft:30px;
				border-bottom-left-radius:30px;
				text-indent:0;
				border:1px solid #d83526;
				display:inline-block;
				color:#ffffff;
				font-family:Verdana;
				font-size:15px;
				line-height:50px;
				width:150px;
				text-decoration:none;
				text-align:center;
				margin: 20px;
			}
			.restart-button {
				background-color:#e69802;
				-webkit-border-top-left-radius:30px;
				-moz-border-radius-topleft:30px;
				border-top-left-radius:30px;
				-webkit-border-top-right-radius:30px;
				-moz-border-radius-topright:30px;
				border-top-right-radius:30px;
				-webkit-border-bottom-right-radius:30px;
				-moz-border-radius-bottomright:30px;
				border-bottom-right-radius:30px;
				-webkit-border-bottom-left-radius:30px;
				-moz-border-radius-bottomleft:30px;
				border-bottom-left-radius:30px;
				text-indent:0;
				border:1px solid #e69802;
				display:inline-block;
				color:#ffffff;
				font-family:Verdana;
				font-size:15px;
				line-height:50px;
				width:150px;
				text-decoration:none;
				text-align:center;
				margin: 20px;
			}
			.logout-button {
				background-color:#444444;
				-webkit-border-top-left-radius:30px;
				-moz-border-radius-topleft:30px;
				border-top-left-radius:30px;
				-webkit-border-top-right-radius:30px;
				-moz-border-radius-topright:30px;
				border-top-right-radius:30px;
				-webkit-border-bottom-right-radius:30px;
				-moz-border-radius-bottomright:30px;
				border-bottom-right-radius:30px;
				-webkit-border-bottom-left-radius:30px;
				-moz-border-radius-bottomleft:30px;
				border-bottom-left-radius:30px;
				text-indent:0;
				border:1px solid #444444;
				display:inline-block;
				color:#ffffff;
				font-family:Verdana;
				font-size:15px;
				line-height:50px;
				width:150px;
				text-decoration:none;
				text-align:center;
				margin: 20px;
			}
			</style>	
			<script src='jquery-1.9.1.js'></script>
			<script>
			var tl = '';
			var interv = '';
			var blur = false;
			window.onfocus = function(){
			if(blur){
				stopblink();
				blur = false;
				
			}
			};
			window.onblur = function(){
			   blur = true;
			};
			function stopblink() {
			
			clearInterval(interv);
			document.title = \"Call Pop-Up\";
			tl = true;
			
			}
			function blink() {
				if(blur){
				if(tl){
					tl = false;
					document.title = \"New Call\";
				}else{
					tl = true;
					document.title = \"Notification\";
				}}else{stopblink();}
			}
			function beep() {
				var snd = new Audio(\"bicycle_bell.wav\");  
				snd.play();
			}
			
			function showMessage(messageHTML) {
				$('#chat-box').append(messageHTML);
			}
				
			$(document).ready(function(){
				var websocket = new WebSocket('ws://192.168.4.9:8090');
				
				websocket.onopen = function(event) {
					$('#websocket-box').append(\"<div class='chat-connection-ack'>Connection is established!</div>\");		
				}
				websocket.onmessage = function(event) {
					
					
					var Data = JSON.parse(event.data);
					if(Data.to == ".$_POST['ext']."){
						document.getElementById('chat-box').innerHTML = '';
						showMessage(\"<div class='chat-box-html'>\"+Data.message+\"</div>\");
						tl = true;
						beep();
						interv = setInterval(blink,1000);
					}   
				};
				
				websocket.onerror = function(event){
					$('#websocket-box').append(\"<div class='error'>Problem due to some Error</div>\");
					beep();
				};
				
				websocket.onclose = function(event){
					$('#websocket-box').append(\"<div class='chat-connection-ack'>Connection Closed</div>\");
					beep();
				}; 
				
				$('#frmChat').on(\"submit\",function(event){
					event.preventDefault();
					$('#chat-user').attr(\"type\",\"hidden\");		
					var messageJSON = {
						chat_user: $('#chat-user').val(),
						chat_message: $('#chat-message').val()
					};
					websocket.send(JSON.stringify(messageJSON));
				});
			});
		
			function clearAll() {
				document.getElementById('chat-box').innerHTML = '';
			}
		
		
			</script>
			<body>
			<center><h1>Ext: ".$_POST['ext']."</h1></center>
				<div id='websocket-box'></div>
				<div id='chat-box'></div>
				<div>
					<div style='width:50%;float: left;'>
						<button class='clear-all-button' onclick='clearAll()'>Clear Call</button>
						<button class='restart-button' onclick=\"location.href='index.php?restart=true';\" >Restart App</button>
					</div>
					<div style='width:50%;float: left; text-align:right;'>
						<button class='logout-button' onclick=\"location.href='index.php';\" >Logout</button>
					</div>
				</div>
			</body>
		</html>		
	";
}
else
{
	echo '
		<html>
		<head>
		<title>Call Pop-Up</title>
		</head>
		<body>
		<form name="danger-login" method="POST" action="'.$_SERVER['PHP_SELF'].'">
		<div align="center">
		<center>
		<br><br>
			<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse" bordercolor="#3333FF" width="60%" id="AutoNumber1">
				<tr height="50">
					<td width="100%" align="center" bgcolor="#3333FF">
						<b><font color="#FFFFFF" size="10">Call Pop-Up</font></b>
					</td>
				</tr>

				<tr>
					<td width="100%" align="center">
						<font size="5"><br>Enter Your Extension: <br></font>
						<br>
						<input type="ext" name="ext" style="font-size:14pt; padding:5px;" autofocus="autofocus">
						&nbsp;&nbsp;&nbsp;
						<input type="submit" value="Login" name="Login" style="color:#FFF; font-size:14pt; font-family:verdana; padding:5px 10px; background-color:#3333FF;">
						<br><br><br>
					</td>
				</tr>
			</table>
		</center>
		</div>
		</form>
		</body>
		</html>';	
}

?>