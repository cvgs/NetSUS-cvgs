<?php

session_start();

$noAuthURL="index.php";
if (!($_SESSION['isAuthUser']))
{
	echo "Not authorized - please log in";
}
else
{

	include "inc/config.php";
	include "inc/functions.php";
	
	if (isset($_POST['mirrorpkgs']) && $_GET['service'] = "SUS")
	{
		$conf->setSetting("mirrorpkgs", $_POST['mirrorpkgs']);
		if ($_POST['mirrorpkgs'] == "true")
		{
			suExec("setbaseurl ".$conf->getSetting("susbaseurl"));
		}
		else
		{
			suExec("setbaseurl ");
		}
	}
	
	if (isset($_POST['enablesyncsch']) && $_GET['service'] = "SUS")
	{
		$conf->setSetting("syncschedule", $_POST['enablesyncsch']);
		if ($_POST['enablesyncsch'] != "Off")
		{
			suExec("addsch \"".$_POST['enablesyncsch']."\"");
		}
		else
		{
			suExec("delsch");
		}
	}
	
	if (isset($_GET['getprodinfo']) && isset($_GET['id']))
	{
		$res = suExec("prodinfo ".$_GET['id']);
		
		if (strpos($res, "No product id") !== FALSE)
		{
			echo $res;
		}
		else
		{
			echo "Product ID: ".$_GET['id']."<br/>\n";
			$lines = explode("\n", $res);
			$desc = "";
			$captureDesc = false;
			foreach ($lines as $line)
			{
				if (strpos($line, "Title:") !== FALSE 
				 || strpos($line, "Version:") !== FALSE 
				 || strpos($line, "Size:") !== FALSE 
				 || strpos($line, "Post Date:") !== FALSE)
				{
					echo "$line<br/>\n";
				}
				else if (strpos($line, "<body>") !== FALSE)
				{
					$desc = "$line\n";
					$captureDesc = true;;
				}
				else if (strpos($line, "</body>") !== FALSE)
				{
					$desc .= "$line\n";
					$desc = str_replace("<body>", "", str_replace("</body>", "", $desc));
					echo "<br/>Description: $desc<br/>\n";
				}
				else if ($captureDesc)
				{
					$desc .= "$line\n";
				}
			}
		}
	}
	
	if (isset($_POST['NetBootImage']) && $_GET['service'] = "NetBoot")
	{
		$wasrunning = getNetBootStatus();
		$nbi = $_POST['NetBootImage'];
		if ($nbi != "")
		{
			$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
			$nbsubnets = "";
			foreach($conf->getSubnets() as $key => $value)
			{
				$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
			}
			$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
			suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
			if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
			{
				echo "<div class=\"errorMessage\">ERROR: Unable to update dhcpd.conf</div>";
			 
			}
			suExec("disablenetboot");
			suExec("installdhcpdconf");
		
			if ($wasrunning || isset($_POST['enablenetboot']))
			{
				suExec("setnbimages ".$nbi);
			}
			$conf->setSetting("netbootimage", $nbi);
		}
	}

}
?>