<?

//Steca Solarregler Modul
// Thomas Westerhoff


//Klassendefinition
class STECA extends IPSModule
{
    /**
     * Timer constant
     * maxage of LastUpdate in sec before ReInit
     */
	 
    const MAXAGE = 300;
    //------------------------------------------------------------------------------
    //module const and vars
    //------------------------------------------------------------------------------
    /**
     * Kernel Status "Ready"
     */
    const KR_READY = 10103;
    /**
     * Module Status aktive
     */
    const ST_AKTIV = 102;
    /**
     * Module Status "inactive"
     */
    const ST_INACTIV = 104;
    /**
     * Module Status "Error"
     */
    const ST_ERROR = 201;
    /**
     * Custom Module Status "NoParent"
     */
    const ST_NOPARENT = 202;
    /**
     * IPS Variable Type Boolean
     */
    const VT_Boolean = 0;
    /**
     * IPS Variable Type Integer
     */
    const VT_Integer = 1;
    /**
     * IPS Variable Type Float
     */
    const VT_Float = 2;
    /**
     * IPS Variable Type String
     */
    const VT_String = 3;

    protected $module_data = array();
    protected $module_interfaces = array(
        //IO
        "VirtIO" => "{6179ED6A-FC31-413C-BB8E-1204150CF376}",
        "SerialPort" => "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}",
        "IO-RX" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", //from VirtIO
        "IO-TX" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", //to VirtIO
    );
    protected $DEBUGLOG = '';


    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        $json_file = __DIR__ . "/module.json";

        parent::__construct($InstanceID);
        $json = @file_get_contents($json_file);
        $data = @json_decode($json, true);
        $this->module_data = $data;
        $this->name = $data["name"];
        if (!isset($this->name)) {
            IPS_LogMessage(__CLASS__, "Reading Moduldata from module.json failed!");
            return false;
        }
        $this->DEBUGLOG = IPS_GetLogDir() . "/" . $data["name"] . "debug.log";
    }

    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
		
        //Properties
		$this->RegisterPropertyString('Category', 'STECA Devices');
        $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root
        $this->RegisterPropertyString('LogFile', '');
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyBoolean('Active', false);

		//VariablenProfile
	   // CreateVarProfile($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon) {

//		$this->CreateVarProfile("WGW.Rainfall", 2, " Liter/m²" ,0 , 10, 0 , 2, "Rainfall");
		$this->CreateVarProfile('TempSolar',1,' °C', -200, 500, 1,0,"Temperature");
        $this->CreateVarProfile('PowSolar',2,' kW',0,1000,0.1,1,'');
        $this->CreateVarProfile('FlowSolar',1,' l/min',0,50,1,'');
		$this->CreateVarProfileAlarmSolar();
		
		//        $this->CreateVarProfile('AlarmSolar',0,'ALARM','',$FF0000,'Kein','',$00FF00);
//        $this->CreateVarProfile('WMZSolar',2,'An','',$00FF00,'Aus','',$FF0000);

		
        //Vars
        $this->RegisterVariableString('Buffer', 'Buffer', "", -1);
        IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
        $this->RegisterVariableString('LastUpdate', 'Last Update', "", -4);
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), true);
        $this->RegisterVariableInteger('T1', 'T1', "TempSolar");
        $this->RegisterVariableInteger('T2', 'T2', "TempSolar");
        $this->RegisterVariableInteger('T3', 'T3', "TempSolar");
        $this->RegisterVariableInteger('T4', 'T4', "TempSolar");
        $this->RegisterVariableInteger('T5', 'T5', "TempSolar");
        $this->RegisterVariableInteger('T6', 'T6', "TempSolar");
        $this->RegisterVariableInteger('R1', 'R1', "~Intensity.100");
        $this->RegisterVariableInteger('R2', 'R2', "~Intensity.100");
        $this->RegisterVariableInteger('R3', 'R3', "~Intensity.100");

        $this->RegisterVariableInteger('System', 'System', "");
        $this->RegisterVariableBoolean('WMZ', 'WMZ', "WMZSolar");
        $this->RegisterVariableFloat('p_curr', 'p_curr', "PowSolar");
        $this->RegisterVariableInteger('p_comp', 'p_comp', "");
        $this->RegisterVariableInteger('radiation', 'radiation', "");
        $this->RegisterVariableInteger('Tds', 'Tds', "TempSolar");
        $this->RegisterVariableInteger('v_flow', 'v_flow', "FlowSolar");
        $this->RegisterVariableBoolean('Alarm', 'Alarm', "AlarmSolar");

        //Timers
        $this->RegisterTimer('ReInit', 60000, $this->module_data["prefix"] . '_ReInitEvent($_IPS[\'TARGET\']);');
//        $this->RegisterTimer('ReInit', 60000, "");

        //Connect Parent
        $this->RequireParent($this->module_interfaces['SerialPort']);
        $pid = $this->GetParent();
        if ($pid) {
            $name = IPS_GetName($pid);
            if ($name == "Serial Port") IPS_SetName($pid, __CLASS__ . " Port");
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
	//Variablenprofil für die Windgeschwindigkeit erstellen
	private function CreateVarProfileAlamSolar() {
		if (!IPS_VariableProfileExists("AlarmSolar")) {
			IPS_CreateVariableProfile("AlarmSolar", 0);
			IPS_SetVariableProfileIcon("AlamSolar", "Speaker");
			IPS_SetVariableProfileAssociation("AlarmSolar", 1, "Alarm", "Speaker", 0xFF0000);
			IPS_SetVariableProfileAssociation("AlarmSolar", 0, "kein", "", 0x00FF00);
		 }
	}
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
			
          if ($this->isActive() && $this->HasActiveParent()) {
            $this->SetStatus(self::ST_AKTIV);
            $this->init();
        } else {
            $this->SetStatus(self::ST_INACTIV);
            $this->SetTimerInterval('ReInit', 0);
        }

        }
		
    //--------------------------------------------------------
    /**
     * Destructor
     */
    public function Destroy()
    {
        parent::Destroy();
    }
	
    //------------------------------------------------------------------------------
    /**
     * Check if a parent for Instance $id exists
     * @param $id integer InstanceID
     * @return integer
     */
    protected function GetParent($id = 0)
    {
        $parent = 0;
        if ($id == 0) $id = $this->InstanceID;
        if (IPS_InstanceExists($id)) {
            $instance = IPS_GetInstance($id);
            $parent = $instance['ConnectionID'];
        } else {
            $this->debug(__FUNCTION__, "Instance #$id doesn't exists");
        }
        return $parent;
    }


    protected function HasActiveParent($id = 0)
    {
        if ($id == 0) $id = $this->InstanceID;
        $parent = $this->GetParent($id);
        if ($parent > 0) {
            $status = $this->GetInstanceStatus($parent);
            if ($status == self::ST_AKTIV) {
                return true;
            } else {
                //IPS_SetInstanceStatus($id, self::ST_NOPARENT);
                $this->debug(__FUNCTION__, "Parent not active for Instance #" . $id);
                return false;
            }
        }
        $this->debug(__FUNCTION__, "No Parent for Instance #" . $id);
        return false;
    }
	
    //--------------------------------------------------------
    /**
     * Check if the given Instance is active
     * @param int $id
     * @return bool
     */
    protected function isActive($id = 0)
    {
        if ($id == 0) $id = $this->InstanceID;
        $res = (bool)IPS_GetProperty($id, 'Active');
        return $res;
    }
	
    private function GetLogFile()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'LogFile');
    }


    private function GetParentCategory()
    {
        return (Integer)IPS_GetProperty($this->InstanceID, 'ParentCategory');
    }

    private function GetBuffer()
    {
        $id = $this->GetIDForIdent('Buffer');
        $val = GetValueString($id);
        return $val;
    }

    private function SetBuffer($val)
    {
        $id = $this->GetIDForIdent('Buffer');
        SetValueString($id, $val);
    }

    //------------------------------------------------------------------------------
    //---Events
    //------------------------------------------------------------------------------
    /**
     * Timer Event to reinitialize system
     * Executed if there are no valid data within Timer as indicated by LastUpdate
     */
    public function ReInitEvent()
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
    //device functions
    //------------------------------------------------------------------------------
    /**
     * Set IO properties
     */
    private function SyncParent()
    {
        $ParentID = $this->GetParent();
        if ($ParentID > 0) {
            $this->debug(__FUNCTION__, 'entered');
            $ParentInstance = IPS_GetInstance($ParentID);
            if ($ParentInstance['ModuleInfo']['ModuleID'] == $this->module_interfaces['SerialPort']) {
                if (IPS_GetProperty($ParentID, 'DataBits') <> 8)
                    IPS_SetProperty($ParentID, 'DataBits', 8);
                if (IPS_GetProperty($ParentID, 'StopBits') <> 1)
                    IPS_SetProperty($ParentID, 'StopBits', 1);
                if (IPS_GetProperty($ParentID, 'BaudRate') <> 9600)
                    IPS_SetProperty($ParentID, 'BaudRate', 9600);
                if (IPS_GetProperty($ParentID, 'Parity') <> 'None')
                    IPS_SetProperty($ParentID, 'Parity', "None");

                if (IPS_HasChanges($ParentID)) {
                    IPS_SetProperty($ParentID, 'Open', false);
                    @IPS_ApplyChanges($ParentID);
                    IPS_Sleep(200);
                    $port = IPS_GetProperty($ParentID, 'Port');
                    if ($port) {
                        IPS_SetProperty($ParentID, 'Open', true);
                        @IPS_ApplyChanges($ParentID);
                    }
                }
            }//serialPort
        }//parentID
    }//function
	
	
    //------------------------------------------------------------------------------
    /**
     * Initialization sequence
     */
    private function init()
    {
        $this->debug(__FUNCTION__, 'Init entered');
        $this->SyncParent();
        $this->SetBuffer('');
        $this->SetTimerInterval('ReInit', 60000);
    }
	


//------------------------------------------------------------------------------
    /**
     * Data Interface from Parent(IO-RX)
     * @param string $JSONString
     *Daten aus dem Serial port lesen
	 */
    public function ReceiveData($JSONString)
    {
        //status check triggered by data
        if ($this->isActive() && $this->HasActiveParent()) {
            $this->SetStatus(self::ST_AKTIV);
        } else {
            $this->SetStatus(self::ST_INACTIV);
            $this->debug(__FUNCTION__, 'Data arrived, but dropped because inactiv:' . $JSONString);
            return;
        }
        // decode Data from Device Instanz
        if (strlen($JSONString) > 0) {
            $this->debug(__FUNCTION__, 'Data arrived:' . $JSONString);
            $this->debuglog($JSONString);
            // decode Data from IO Instanz
            $data = json_decode($JSONString);
            //entry for data from parent

            $buffer = $this->GetBuffer();
            if (is_object($data)) $data = get_object_vars($data);
           if (isset($data['DataID'])) {
                $target = $data['DataID'];
                if ($target == $this->module_interfaces['IO-RX']) {
     				$this->debug(__CLASS__, "decode buffer");
  //                  $buffer .= utf8_decode($data['Buffer']);
                      $buffer .= $data['Buffer'];
                   
  		//		   $this->debug(__CLASS__, strToHex($buffer));
					   $this->debug(__CLASS__, $buffer);
					
                    $bl = strlen($buffer);
                    if ($bl > 500) {
                        $buffer = substr($buffer, 500);
                        $this->debug(__CLASS__, "Buffer length exceeded, dropping...");
                    }
                    $inbuf = $this->ReadRecord($buffer); //returns remaining chars
                    $this->SetBuffer($inbuf);
                }//target
            }//dataid
            else {
                $this->debug(__FUNCTION__, 'No DataID supplied');
            }//dataid
        } else {
            $this->debug(__FUNCTION__, 'strlen(JSONString) == 0');
        }//else len json
    }//func

	
	
	
	
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
			
            $data = substr($inbuf, 0, $pos);
 //           $this->debug(__CLASS__, 'Data:' . $data);
            
			$inbuf = substr($inbuf, $pos);
            //Daten decodieren 
            $steca_data = $this->parse_solar($data);
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
    
        //clear record
        //$1;1;;21,2;22,4;25,1;14,6;15,8;12,1;;24,5;37;;78;72;;75;;:50;16,0;42;8,0;455;1;0<cr><lf>
        $this->debug(__FUNCTION__, 'Entered:' . $data);
        
		//Array definieren
		$steca_data = array();
        $records = array();
        
//		for ($p = 0; $p < self::MAXSENSORS; $p++) {
//            $records[$p] = array('typ' => '', 'id' => '', 'sensor' => '', 'temp' => '', 'hum' => '', 'lost' => '');
//        }

        //Felddefinitiionen
        $steca_data['Time'] = '';  
        $steca_data['T1'] = '';
        $steca_data['T2'] = '';
	    $steca_data['T3'] = '';
	    $steca_data['T4'] = '';
	    $steca_data['T5'] = '';
	    $steca_data['T6'] = '';
	    $steca_data['R1'] = '';
	    $steca_data['R2'] = '';
	    $steca_data['R3'] = '';
        $steca_data['System'] = '';
	    $steca_data['WMZ'] = '';
	    $steca_data['p-curr'] = '';
	    $steca_data['p_comp'] = '';
	    $steca_data['radiation'] = '';
	    $steca_data['TDS'] = '';
	    $steca_data['v_flow'] = '';
	    $steca_data['Alarm'] = '';
		
		
        $fields = explode(';', $data);
        $f = 0;  //Positionszähler
//        $this->debug(__FUNCTION__, "Data: " . print_r($fields, true));
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
            elseif ($f == 7) {$steca_data['R1'] = $s;
						SetValue($this->GetIDForIdent("R1"), $steca_data['T7']);}
            elseif ($f == 7) {$steca_data['R2'] = $s;
						SetValue($this->GetIDForIdent("R2"), $steca_data['R2']);}
            elseif ($f == 7) {$steca_data['R3'] = $s;
						SetValue($this->GetIDForIdent("R3"), $steca_data['R3']);}
            elseif ($f == 19) {$steca_data['Alarm'] = ($s == 'ERR') ? 'YES' : 'NO';}
        }//while
 //       $this->debug(__CLASS__, " Parsed Data:" . print_r($steca_data, true));
        return $steca_data;
    }//function

    /**
     * Log an debug message
     * PHP modules cannot enter data to debug window,use messages instead
     * @param $topic
     * @param $data
     */
    protected function debug($topic, $data)
    {
        if (method_exists($this,"SendDebug")) {
            //available as of #150 (2016-04-22)
            $this->SendDebug($topic,$data,0);
        }
    }
    //------------------------------------------------------------------------------
    /**
     * check if debug is enabled
     * @return bool
     */
    protected function isDebug()
    {
        $debug = @IPS_GetProperty($this->InstanceID, 'Debug');
        return ($debug === true);
    }
    //--------------------------------------------------------
    /**
     * Log Debug to its own file
     * @param $data
     */
    protected function debuglog($data)
    {
        if (!$this->isDebug()) return;
        $fname = $this->DEBUGLOG;
        $o = @fopen($fname, "a");
        if (!$o) {
            $this->debug(__FUNCTION__, 'Cannot open ' . $fname);
            return;
        }
        fwrite($o, $data . "\n");
        fclose($o);
    }
	
    protected function GetInstanceStatus($id = 0)
    {
        if ($id == 0) $id = $this->InstanceID;
        $inst = IPS_GetInstance($id);
        return $inst['InstanceStatus'];
    }
	

	
}//class
?>
