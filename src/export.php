<?php


/**
 *	Generate XLS file
 *********************************/

/* functions */
require_once( "/phpipam/functions/functions.php" );
require( "/phpipam/functions/PEAR/Spreadsheet/Excel/Writer.php");


# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
# $User->check_user_session();



// Create a workbook
$filename = "phpipam_IP_adress_export_". date("Y-m-d") .".xls";
$workbook = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8);

//fetch sections, and for each section write new tab, inside tab write all values!
$sections = $Sections->fetch_sections();

//we need to reformat state!
$ip_types = $Addresses->addresses_types_fetch();

//fetch devices and reorder
$devices = $Tools->fetch_all_objects("devices", "hostname");
$devices_indexed = array();
if ($devices!==false) {
    foreach($devices as $d) {
    	$devices_indexed[$d->id] = $d;
    }
}



//fetch nameservers and reorder
$nameservers = $Tools->fetch_all_objects("nameservers", "name");
$nameservers_indexed = array();
if ($nameservers!==false) {
    foreach($nameservers as $d) {
    	$nameservers_indexed[$d->id] = $d;
    }
}

//get all custom fields!
# fetch custom fields
$myFields = $Tools->fetch_custom_fields('ipaddresses');
$myFieldsSize = sizeof($myFields);

$colSize = 8 + $myFieldsSize;

//formatting headers
$format_header = $workbook->addFormat();
$format_header->setBold();
$format_header->setColor('white');
$format_header->setFgColor('black');

//formatting titles
$format_title = $workbook->addFormat();
$format_title->setColor('black');
$format_title->setFgColor(22);			//light gray
$format_title->setBottom(2);
$format_title->setLeft(1);
$format_title->setRight(1);
$format_title->setTop(1);
$format_title->setAlign('left');

//formatting content - borders around IP addresses
$format_right = $workbook->addFormat();
$format_right->setRight(1);
$format_left = $workbook->addFormat();
$format_left->setLeft(1);
$format_top = $workbook->addFormat();
$format_top->setTop(1);


foreach ($sections as $section) {
	//cast
	$section = (array) $section;

	//get all subnets in this section
	$subnets = $Subnets->fetch_section_subnets ($section['id']);

	$lineCount = 0;
	//Write titles
	foreach ($subnets as $subnet) {
		//cast
		$subnet = (array) $subnet;
		//ignore folders!
		if($subnet['isFolder']!="1") {


			//IP addresses in subnet
			$ipaddresses = $Addresses->fetch_subnet_addresses ($subnet['id']);

			if(!is_array($ipaddresses) || sizeof($ipaddresses) <= 0) {
				continue;
			}

			$worksheet_name = $Tools->shorten_text($Subnets->transform_to_dotted($subnet['subnet']) ."_".$subnet['description'], 30);
			$worksheet =& $workbook->addWorksheet($worksheet_name);
			$worksheet->setInputEncoding("utf-8");



			//vlan details
			$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
			if(strlen($vlan['number']) > 0) {
				$vlanText = " (vlan: " . $vlan['number'];
				if(strlen($vlan['name']) > 0) {
					$vlanText .= ' - '. $vlan['name'] . ')';
				}
				else {
					$vlanText .= ")";
				}
			}
			else {
				$vlanText = "";
			}

			// print_r($subnet);

			$deviceName = is_null($subnet['device'])||strlen($subnet['device'])==0||$subnet['device']==0 ? "" : $devices_indexed[$subnet['device']]->hostname;
			$nameServer = is_null($subnet['nameserverId'])||strlen($subnet['nameserverId'])==0||$subnet['nameserverId']==0 ? "" : $nameservers_indexed[$subnet['nameserverId']]->namesrv1;



			$worksheet->write(0,0,"INFOS",$format_header);
			$worksheet->write(1,0,"Description",$format_title);
			$worksheet->write(2,0,"NameServer",$format_title);
			$worksheet->write(3,0,"Device",$format_title);
			$worksheet->write(4,0,"Subnet",$format_title);
			$worksheet->write(5,0,"VLAN",$format_title);
			
			$worksheet->write(1,1,$subnet['description'],$format_left);
			$worksheet->write(2,1,$nameServer,$format_left);
			$worksheet->write(3,1,$deviceName,$format_left);
			$worksheet->write(4,1,$Subnets->transform_to_dotted($subnet['subnet']) . "/" .$subnet['mask'],$format_left);
			$worksheet->write(5,1,$vlanText,$format_left);

			$worksheet->mergeCells(0, 0, 0, 5);
			$worksheet->mergeCells(1, 1, 1, 5);
			$worksheet->mergeCells(2, 1, 2, 5);
			$worksheet->mergeCells(3, 1, 3, 5);
			$worksheet->mergeCells(4, 1, 4, 5);
			$worksheet->mergeCells(5, 1, 5, 5);

//			$worksheet->write(5, 0, $Subnets->transform_to_dotted($subnet['subnet']) . "/" .$subnet['mask'] . " - " . $subnet['description'] . $vlanText, $format_header );

			$lineCount = 6;

			$lineCount++;
			//write headers
			$worksheet->write($lineCount, 0, _('ip address'), $format_title);
			$worksheet->write($lineCount, 1, _('ip state'), $format_title);
			$worksheet->write($lineCount, 2, _('description'), $format_title);
			$worksheet->write($lineCount, 3, _('hostname'), $format_title);
			$worksheet->write($lineCount, 4, _('mac'), $format_title);
			$worksheet->write($lineCount, 5, _('owner'), $format_title);
			$worksheet->write($lineCount, 6, _('device'), $format_title);
			$worksheet->write($lineCount, 7, _('port'), $format_title);
			$worksheet->write($lineCount, 8, _('note'), $format_title);
			$m = 9;
			//custom
			if(sizeof($myFields) > 0) {
				foreach($myFields as $myField) {
					$worksheet->write($lineCount, $m, $myField['name'], $format_title);
					$m++;
				}
			}

			$lineCount++;

			if(is_array($ipaddresses) && sizeof($ipaddresses) > 0) {

				foreach ($ipaddresses as $ip) {
					//cast
					$ip = (array) $ip;

					//reformat state
					if(@$ip_types[$ip['state']]['showtag']==1) 	{ $ip['state'] = $ip_types[$ip['state']]['type']; }
					else										{ $ip['state'] = ""; }

					//change switch ID to name
					$ip['switch'] = is_null($ip['switch'])||strlen($ip['switch'])==0||$ip['switch']==0 ? "" : $devices_indexed[$ip['switch']]->hostname;

					$worksheet->write($lineCount, 0, $Subnets->transform_to_dotted($ip['ip_addr']), $format_left);
					$worksheet->write($lineCount, 1, $ip['state']);
					$worksheet->write($lineCount, 2, $ip['description']);
					$worksheet->write($lineCount, 3, $ip['hostname']);
					$worksheet->write($lineCount, 4, $ip['mac']);
					$worksheet->write($lineCount, 5, $ip['owner']);
					$worksheet->write($lineCount, 6, $ip['switch']);
					$worksheet->write($lineCount, 7, $ip['port']);
					$worksheet->write($lineCount, 8, $ip['note']);

					//custom
					$m = 9;
					if(sizeof($myFields) > 0) {
						foreach($myFields as $myField) {
							$worksheet->write($lineCount, $m, $ip[$myField['name']]);
							$m++;
						}
					}

					$lineCount++;
				}

			} else {
				$worksheet->write($lineCount, 0, _('No hosts'));
				$lineCount++;
			}

			//new line
			$lineCount++;
		}
	}
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
