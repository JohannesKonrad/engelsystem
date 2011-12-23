<?PHP
$title = "DB Update from XML";
$header = "DB Update from XML";
include ("../../includes/header.php");
include ("../../includes/funktion_xml.php");

///////////
// DEBUG //
///////////
$ShowDataStrukture = 0;
$EnableRoomFunctions = 1;
$EnableRooms = 1;
$EnableSchudleFunctions = 1;
$EnableSchudle = 1;
$EnableSchudleDB = 1;


/*##############################################################################################
				erstellt Arrays der Reume
  ##############################################################################################*/
function CreateRoomArrays()
{
	global $Room, $RoomID, $RoomName, $con;

	$sql =	"SELECT `RID`, `Name` FROM `Room` ".
		"WHERE `Show`='Y'".
		"ORDER BY `Number`, `Name`;";
	$Erg = mysql_query($sql, $con);
	$rowcount = mysql_num_rows($Erg);

	for ($i=0; $i<$rowcount; $i++)
	{
		$Room[$i]["RID"]  = mysql_result($Erg, $i, "RID");
		$Room[$i]["Name"] = mysql_result($Erg, $i, "Name");
		$RoomID[ mysql_result($Erg, $i, "RID") ] =  mysql_result($Erg, $i, "Name");
		$RoomName[ mysql_result($Erg, $i, "Name") ] = mysql_result($Erg, $i, "RID");
	}
}
CreateRoomArrays();

/*##############################################################################################
				           F I L E
  ##############################################################################################*/
echo "\n\n<br>\n<h1>XML File:</h1>\n";
if( isset($_POST["PentabarfUser"]) && isset($_POST["password"]) && isset($_POST["PentabarfURL"]))
{
	echo "Update XCAL-File from Pentabarf..";
	if($PentabarfGetWith=="fsockopen")
	{

	//backup error messeges and delate
	$Backuperror_messages = $error_messages;
		$fp = fsockopen( "ssl://$PentabarfXMLhost", 443, $errno, $errstr, 30);
//	$error_messages = $Backuperror_messages;
	
	if( !$fp)
	{
	   echo "<h2>fail: File 'https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"]. "' not readable!".
	   	"[$errstr ($errno)]</h2>";
	}
	else
	{
		if( ($fileOut = fopen( "$Tempdir/engelXML", "w")) != FALSE)
		{
			$head =	'GET /'. $PentabarfXMLpath. $_POST["PentabarfURL"]. ' HTTP/1.1'."\r\n".
				'Host: '. $PentabarfXMLhost. "\r\n".
				'User-Agent: Engelsystem'. "\r\n".
				'Authorization: Basic '.
				base64_encode($_POST["PentabarfUser"]. ':'. $_POST["password"])."\r\n".
				"\r\n";
			fputs( $fp, $head);
			$Zeilen = -1;
			echo "<pre>";
			while (!feof($fp))
			{	
				$Temp= fgets($fp,1024);
	
				// show header			
				if ($Zeilen == -1)
				{
					echo $Temp;
				}
				
				// ende des headers
				if( $Temp== "\r\n" )
				{
					echo "</pre>\n";
					$Zeilen = 0;
					$Temp="";
				}
				
				//file ende?
				if( $Temp=="0\r\n")
					break;

				if( ($Zeilen>-1) && ($Temp!="ffb\r\n") )
				{
					//steuerzeichen ausfiltern
					if( strpos( "#$Temp", "\r\n") > 0)
						$Temp = substr($Temp, 0, strlen($Temp)-2);
					if( strpos( "#$Temp", "1005") > 0)
						$Temp = "";
					if( strpos( "#$Temp", "783") > 0)
						$Temp = "";
					//schreiben in file
					fputs( $fileOut, $Temp);
					$Zeilen++;
				}
			}
			fclose( $fileOut);
			
			echo "<br>Es wurden $Zeilen Zeilen eingelesen<br>";
		}
		else
			echo "<h2>fail: File '$Tempdir/engelXML' not writeable!</h2>";
		fclose($fp);
	}
	}
	elseif($PentabarfGetWith=="fopen")
	{
		//user uns password in url einbauen
		$FileNameIn =	"https://". $_POST["PentabarfUser"]. ':'. $_POST["password"]. "@". 
				$PentabarfXMLhost. "/". $PentabarfXMLpath. $_POST["PentabarfURL"];


		if( ($fileIn = fopen( $FileNameIn, "r")) != FALSE)
		{
			if( ($fileOut = fopen( "$Tempdir/engelXML", "w")) != FALSE)
			{
				$Zeilen = 0;
				while (!feof($fileIn)) 
				{	
					$Zeilen++;
					fputs( $fileOut, fgets( $fileIn));	
				}
				fclose( $fileOut);
				echo "<br>Es wurden $Zeilen Zeilen eingelesen<br>";
			}
			else
				echo "<h2>fail: File '$Tempdir/engelXML' not writeable!</h2>";
			fclose( $fileIn);
		}
		else
			echo "<h2>fail: File 'https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"]. "' not readable!</h2>";
	}
	elseif( $PentabarfGetWith=="wget")
	{
		$Command = "wget --http-user=". $_POST["PentabarfUser"]. " --http-passwd=".$_POST["password"]. " ".
			"https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"].
			" --output-file=$Tempdir/engelXMLwgetLog --output-document=$Tempdir/engelXML".
			" --no-check-certificate";
		echo system( $Command, $Status);
		if( $Status==0)
			echo "OK.<br>";
		else
			echo "fail ($Status)($Command).<br>";
	}
	elseif( $PentabarfGetWith=="lynx")
	{
		$Command = "lynx -auth=". $_POST["PentabarfUser"]. ":".$_POST["password"]. " -dump ".
			"https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"]. " > $Tempdir/engelXML";
		echo system( $Command, $Status);
		if( $Status==0)
			echo "OK.<br>";
		else
			echo "fail ($Status)($Command).<br>";
	}
	elseif($PentabarfGetWith=="fopen")
	{
		//user uns password in url einbauen
		$FileNameIn =	"https://". $_POST["PentabarfUser"]. ':'. $_POST["password"]. "@". 
				$PentabarfXMLhost. "/". $PentabarfXMLpath. $_POST["PentabarfURL"];


		if( ($fileIn = fopen( $FileNameIn, "r")) != FALSE)
		{
			if( ($fileOut = fopen( "$Tempdir/engelXML", "w")) != FALSE)
			{
				$Zeilen = 0;
				while (!feof($fileIn)) 
				{	
					$Zeilen++;
					fputs( $fileOut, fgets( $fileIn));	
				}
				fclose( $fileOut);
				echo "<br>Es wurden $Zeilen Zeilen eingelesen<br>";
			}
			else
				echo "<h2>fail: File '$Tempdir/engelXML' not writeable!</h2>";
			fclose( $fileIn);
		}
		else
			echo "<h2>fail: File 'https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"]. "' not readable!</h2>";
	}
	elseif( $PentabarfGetWith=="wget")
	{
		$Command = "wget --http-user=". $_POST["PentabarfUser"]. " --http-passwd=".$_POST["password"]. " ".
			"https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"].
			" --output-file=$Tempdir/engelXMLwgetLog --output-document=$Tempdir/engelXML".
			" --no-check-certificate";
		echo system( $Command, $Status);
		if( $Status==0)
			echo "OK.<br>";
		else
			echo "fail ($Status)($Command).<br>";
	}
	elseif( $PentabarfGetWith=="lynx")
	{
		$Command = "lynx -auth=". $_POST["PentabarfUser"]. ":".$_POST["password"]. " -dump ".
			"https://$PentabarfXMLhost/$PentabarfXMLpath". $_POST["PentabarfURL"]. " > $Tempdir/engelXML";
		echo system( $Command, $Status);
		if( $Status==0)
			echo "OK.<br>";
		else
			echo "fail ($Status)($Command).<br>";
	}
	else
		echo "<h1>The PentabarfGetWith='$PentabarfGetWith' not supported</h1>";
}
else
{
	echo "<form action=\"dbUpdateFromXLS.php\" method=\"post\">\n";
	echo "<table border=\"0\">\n";
	echo "\t<tr><td>XCAL-File: https://$PentabarfXMLhost/$PentabarfXMLpath</td>".
		"<td><input name=\"PentabarfURL\" type=\"text\" size=\"4\" maxlength=\"5\" ".
		"value=\"$PentabarfXMLEventID\"></td></tr>\n";
	echo "\t<tr><td>Username:</td>".
		"<td><input name=\"PentabarfUser\" type=\"text\" size=\"30\" maxlength=\"30\"></td></tr>\n";
	echo "\t<tr><td>Password:</td>".
		"<td><input name=\"password\" type=\"password\" size=\"30\" maxlength=\"30\"></td></tr>\n";
	echo "\t<tr><td></td><td><input type=\"submit\" name=\"FileUpload\" value=\"upload\"></td></tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}



//readXMLfile("xml.php.xml");
if( readXMLfile("$Tempdir/engelXML") == 0)
{
$XMLmain = getXMLsubPease( $XMLmain, "VCALENDAR");


if( $ShowDataStrukture)
{
	echo "<pre><br>";
	echo $XMLmain->name;
	echo "<br>";
	print_r(array_values ($XMLmain->sub));
	echo "</pre>";
}

/*
echo "<br>";
$Feld=7;
echo "$Feld#". $XMLmain->sub[$Feld]->name. "<br>";
echo "$Feld#". $XMLmain->sub[$Feld]->sub;
//print_r(array_values ($XMLmain->sub[$Feld]->sub));
while(list($key, $value) = each($XMLmain->sub[$Feld]->sub))
	echo "?ID".$value->sub[1]->data. "=". $value->sub[2]->data. "\n";
echo "</pre>";
*/


/*##############################################################################################
				           V e r s i o n
  ##############################################################################################*/

echo "<hr>\n";
$XMLrelease = getXMLsubPease( $XMLmain, "X-WR-CALDESC");
echo "release: ". $XMLrelease->data. "<br>\n";
//$XMLreleaseDate = getXMLsubPease( $XMLmain, "RELEASE-DATE");
//echo "release date: ". $XMLreleaseDate->data. "<br>\n";
echo "<hr>\n";



/*##############################################################################################
				           V e r s i o n
  ##############################################################################################*/
if( $EnableRoomFunctions)
	include("../../includes/funktion_xml_room.php");

if( $EnableSchudleFunctions)
	include("../../includes/funktion_xml_schudle.php");


/*##############################################################################################
				         U P D A T E  A L L 
  ##############################################################################################*/
echo "\n\n<br>\n<h1>Update ALL:</h1>\n";

echo "<form action=\"dbUpdateFromXLS.php\">\n";
echo "\t<input type=\"submit\" name=\"UpdateALL\" value=\"now\">\n";
echo "</form>\n";

} //if XMLopenOOK

include ("../../includes/footer.php");
?>

