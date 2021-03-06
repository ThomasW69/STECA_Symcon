<?

//Steca Solarregler Modul
// Thomas Westerhoff

/**
 * common module helper function
 */
include_once(__DIR__ . "/../libs/module_helper.php");

//Klassendefinition
class STECA extends T2DModule
{
    /**
     * Timer constant
     * maxage of LastUpdate in sec before ReInit
     */
	 
 
    //------------------------------------------------------------------------------
    //module const and vars
    //------------------------------------------------------------------------------
    const MAXAGE = 300;

    ///[capvars]
    /**
     * mapping array for capabilities to variables
     * @var array $capvars
     */
    protected $capvars = array(
        'Buffer' 	=>	array("ident" =>'Buffer',		"type" => self::VT_String,	"name"=>'Buffer',					"profile"=>'~String',		"pos" => -1),
        'LastUpdate'=>	array("ident" =>'LastUpdate',	"type" => self::VT_String,	"name"=>'LastUpdate',				"profile"=>'~String',		"pos" => 0),
        'Name'		=>	array("ident" =>'Name',			"type" => self::VT_String,  "name"=>'Name', 					"profile"=>'~String',		"pos" => 0),
        "T1" 		=> 	array("ident" => 'T1', 			"type" => self::VT_Integer, "name" => 'T1', 					"profile" => 'TempSolar', 	"pos" => 0),
        "T2" 		=> 	array("ident" => 'T2', 			"type" => self::VT_Integer, "name" => 'T2', 					"profile" => 'TempSolar', 	"pos" => 1),
        "T3" 		=> 	array("ident" => 'T3', 			"type" => self::VT_Integer, "name" => 'T3', 					"profile" => 'TempSolar', 	"pos" => 2),
        "T4" 		=> 	array("ident" => 'T4', 			"type" => self::VT_Integer, "name" => 'T4', 					"profile" => 'TempSolar', 	"pos" => 3),
        "T5" 		=> 	array("ident" => 'T5', 			"type" => self::VT_Integer, "name" => 'T5', 					"profile" => 'TempSolar', 	"pos" => 4),
        "T6" 		=> 	array("ident" => 'T6', 			"type" => self::VT_Integer, "name" => 'T6', 					"profile" => 'TempSolar', 	"pos" => 5),
        "R1" 		=> 	array("ident" => 'R1', 			"type" => self::VT_Integer, "name" => 'R1', 					"profile" => 'Intensity.1', "pos" => 6),
        "R2" 		=> 	array("ident" => 'R2', 			"type" => self::VT_Integer, "name" => 'R2', 					"profile" => 'Intensity.1', "pos" => 7),
        "R3" 		=> 	array("ident" => 'R3', 			"type" => self::VT_Integer, "name" => 'R3', 					"profile" => 'Intensity.1', "pos" => 8),
        "System" 	=> 	array("ident" => 'System', 		"type" => self::VT_Integer, "name" => 'System', 				"profile" => '', 			"pos" => 9),
        "WMZ" 		=> 	array("ident" => 'WMZ', 		"type" => self::VT_Boolean, "name" => 'Wärmemengenzählung', 	"profile" => '', 			"pos" => 10),
        "p_curr" 	=> 	array("ident" => 'p_curr', 		"type" => self::VT_Float, 	"name" => 'Momentanleistung', 		"profile" => 'PowSolar', 	"pos" => 11),
        "p_comp" 	=> 	array("ident" => 'p_comp', 		"type" => self::VT_Integer, "name" => 'Gesamtwärmemenge', 		"profile" => 'SolarWM', 	"pos" => 12),
        "radiation" => 	array("ident" => 'radiation', 	"type" => self::VT_Integer, "name" => 'Einstrahlung', 			"profile" => '', 			"pos" => 13),
        "Country" 	=> 	array("ident" => 'Country', 	"type" => self::VT_String, 	"name" => 'Ländercode', 			"profile" => '~String', 	"pos" => 14),
        "Model" 	=> 	array("ident" => 'Model', 		"type" => self::VT_String, 	"name" => 'Modellvariante', 		"profile" => '~String', 	"pos" => 15),
        "Tds"		=> 	array("ident" => "Tds", 		"type" => self::VT_Integer, "name" => 'temperatur Direktsensor',"profile" => 'TempSolar', 	"pos" => 16), 
        "v_flow" 	=> 	array("ident" => "v_flow", 		"type" => self::VT_Integer, "name" => 'Volumenstrom', 			"profile" => 'FlowSolar', 	"pos" => 17),
        "Alarm" 	=> 	array("ident" => "alarm", 		"type" => self::VT_Boolean, "name" => 'Alarm', 					"profile" => 'AlarmSolar', 	"pos" => 18)
    );
    ///[capvars]


    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        $json=__DIR__."/module.json";
        parent::__construct($InstanceID,$json);
     /*   $data = @json_decode($json, true);
        $this->module_data = $data;
        $this->name = $data["name"];
        if (!isset($this->name)) {
            IPS_LogMessage(__CLASS__, "Reading Moduldata from module.json failed!");
            return false;
        }
        $this->useBufferVar=! (method_exists($this,'GetBuffer'));
        $this->DEBUGLOG = IPS_GetLogDir() . "/" . $data["name"] . "debug.log";
        return true;*/
    }

	

    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
		
		// register property
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('Typ', '');
        $this->RegisterPropertyString('Class', '');
        $this->RegisterPropertyString('CapList', '');
        $this->RegisterPropertyBoolean('Debug', false);
       
	   //Properties
		//muss nochmal aufgeräumt werden
 		$this->RegisterPropertyString('Category', 'STECA Devices');
        $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root
        $this->RegisterPropertyInteger('ParentInstance', 0); 
        $this->RegisterPropertyString('LogFile', '');
        $this->RegisterPropertyBoolean('AutoCreate', true);

        $this->RegisterPropertyBoolean('Active', false);

		//VariablenProfile
        $this->check_profile('TempSolar', self::VT_Integer, "", " °C", "Temperature", -10, +500, 1, 0, false);
        $this->check_profile('PowSolar', self::VT_Float, "", " kW", "", 0, +1000, 0.1, 0, false);
        $this->check_profile('FlowSolar', self::VT_Integer, "", " l/min", "", 0, +50, 1, 0, false);
        $this->check_profile('SolarWM', self::VT_Integer, "", " kWh", "", 0, 0, 1, 0, false);

		$this->CreateVarProfileAlarmSolar();
		$this->CreateVarProfileIntensity();

        $this->CreateStatusVars();

        //hide some Vars
        IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), true);

        //Timers
      //  $this->RegisterTimer('ReInit', 60000, $this->module_data['prefix'] . '_ReInitEvent($_IPS[\'TARGET\']);');

        //Connect Parent
        $this->RequireParent($this->module_interfaces['Cutter']);
        $pid = $this->GetParent();
        $this->debug(__FUNCTION__, "ParentID: $pid");
		
        if ($pid) {
            $name = IPS_GetName($pid);
            if ($name == "Cutter") IPS_SetName($pid, __CLASS__ . " Cutter");
			}

        //call init if ready and activated
        if (IPS_GetKernelRunlevel() == self::KR_READY) {
            if ($this->isActive()) {
                $this->SetStatus(self::ST_AKTIV);
                $this->init();
            } else {
                $this->SetStatus(self::ST_INACTIV);
                $this->SetTimerInterval('ReInit', 0);
            }
        }
		
	}

	//Hier ein paar Hilfsfunktionen
	// Variablenprofile erstellen
	private function CreateVarProfile($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon) {
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, $ProfileType);
			IPS_SetVariableProfileText($name, "", $Suffix);
			IPS_SetVariableProfileValues($name, $MinValue, $MaxValue, $StepSize);
			IPS_SetVariableProfileDigits($name, $Digits);
			IPS_SetVariableProfileIcon($name, $Icon);
        }
    }		
	//Variablenprofil für den Alarm (Stagnationswarnung) erzeugen
	private function CreateVarProfileAlarmSolar() {
		if (!IPS_VariableProfileExists("AlarmSolar")) {
			IPS_CreateVariableProfile("AlarmSolar", 0);
			IPS_SetVariableProfileIcon("AlarmSolar", "Speaker");
			IPS_SetVariableProfileAssociation("AlarmSolar", 1, "Alarm", "Speaker", 0xFF0000);
			IPS_SetVariableProfileAssociation("AlarmSolar", 0, "kein", "", 0x00FF00);
		 }
	}
	
	private function CreateVarProfileIntensity() {
		if (!IPS_VariableProfileExists("Intensity.1")) {
			IPS_CreateVariableProfile("Intensity.1", 1);
			IPS_SetVariableProfileAssociation("Intensity.1", 1, "An", "", 0x00FF00);
			IPS_SetVariableProfileAssociation("Intensity.1", 0, "Aus", "", 0x808080);
		 }
	}


		
    //------------------------------------------------------------------------------
    /**
     * Destructor
     */
    public function Destroy()
    {

        parent::Destroy();
    }

    //------------------------------------------------------------------------------
    /**
     * overload internal IPS_ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        // Diese Zeile nicht loeschen
        parent::ApplyChanges();
        if (IPS_GetKernelRunlevel() == self::KR_READY) {
            if ($this->HasActiveParent()) {
                $this->SetStatus(self::ST_AKTIV);
            } else {
                $this->SetStatus(self::ST_NOPARENT);
            } //check status
        }
        //must be here!!
        $this->SetStatusVariables(); //Update Variables
    }


    //------------------------------------------------------------------------------
    //---Events
    //------------------------------------------------------------------------------
    /**
     * Timer Event to reinitialize system
     * Executed if there are no valid data within Timer as indicated by LastUpdate
     */
    public function ReInitEvent() //Aktuell nicht gebraucht
    {
        $id = @$this->GetIDForIdent('LastUpdate');
        if (!$id) return;
        $var = IPS_GetVariable($id);
        if (!$var) return;
        $last = $var['VariableUpdated'];
        //if (!$last) $last=0;
        $now = time();
        $diff = $now - $last;
        $this->debug(__FUNCTION__, "last update $diff s ago");
        if (($diff > self::MAXAGE) && $this->isActive() && $this->HasActiveParent()) {
            $this->init();
        }
    }

//------------------------------------------------------------------------------
    //Get/Set
    //------------------------------------------------------------------------------
    /**
     * Get Property DeviceID
     * @return string
     */
    private function GetDeviceID()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'DeviceID');
    }

    //------------------------------------------------------------------------------
    /**
     * Get Property Type
     * @return string
     */
    private function GetType()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'Typ');
    }


    //------------------------------------------------------------------------------
    /**
     * GetProperty Modul class of creator
     * @return string
     */
    private function GetClass()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'Class');
    }

    //------------------------------------------------------------------------------
    /**
     * Initialization sequence
     */
    private function init()
    {
        $this->debug(__FUNCTION__, 'Init entered');
       // $this->SetLocalBuffer('');
       // $this->SetTimerInterval('ReInit', 60000);
		$pid = $this->GetParent();
        $this->debug(__FUNCTION__, "ParentID ist $pid");
    }
	


//------------------------------------------------------------------------------
    /**
     * Data Interface from Parent(IO-RX)
     * @param string $JSONString
     *Daten aus dem Cutter lesen
	 */
    public function ReceiveData($JSONString)
    {
        $this->debug(__FUNCTION__, 'Receivedata entered');
       //trigger status check
        if ($this->HasActiveParent()) {
            $this->SetStatus(self::ST_AKTIV);
        }else{
            $this->SetStatus(self::ST_NOPARENT);
        }

        // decode Data from Device Instanz
        if (strlen($JSONString) > 0) {
            // decode Data from IO Instanz
            $this->debug(__FUNCTION__, 'Data arrived:' . $JSONString);
            //$this->debuglog($JSONString);
            // decode Data from IO Instanz
            $data = json_decode($JSONString);
            //entry for data from parent
            if (is_object($data)) $data = get_object_vars($data);
            if (isset($data['DataID'])) {
                $target = $data['DataID'];
                if ($target == $this->module_interfaces['IO-RX']) {
                    if (isset($data['Buffer'])) {
                        $Device = $data['DeviceID'];
                        $typ = $data['Typ'];
                        $class = $data['Class'];
                        //call data point
                        $myID = $this->GetDeviceID();
                        $myType = $this->GetType();
                        $myClass = $this->GetClass();
                        //nur wenn die Daten für mich bestimmt sind
                        $this->debug(__FUNCTION__, "$Device(Typ:$typ,Class:$class)");
                        $this->debug(__FUNCTION__, "$myID(Typ:$myTyp,Class:$myClass)");

						if (($myID == $Device) && ($myType == $typ) && ($myClass == $class)) {
                            $this->debug(__FUNCTION__, "$Device(Typ:$typ,Class:$class)");
                            $st_data = $data['Buffer'];
                            if (is_object($st_data)) $st_data = get_object_vars($st_data);
                            
							//$this->ParseData($ws_data);
							$steca_data = $this->parse_solar($st_data);
                        }
						else {
                            $this->debug(__FUNCTION__, "IDs passen nicht");
						}

                    } else {
                        $this->debug(__FUNCTION__, 'Interface Data Error');
                    }
                }
            }
        } else {
            $this->debug(__FUNCTION__, 'strlen(JSONString) == 0');
        }
    }
	
	
	
	
	    //------------------------------------------------------------------------------
    /**
     * Takes an input string and prepare it for parsing
     * @param $inbuf
     * @return string
     */
    public function ReadRecord($inbuf)
    {
        $this->debug(__CLASS__, 'ReadRecord:' . $inbuf);
        while (strlen($inbuf) > 0) {
            $pos = strpos($inbuf, chr(10));
            $this->debug(__FUNCTION__, 'Pos:' . $pos);
			
            if (!$pos) {
                return $inbuf;
            }
			if ($pos >=80) {
            $data = substr($inbuf, 0, $pos);
 //           $this->debug(__CLASS__, 'Data:' . $data);
            
			$inbuf = substr($inbuf, $pos);
            //Daten decodieren 
            $steca_data = $this->parse_solar($data);
			}
        }//while
        return $inbuf;
    }//function

    //------------------------------------------------------------------------------
    /**
     * parses an record string
     * @param String $data
     * @return array
	 * noch anpassen für die STECA Strings
     */
    private function parse_solar($data)
    {
   
        $this->debug(__FUNCTION__, 'Entered:' . $data);
        
		//Array definieren
		$steca_data = array();
        $records = array();
        

        //Felddefinitionen
        $steca_data['Time'] 	= '';  
        $steca_data['T1'] 		= '';
        $steca_data['T2'] 		= '';
	    $steca_data['T3'] 		= '';
	    $steca_data['T4'] 		= '';
	    $steca_data['T5'] 		= '';
	    $steca_data['T6'] 		= '';
	    $steca_data['R1'] 		= '';
	    $steca_data['R2'] 		= '';
	    $steca_data['R3'] 		= '';
        $steca_data['System'] 	= '';
	    $steca_data['WMZ'] 		= '';
	    $steca_data['p_curr'] 	= '';
	    $steca_data['p_comp'] 	= '';
	    $steca_data['radiation']= '';
	    $steca_data['Country'] 	= '';
	    $steca_data['Model'] 	= '';
	    $steca_data['TDS'] 		= '';
	    $steca_data['v_flow'] 	= '';
	    $steca_data['Alarm'] 	= '';
		
		
        $fields = explode(';', $data);
        $f = 0;  //Positionszähler
        $this->debug(__FUNCTION__, "Data: " . print_r(count($fields), true));
       
		if (count($fields) >= 20){
		while ($f < count($fields) - 1) {
            $f++;
            $s = $fields[$f];  //String puffer für feld $f
            if ($s == '') continue;
            $this->debug(__CLASS__, 'Field:' . $f . '=' . $s);
            if ($f ==1) {$steca_data['T1'] = $s;
		    SetValue($this->GetIDForIdent("T1"), $steca_data['T1']);}
            elseif ($f == 2) {$steca_data['T2'] = $s;
					    SetValue($this->GetIDForIdent("T2"), $steca_data['T2']);}
            elseif ($f == 3) {$steca_data['T3'] = $s;
					    SetValue($this->GetIDForIdent("T3"), $steca_data['T3']);}
            elseif ($f == 4) {$steca_data['T4'] = $s;
					    SetValue($this->GetIDForIdent("T4"), $steca_data['T4']);}
            elseif ($f == 5) {$steca_data['T5'] = $s;
					    SetValue($this->GetIDForIdent("T5"), $steca_data['T5']);}
            elseif ($f == 6) {$steca_data['T6'] = $s;
						SetValue($this->GetIDForIdent("T6"), $steca_data['T6']);}
            elseif ($f == 8) {$steca_data['R1'] = $s;
						SetValue($this->GetIDForIdent("R1"), $steca_data['R1']);}
            elseif ($f == 9) {$steca_data['R2'] = $s;
						SetValue($this->GetIDForIdent("R2"), $steca_data['R2']);}
            elseif ($f == 10) {$steca_data['R3'] = $s;
						SetValue($this->GetIDForIdent("R3"), $steca_data['R3']);}
            elseif ($f == 11) {$steca_data['System'] = $s;
						SetValue($this->GetIDForIdent("System"), $steca_data['System']);}
            elseif ($f == 12) {$steca_data['WMZ'] = $s;
						SetValue($this->GetIDForIdent("WMZ"), $steca_data['WMZ']);}
            elseif ($f == 13) {$steca_data['p_curr'] = intval($s)/10;
						SetValue($this->GetIDForIdent("p_curr"), $steca_data['p_curr']);}
            elseif ($f == 14) {$steca_data['p_comp'] = $s;
						SetValue($this->GetIDForIdent("p_comp"), $steca_data['p_comp']);}
            elseif ($f == 15) {$steca_data['radiation'] = $s;
						SetValue($this->GetIDForIdent("radiation"), $steca_data['radiation']);}
            elseif ($f == 16) {$steca_data['Country'] = $s;
						SetValue($this->GetIDForIdent("Country"), $steca_data['Country']);}
            elseif ($f == 18) {$steca_data['Model'] = $s;
						SetValue($this->GetIDForIdent("Model"), $steca_data['Model']);}
            elseif ($f == 19) {$steca_data['Tds'] = $s;
						SetValue($this->GetIDForIdent("Tds"), $steca_data['Tds']);}
             elseif ($f == 20) {$steca_data['v_flow'] = $s;
						SetValue($this->GetIDForIdent("v_flow"), $steca_data['v_flow']);}

//			elseif ($f == 21) {$steca_data['Alarm'] = ($s == 'ERR') ? 'TRUE' : 'FALSE';}
  			elseif ($f == 21) {$steca_data['Alarm'] = ($s == '1') ? 'TRUE' : 'FALSE';}
        }//while
		}
 //       $this->debug(__CLASS__, " Parsed Data:" . print_r($steca_data, true));

    $this->debug(__FUNCTION__, 'Finished');
    $vid = @$this->GetIDForIdent('LastUpdate');
	$datum = date("d.m.Y H:i:s",time());
    SetValueString($vid, $datum);

    return $steca_data;
    }//function

 
	
}//class
?>
