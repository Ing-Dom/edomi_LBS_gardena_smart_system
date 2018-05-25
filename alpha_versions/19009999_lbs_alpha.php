###[DEF]###
[name				= Gardena Smart System Sileno V0.1		]

[e#1	important	= trigger		#init=0					]
[e#2	important	= username								]
[e#3	important	= password								]
[e#50				= Loglevel #init=8						]



[a#1				= mowerstate							]

[v#100				= 0.1 ]
[v#101 				= 19009999 ]
[v#102 				= Gardena Smart System Sileno ]
[v#103 				= 0 ]

###[/DEF]###

###[HELP]###


###[/HELP]###


###[LBS]###
<?
function LB_LBSID($id) {
	if ($E=logic_getInputs($id)) {
		setLogicElementVar($id, 103, $E[50]['value']); //set loglevel to #VAR 103
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
	
	$gardena = new gardena($username, $password);
	$mower = $gardena -> getFirstDeviceOfCategory($gardena::CATEGORY_MOWER);

	$mowerstate = $gardena -> getMowerState($mower);
	
	logic_setOutput($id,1,$mowerstate);


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