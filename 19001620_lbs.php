###[DEF]###
[name				= Gardena Smart Sileno V0.20			]

[e#1	important	= Autostart			#init=1				]
[e#2	important	= username								]
[e#3	important	= password								]
[e#4				= mower_number		#init=1				]
[e#5				= park1				#init=0				]
[e#6				= park2				#init=0				]
[e#7				= start				#init=0				]
[e#8				= start_24h			#init=0				]
[e#9				= start_3d			#init=0				]
[e#10				= Loglevel 			#init=8				]
[e#11				= CycleTime 		#init=20			]



[a#1				= mower_name							]
[a#2				= state_text							]
[a#3				= state_orig							]
[a#4				= battery_level							]
[a#5				= next_start							]
[a#6				= signal								]
[a#7				= last_connect							]
[a#8				= error_text							]
[a#9				= error_orig							]
[a#10				= charge_cycles							]
[a#11				= collisions							]
[a#12				= cut_time								]
[a#13				= run_time								]
[a#15				= cutting								]
[a#16				= charging								]
[a#17				= parking								]
[a#18				= state_num								]

[v#1 = 0]
[v#5 = 0]
[v#10 = 0]
[v#11 = 0]
[v#12 = 0]
[v#13 = 0]
[v#14 = 0]


[v#100				= 0.20 ]
[v#101 				= 19001620 ]
[v#102 				= Gardena Smart Sileno ]
[v#103 				= 0 ]

###[/DEF]###

###[HELP]###
This LBS connects to the Gardena cloud and can communicate with your Gardena Smart Sileno (R100LiC) auto mower.
It is possible to get above information and send commands.
Sending commands is only trigged on a rising edge 0->1 of the respective Input.
The LBS will update the information from gardena every E11 seconds. When a command is is triggered, the LBS will be updated in 0-500ms after the command.
After the command was issued, the cycletime will be set to 1 second for 20 times.

Inputs:
E1 - Autostart:		Don't change or connect!
E2 - username:		Your gardena cloud username (e.g. E-Mail address)
E3 - password:		Your gardena cloud password
E4 - mower_number:	In case you have more than one gardena mower connected to your account, select the correct one here (default: 1)
E5 - park1:			Park the mower until the next timer triggers a start
E6 - park2:			Park the mower until further notice
E7 - start:			Start the mower (resume schedule)
E8 - start_24h:		Start the mower for 24 hours (regardless of schedule)
E9 - start_3d:		Start the mower for 3 days (regardless of schedule)
E10- LogLevel:		Set the PHP-LogLevel (default: 8)
E11- CycleTime:		Poll the information from gardena cloud every n seconds (default: 20)

Outputs:
A1 - mower_name:	The name of the mower as configured in the gardena app
A2 - state_text:	The current state of the mower as human readable text
A3 - state_orig:	The current state of the mower as keyword
A4 - battery_level:	The battery level in percent (0-100)
A5 - next_start:	Next scheduled start of the mower - 0 when no start is planned (be aware time is in UTC. See also LBS19000153)
A6 - signal:		Signal strengt for the radio connection between mower and smart gateway
A7 - last_connect:	Time of last connection between gardena cloud and mower (be aware time is in UTC. See also LBS19000153)
A8 - error_text:	The current error state of the mower as human readable text
A9 - error_orig:	The current error state of the mower as keyword
A10- charge_cycles:	The total number of charging cycles
A11- collisions:	The total number of collisions
A12- cut_time:		cutting time (hours)
A13- run_time:		running time (hours)
A15-cutting:		mower is cutting (bool)
A16-charging:		mower is charging (bool)
A17-parking:		mower is parking (bool)
A18-state_num:		summarized state as integer (for logging/operation history visualization). Will only be set by change. Every change results in 2 telegrams with first the old value, then the new value (optimzed for visualization)


Versions:
V0.20	2018-06-12	SirSydom		set cycletime to 1s 20x after a cmd was issued, changed behaviour of A18
V0.19	2018-06-05	SirSydom		changed edge detection in LB_LBSID to prevent error messages, supressed error messaged in gardena class
V0.18	2018-05-30	SirSydom		Inputs in LBS not EXEC
V0.17	2018-05-28	SirSydom		minor changes to logging
V0.16	2018-05-28	SirSydom		added error handling to reduce error logs, added A15-18 for extended state information
V0.15	2018-05-25	SirSydom		added translation for status and error messages
V0.14	2018-05-25	SirSydom

Open Issues:
Timezone-Handling

Author:
SirSydom - com@sirsydom.de
Copyright (c) 2018 SirSydom

Github:
https://github.com/SirSydom/edomi_LBS_gardena_smart_system Label 19001620_V0_20

Links:
https://knx-user-forum.de/forum/projektforen/edomi/1233746-lbs-f%C3%BCr-gardena-smart-sileno-smart-system
http://www.roboter-forum.com/showthread.php?16777-Gardena-Smart-System-Analyse
http://www.dxsdata.com/de/2016/07/php-class-for-gardena-smart-system-api/
https://github.com/DXSdata/Gardena


Contributions:
Gardena php class and sample code based on https://github.com/DXSdata/Gardena commit 0659b71 
Copyright (c) 2017 DXSdata


###[/HELP]###


###[LBS]###
<?
function LB_LBSID($id)
{
	if($E=getLogicEingangDataAll($id))
	{
		setLogicElementVar($id, 103, $E[10]['value']); //set loglevel to #VAR 103
		if(getLogicElementVar($id,1)!=1)
		{
			setLogicElementVar($id,1,1);                      //setzt V1=1, um einen mehrfachen Start des EXEC-Scripts zu verhindern
			callLogicFunctionExec(LBSID,$id);                 //EXEC-Script starten (garantiert nur einmalig)
		}
		
		if($E[5]['value'] == 1 && $E[5]['refresh'] == 1 && getLogicElementVar($id,10) != 1)	// only act on rising edge
		{
			setLogicElementVar($id, 5, 1);
		}
		setLogicElementVar($id, 10, $E[5]['value']);
		
		if($E[6]['value'] == 1 && $E[6]['refresh'] == 1 && getLogicElementVar($id,11) != 1)	// only act on rising edge
		{
			setLogicElementVar($id, 5, 2);
		}		
		setLogicElementVar($id, 11, $E[6]['value']);
		
		if($E[7]['value'] == 1 && $E[7]['refresh'] == 1 && getLogicElementVar($id,12) != 1)	// only act on rising edge
		{
			setLogicElementVar($id, 5, 3);
		}		
		setLogicElementVar($id, 12, $E[7]['value']);
		
		if($E[8]['value'] == 1 && $E[8]['refresh'] == 1 && getLogicElementVar($id,13) != 1)	// only act on rising edge
		{
			setLogicElementVar($id, 5, 4);
		}		
		setLogicElementVar($id, 13, $E[8]['value']);
		
		if($E[9]['value'] == 1 && $E[9]['refresh'] == 1 && getLogicElementVar($id,14) != 1)	// only act on rising edge
		{
			setLogicElementVar($id, 5, 5);
		}		
		setLogicElementVar($id, 14, $E[9]['value']);


		
	}
}



?>
###[/LBS]###


###[EXEC]###
<?
require(dirname(__FILE__)."/../../../../main/include/php/incl_lbsexec.php");
set_time_limit(0);                                       //Wichtig! Script soll endlos laufen
sql_connect();
logging($id, "Gardena Smart System Daemon started", null, 5);
$E = logic_getInputs($id);
$cyclecounter = $E[11]['value'] * 2; // start with max value, so a cycle is immidiatelly triggerd
$reduce_cycletime = 0;
$A18 = null;
while (getSysInfo(1)>=1)
{
	$E = logic_getInputs($id);
	$cmd = getLogicElementVar($id,5);	
	setLogicElementVar($id, 5, 0);
	
	if(($cyclecounter < ($E[11]['value'])*2) && $cmd == 0)
	{
		usleep(500000);
		$cyclecounter++;
	}
	else
	{
		if($reduce_cycletime > 0)
		{
			$reduce_cycletime--;
			$cyclecounter = $E[11]['value'] * 2 - 2;
		}
		else
		{
			$cyclecounter = 0;
		}
		
		logging($id, "Gardena Smart System Cycle started", null, 8);

		if ($E)
		{
			$username = $E[2]['value'];
			$password = $E[3]['value'];
			$mower_num = $E[4]['value'];
			
			error_off();
			$gardena = new gardena($username, $password);
			error_on();
			logging($id, "new gardena", null, 8);
			if($gardena == NULL)
			{
				logging($id, "$gardena is NULL", null, 1);
			}
			else
			{
				error_off();
				$mower = $gardena -> getDeviceOfCategory($gardena::CATEGORY_MOWER, $mower_num);
				error_on();
				logging($id, "getDeviceOfCategory", null, 8);
			}
			
			if($mower == NULL)
			{
				logging($id, "mower is NULL", null, 1);
				$cyclecounter = $E[11]['value'] * 2;
			}
			else
			{
				switch($cmd)
				{
					case 1:
						$gardena -> sendCommand($mower, $gardena -> CMD_MOWER_PARK_UNTIL_NEXT_TIMER);
						logging($id, "Send command CMD_MOWER_PARK_UNTIL_NEXT_TIMER", null, 5);
						break;
					case 2:
						$gardena -> sendCommand($mower, $gardena -> CMD_MOWER_PARK_UNTIL_FURTHER_NOTICE);
						logging($id, "Send command CMD_MOWER_PARK_UNTIL_FURTHER_NOTICE", null, 5);
						break;
					case 3:
						$gardena -> sendCommand($mower, $gardena -> CMD_MOWER_START_RESUME_SCHEDULE);
						logging($id, "Send command CMD_MOWER_START_RESUME_SCHEDULE", null, 5);
						break;
					case 4:
						$gardena -> sendCommand($mower, $gardena -> CMD_MOWER_START_24HOURS);
						logging($id, "Send command CMD_MOWER_START_24HOURS", null, 5);
						break;
					case 5:
						$gardena -> sendCommand($mower, $gardena -> CMD_MOWER_START_3DAYS);
						logging($id, "Send command CMD_MOWER_START_3DAYS", null, 5);
						break;
				}
				
				if($cmd > 0)
				{
					$reduce_cycletime = 20; //reduce cyclimetime to 1s for 20times
					$cyclecounter = $E[11]['value'] * 2 - 2; //come back in 1s
				}
				

				$mowername = $gardena -> getMowerName($mower);	
				logic_setOutput($id,1,$mowername);
				
				$mowerstate = $gardena -> getMowerState($mower);
				logic_setOutput($id,2,$gardena->status_map[$mowerstate]);
				logic_setOutput($id,3,$mowerstate);
				logic_setOutput($id,15,$gardena->status_map_int[$mowerstate] == 10 ? 1 : 0);
				logic_setOutput($id,16,$gardena->status_map_int[$mowerstate] == 5 ? 1 : 0);
				logic_setOutput($id,17,$gardena->status_map_int[$mowerstate] == 2 ? 1 : 0);
				
				if($A18 != $gardena->status_map_int[$mowerstate])
				{
					if($A18 != null)
						logic_setOutput($id,18,$A18);
					
					logic_setOutput($id,18,$gardena->status_map_int[$mowerstate]);
					$A18 = $gardena->status_map_int[$mowerstate];
				}
				
				
				$batterylevel = $gardena -> getPropertyData($mower, "battery", "level") -> value;
				logic_setOutput($id,4,$batterylevel);
				
				$next_start = $gardena -> getPropertyData($mower, "mower", "timestamp_next_start") -> value;
				logic_setOutput($id,5,$next_start); //ToDo: Timezone
				
				$signal = $gardena -> getPropertyData($mower, "radio", "quality") -> value;
				logic_setOutput($id,6,$signal);
				
				$last_time_online = $gardena -> getPropertyData($mower, "device_info", "last_time_online") -> value;
				logic_setOutput($id,7,$last_time_online); //ToDo: Timezone
				
				$error = $gardena -> getPropertyData($mower, "mower", "error") -> value;
				logic_setOutput($id,8,$gardena->error_map[$error]);
				logic_setOutput($id,9,$error);
				
				$charging_cycles = $gardena -> getPropertyData($mower, "mower_stats", "charging_cycles") -> value;
				logic_setOutput($id,10,$charging_cycles);
				
				$collisions = $gardena -> getPropertyData($mower, "mower_stats", "collisions") -> value;
				logic_setOutput($id,11,$collisions);
				
				$cutting_time = $gardena -> getPropertyData($mower, "mower_stats", "cutting_time") -> value;
				logic_setOutput($id,12,$cutting_time);
				
				$running_time = $gardena -> getPropertyData($mower, "mower_stats", "running_time") -> value;
				logic_setOutput($id,13,$running_time);
					
					
					
					
			}
				
				
				
			/*
			echo "battery level: #". $gardena -> getPropertyData($mower, "battery", "level") -> value ."#\n<br>\n<br>";
			echo "mower timestamp_next_start: #". $gardena -> getPropertyData($mower, "mower", "timestamp_next_start") -> value ."#\n<br>\n<br>";
			echo "radio quality: #". $gardena -> getPropertyData($mower, "radio", "quality") -> value ."#\n<br>\n<br>";
			echo "device_info last_time_online: #". $gardena -> getPropertyData($mower, "device_info", "last_time_online") -> value ."#\n<br>\n<br>";
			echo "mower error: #". $gardena -> getPropertyData($mower, "mower", "error") -> value ."#\n<br>\n<br>";
			echo "mower_stats charging_cycles: #". $gardena -> getPropertyData($mower, "mower_stats", "charging_cycles") -> value ."#\n<br>\n<br>";
			echo "mower_stats collisions: #". $gardena -> getPropertyData($mower, "mower_stats", "collisions") -> value ."#\n<br>\n<br>";
			echo "mower_stats cutting_time: #". $gardena -> getPropertyData($mower, "mower_stats", "cutting_time") -> value ."#\n<br>\n<br>";
			echo "mower_stats running_time: #". $gardena -> getPropertyData($mower, "mower_stats", "running_time") -> value ."#\n<br>\n<br>";
			*/	
			
			logging($id, "Gardena Smart System Cycle exit", null, 8);
		}
	}
}
sql_disconnect();
logging($id, "Gardena Smart System Daemon exit", null, 5);

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	global $id;
	logging($id, "File: $errfile | Error: $errno | Line: $errline | $errstr ");
}

function error_off()
{
	$error_handler = set_error_handler("myErrorHandler");
	error_reporting(0);
}

function error_on()
{
	restore_error_handler();
	error_reporting(E_ALL);
}

function logging($id,$msg, $var=NULL, $priority=8)
{
	$E=getLogicEingangDataAll($id);
	$logLevel = getLogicElementVar($id,103);
	if (is_int($priority) && $priority<=$logLevel && $priority>0)
	{
		$logLevelNames = array('none','emerg','alert','crit','err','warning','notice','info','debug');
		$version = getLogicElementVar($id,100);
		$lbsNo = getLogicElementVar($id,101);
		$logName = getLogicElementVar($id,102) . ' --- LBS'.$lbsNo;
		strpos($_SERVER['SCRIPT_NAME'],$lbsNo) ? $scriptname='EXE'.$lbsNo : $scriptname = 'LBS'.$lbsNo;
		writeToCustomLog($logName,str_pad($logLevelNames[$logLevel],7), $scriptname." [v$version]:\t".$msg);
		
		if (is_object($var)) $var = get_object_vars($var); // transfer object into array
		if (is_array($var)) // print out array
		{
			writeToCustomLog($logName,str_pad($logLevelNames[$logLevel],7), $scriptname." [v$version]:\t================ ARRAY/OBJECT START ================");
			foreach ($var as $index => $line)
				writeToCustomLog($logName,str_pad($logLevelNames[$logLevel],7), $scriptname." [v$version]:\t".$index." => ".$line);
			writeToCustomLog($logName,str_pad($logLevelNames[$logLevel],7), $scriptname." [v$version]:\t================ ARRAY/OBJECT END ================");
		}
	}
}

class gardena
{
    var $user_id, $token, $locations;
    var $devices = array();
    
    const LOGINURL = "https://smart.gardena.com/sg-1/sessions";
    const LOCATIONSURL = "https://smart.gardena.com/sg-1/locations/?user_id=";
    const DEVICESURL = "https://smart.gardena.com/sg-1/devices?locationId=";
    const CMDURL = "https://smart.gardena.com/sg-1/devices/|DEVICEID|/abilities/mower/command?locationId=";
        
    var $CMD_MOWER_PARK_UNTIL_NEXT_TIMER = array("name" => "park_until_next_timer");
    var $CMD_MOWER_PARK_UNTIL_FURTHER_NOTICE = array("name" => "park_until_further_notice");
    var $CMD_MOWER_START_RESUME_SCHEDULE = array("name" => "start_resume_schedule");
    var $CMD_MOWER_START_24HOURS = array("name" => "start_override_timer", "parameters" => array("duration" => 1440));
    var $CMD_MOWER_START_3DAYS = array("name" => "start_override_timer", "parameters" => array("duration" => 4320));
    
    var $CMD_SENSOR_REFRESH_TEMPERATURE = array("name" => "measure_ambient_temperature");
    var $CMD_SENSOR_REFRESH_LIGHT = array("name" => "measure_light");
    var $CMD_SENSOR_REFRESH_HUMIDITY = array("name" => "measure_humidity");    
    
    var $CMD_WATERINGCOMPUTER_START_30MIN = array("name" => "manual_override", "parameters" => array("duration" => 30));
    var $CMD_WATERINGCOMPUTER_STOP = array("name" => "cancel_override");
    
        
    const CATEGORY_MOWER = "mower";
    const CATEGORY_GATEWAY = "gateway";
    const CATEGORY_SENSOR = "sensor";
    const CATEGORY_WATERINGCOMPUTER = "watering_computer";
    
    const PROPERTY_STATUS = "status";
    const PROPERTY_BATTERYLEVEL = "level";
    const PROPERTY_TEMPERATURE = "temperature";
    const PROPERTY_SOIL_HUMIDITY = "humidity";
    const PROPERTY_LIGHTLEVEL = "light";
    const PROPERTY_VALVE_OPEN = "valve_open";
    
    const ABILITY_CONNECTIONSTATE = "radio";
    const ABILITY_BATTERY = "battery";
    const ABILITY_TEMPERATURE = "ambient_temperature";
    const ABILITY_SOIL_HUMIDITY = "humidity";
    const ABILITY_LIGHT = "light";
    const ABILITY_OUTLET = "outlet";
	
	var $status_map = array(
		"paused" => "Pausiert",
		"ok_cutting" => "Mähen",
		"ok_searching" => "Suche Ladestation",
		"ok_charging" => "Lädt",
		"ok_leaving" => "Mähen",
		"wait_updating" => "Wird aktualisiert ...",
		"wait_power_up" => "Wird eingeschaltet ...",
		"parked_timer" => "Geparkt nach Zeitplan",
		"parked_park_selected" => "Geparkt",
		"off_disabled" => "Der Mäher ist ausgeschaltet",
		"off_hatch_open" => "Deaktiviert. Abdeckung ist offen oder PIN-Code erforderlich",
		"unknown" => "Unbekannter Status",
		"error" => "Fehler",
		"error_at_power_up" => "Neustart ...",
		"off_hatch_closed" => "Deaktiviert. Manueller Start erforderlich",
		"ok_cutting_timer_overridden" => "Manuelles Mähen",
		"parked_autotimer" => "Geparkt durch SensorControl",
		"parked_daily_limit_reached" => "Abgeschlossen"
	);
	
	var $status_map_int = array(
		"paused" => 0,
		"ok_cutting" => 10,
		"ok_searching" => 9,
		"ok_charging" => 5,
		"ok_leaving" => 10,
		"wait_updating" => 19,
		"wait_power_up" => 19,
		"parked_timer" => 2,
		"parked_park_selected" => 2,
		"off_disabled" => 0,
		"off_hatch_open" => 0,
		"unknown" => 20,
		"error" => 20,
		"error_at_power_up" => 20,
		"off_hatch_closed" => 0,
		"ok_cutting_timer_overridden" => 10,
		"parked_autotimer" => 2,
		"parked_daily_limit_reached" => 2
	);
	
	var $error_map = array(
		"no_message" => "Kein Fehler",
		"outside_working_area" => "Außerhalb des Arbeitsbereichs",
		"no_loop_signal" => "Kein Schleifensignal",
		"wrong_loop_signal" => "Falsches Schleifensignal",
		"loop_sensor_problem_front" => "Problem Schleifensensor, vorne",
		"loop_sensor_problem_rear" => "Problem Schleifensensor, hinten",
		"trapped" => "Eingeschlossen",
		"upside_down" => "Steht auf dem Kopf",
		"low_battery" => "Niedriger Batteriestand",
		"empty_battery" => "empty_battery",
		"no_drive" => "no_drive",
		"lifted" => "Angehoben",
		"stuck_in_charging_station" => "Eingeklemmt in Ladestation",
		"charging_station_blocked" => "Ladestation blockiert",
		"collision_sensor_problem_rear" => "Problem Stoßsensor hinten",
		"collision_sensor_problem_front" => "Problem Stoßsensor vorne",
		"wheel_motor_blocked_right" => "Radmotor rechts blockiert",
		"wheel_motor_blocked_left" => "Radmotor links blockiert",
		"wheel_drive_problem_right" => "Problem Antrieb, rechts",
		"wheel_drive_problem_left" => "Problem Antrieb, links",
		"cutting_system_blocked" => "Schneidsystem blockiert",
		"invalid_sub_device_combination" => "Fehlerhafte Verbindung",
		"settings_restored" => "Standardeinstellungen",
		"electronic_problem" => "Elektronisches Problem",
		"charging_system_problem" => "Problem Ladesystem",
		"tilt_sensor_problem" => "Kippsensorproblem",
		"wheel_motor_overloaded_right" => "Rechter Radmotor überlastet",
		"wheel_motor_overloaded_left" => "Linker Radmotor überlastet",
		"charging_current_too_high" => "Ladestrom zu hoch",
		"temporary_problem" => "Vorübergehendes Problem",
		"guide_1_not_found" => "SK 1 nicht gefunden",
		"guide_2_not_found" => "SK 2 nicht gefunden",
		"guide_3_not_found" => "SK 3 nicht gefunden",
		"difficult_finding_home" => "Problem die Ladestation zu finden",
		"guide_calibration_accomplished" => "Kalibration des Suchkabels beendet",
		"guide_calibration_failed" => "Kalibration des Suchkabels fehlgeschlagen",
		"temporary_battery_problem" => "Kurzzeitiges Batterieproblem",
		"battery_problem" => "Batterieproblem",
		"alarm_mower_switched_off" => "Alarm! Mäher ausgeschalten",
		"alarm_mower_stopped" => "Alarm! Mäher gestoppt",
		"alarm_mower_lifted" => "Alarm! Mäher angehoben",
		"alarm_mower_tilted" => "Alarm! Mäher gekippt",
		"connection_changed" => "Verbindung geändert",
		"connection_not_changed" => "Verbindung nicht geändert",
		"com_board_not_available" => "COM board nicht verfügbar",
		"slipped" => "Rutscht"
	);
    
    function gardena($user, $pw)
    {
        
        $data = array(
            "sessions" => array(
                "email" => "$user", "password" => "$pw")
                );                     
                                                               
        $data_string = json_encode($data);                                                                                   
                                                                                                                             
        $ch = curl_init(self::LOGINURL);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type:application/json',                                                                                
            'Content-Length: ' . strlen($data_string))                                                                       
        );   
            
        $result = curl_exec($ch);
        $data = json_decode($result);
 
        $this -> token = $data -> sessions -> token;
        $this -> user_id = $data -> sessions -> user_id;
        
        $this -> loadLocations();
        $this -> loadDevices();        
    }
    
    
    function loadLocations()
    {                                       
        $url = self::LOCATIONSURL . $this -> user_id;
                                                                                                                             
        $ch = curl_init($url);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type:application/json',                                                                                
            'X-Session:' . $this -> token)                                                                       
        );   
            
        $this -> locations = json_decode(curl_exec($ch)) -> locations;  
                                                                       
    }
    
    function loadDevices()
    {         
        foreach($this->locations as $location)
        {
            $url = self::DEVICESURL . $location -> id;
                                                                                                                                 
            $ch = curl_init($url);                                                                      
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                     
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type:application/json',                                                                                
                'X-Session:' . $this -> token)                                                                       
            );   
                
            $this -> devices[$location -> id] = json_decode(curl_exec($ch)) -> devices;
        }
    }
         
           
    /**
    * Finds the first occurrence of a certain category type.
    * Example: You want to find your only mower, having one or more gardens. 
    * 
    * @param constant $category
    */
    function getFirstDeviceOfCategory($category)
    {
        foreach($this -> devices as $locationId => $devices)
        {        
            foreach($devices as $device)
                if ($device -> category == $category)
                    return $device;
        }
    }
	
	function getDeviceOfCategory($category, $number)
    {
		$counter = 1;
        foreach($this -> devices as $locationId => $devices)
        {        
            foreach($devices as $device)
                if ($device -> category == $category)
				{
					if($counter == $number)
						return $device;
					else
						$counter++;
				}
        }
    }
    
    function getDeviceLocation($device)
    {
        foreach($this -> locations as $location)
            foreach($location -> devices as $d)
                if ($d == $device -> id)
                    return $location;
    }
    
      
    function sendCommand($device, $command)
    {
        $location = $this -> getDeviceLocation($device);
        
        $url = str_replace("|DEVICEID|", $device -> id, self::CMDURL) . $location -> id;
                             
        $data_string = json_encode($command);       
       
        $ch = curl_init($url);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type:application/json',                                                                                
            'X-Session:' . $this -> token,
            'Content-Length: ' . strlen($data_string)
            ));  
 
        $result =  curl_exec($ch);        
        
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == "204") //success
            return true;
            
        return json_encode($result);
    }       
    
    function getMowerState($device)
    {
        return $this->getPropertyData($device, $this::CATEGORY_MOWER, $this::PROPERTY_STATUS) -> value;
    }
	
	function getMowerName($device)
    {
        return $device->name;
    }
    
    function getDeviceStatusReportFriendly($device)                                        
    {
        $result = "";
        foreach ($device -> status_report_history as $entry)
        {               
             $result .= $entry -> timestamp . " | " . $entry -> source . " | " . $entry -> message . "<br>";
        }                                                           
        
        return $result;
    }
    
    function getAbilityData($device, $abilityName)
    {
        foreach($device -> abilities as $ability)
            if ($ability -> name == $abilityName)
                return $ability;
    }
    
    function getPropertyData($device, $abilityName, $propertyName)
    {
        $ability = $this->getAbilityData($device, $abilityName);
        
        foreach($ability -> properties as $property)
            if ($property -> name == $propertyName)
                return $property;
    }
    
    /**
    * Note "quality 80" seems to be quite the highest possible value (measured with external antenna and 2-3 meters distance)
    * 
    * @param mixed $device
    */
    function getConnectionDataFriendly($device)
    {
        $ability = $this->getAbilityData($device, $this::ABILITY_CONNECTIONSTATE);
        
        $properties = array('quality', 'connection_status', 'state');
        
        foreach ($properties as $property)
        {
            $p = $this->getPropertyData($device, $ability -> name, $property);
            
            echo $property . ": " . $p -> value . " | " . $p -> timestamp . "<br>";
        }
    }
}


?>
###[/EXEC]###