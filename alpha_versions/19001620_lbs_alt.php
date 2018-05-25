###[DEF]###
[name				= Gardena Smart Sileno V0.1				]

[e#1	important	= trigger			#init=0				]
[e#2	important	= username								]
[e#3	important	= password								]
[e#4				= mower_number		#init=1				]
[e#5				= park1				#init=0				]
[e#6				= park2				#init=0				]
[e#7				= start				#init=0				]
[e#8				= start_24h			#init=0				]
[e#9				= start_3d			#init=0				]
[e#10				= Loglevel 			#init=8				]



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

[v#100				= 0.1 ]
[v#101 				= 19001620 ]
[v#102 				= Gardena Smart Sileno ]
[v#103 				= 0 ]

###[/DEF]###

###[HELP]###
This LBS connects to the Gardena cloud and can communicate with your Gardena Smart Sileno (R100LiC) auto mower.
It is possible to get some information and send some commands.

Inputs:
E1 - trigger:		Read data from gardena cloud at 1
E2 - username:		Your gardena cloud username (e.g. E-Mail address)
E3 - password:		Your gardena cloud password
E4 - mower_number:	In case you have more than one gardena mower connected to your account, select the correct one here (default: 1)
E5 - park1:			Park the mower until the next timer triggers a start
E6 - park2:			Park the mower until further notice
E7 - start:			Start the mower (resume schedule)
E8 - start_24h:		Start the mower for 24 hours (regardless of schedule)
E9 - start_3d:		Start the mower for 3 days (regardless of schedule)
E10- LogLevel:		Set the PHP-LogLevel (default: 8)

Outputs:
A1 - mower_name:	The name of the mower as configured in the gardena app
A2 - state_text:	The current state of the mower as human readable text
A3 - state_orig:	The current state of the mower as keyword
A4 - battery_level:	The battery level in percent (0-100)
A5 - next_start:	Next scheduled start of the mower - 0 when no start is planned.
A6 - signal:		Signal strengt for the radio connection between mower and smart gateway
A7 - last_connect:	Time of last connection between gardena cloud and mower
A8 - error_text:	The current error state of the mower as human readable text
A9 - error_orig:	The current error state of the mower as keyword
A10- charge_cycles:	The total number of charging cycles
A11- collisions:	The total number of collisions
A12- cut_time:		cutting time (hours)
A13- run_time:		running time (hours)


###[/HELP]###


###[LBS]###
<?
function LB_LBSID($id) {
	if ($E=logic_getInputs($id)) {
		setLogicElementVar($id, 103, $E[10]['value']); //set loglevel to #VAR 103
		if ($E[1]['refresh'] == 1 && $E[1]['value'] == 1) {
			callLogicFunctionExec(LBSID, $id);
		}
	}
}
?>
###[/LBS]###


###[EXEC]###
<?
require(dirname(__FILE__)."/../../../../main/include/php/incl_lbsexec.php");
sql_connect();
set_time_limit(60);

$curl_errno = 0;

logging($id, "Gardena Smart System started");

if ($E = logic_getInputs($id))
{
	
	$username = $E[2]['value'];
	$password = $E[3]['value'];
	$mower_num = $E[4]['value'];
	
	$gardena = new gardena($username, $password);
	$mower = $gardena -> getDeviceOfCategory($gardena::CATEGORY_MOWER, $mower_num);
	
	$mowername = $gardena -> getMowerName($mower);	
	logic_setOutput($id,1,$mowername);
	
	$mowerstate = $gardena -> getMowerState($mower);
	logic_setOutput($id,2,$mowerstate);	//ToDo: Translate
	logic_setOutput($id,3,$mowerstate);
	
	$batterylevel = $gardena -> getPropertyData($mower, "battery", "level") -> value;
	logic_setOutput($id,4,$batterylevel);
	
	$next_start = $gardena -> getPropertyData($mower, "mower", "timestamp_next_start") -> value;
	logic_setOutput($id,5,$next_start); //ToDo: Timezone
	
	$signal = $gardena -> getPropertyData($mower, "radio", "quality") -> value;
	logic_setOutput($id,6,$signal);
	
	$last_time_online = $gardena -> getPropertyData($mower, "device_info", "last_time_online") -> value;
	logic_setOutput($id,7,$last_time_online); //ToDo: Timezone
	
	$error = $gardena -> getPropertyData($mower, "mower", "error") -> value;
	logic_setOutput($id,8,$error); //ToDo: Translate
	logic_setOutput($id,9,$error);
	
	$charging_cycles = $gardena -> getPropertyData($mower, "mower_stats", "charging_cycles") -> value;
	logic_setOutput($id,10,$charging_cycles);
	
	$collisions = $gardena -> getPropertyData($mower, "mower_stats", "collisions") -> value;
	logic_setOutput($id,11,$collisions);
	
	$cutting_time = $gardena -> getPropertyData($mower, "mower_stats", "cutting_time") -> value;
	logic_setOutput($id,12,$cutting_time);
	
	$running_time = $gardena -> getPropertyData($mower, "mower_stats", "running_time") -> value;
	logic_setOutput($id,13,$running_time);
	
	
	
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


}

logging($id, "Gardena Smart System exit");
sql_disconnect();

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