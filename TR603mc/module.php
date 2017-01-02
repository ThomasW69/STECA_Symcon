<?

//Steca Solarregler Modul
// Thomas Westerhoff

//html errors in modulen abschalten
ini_set("html_errors", "0");

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

    /**
     * Device action capabilities
     * to be overwrite in implementation
     * @var array $actions
     */
    protected $actions = array();
    protected $name = '';

    protected $module_interfaces = array(
        //IO
        "VirtIO" => "{6179ED6A-FC31-413C-BB8E-1204150CF376}",
        "SerialPort" => "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}", //Wird eigentlich nicht benötigt, da alles über den Cutter geht
        "Cutter" => "{AC6C6E74-C797-40B3-BA82-F135D941D1A2}",//Eigentlicher Cutter als Parent instanz
        "IO-RX" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", //from VirtIO
        "IO-TX" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", //to VirtIO
    );
    protected $DEBUGLOG = '';
	protected $useBufferVar=True;
	    //------------------------------------------------------------------------------
    //module const and vars
    //------------------------------------------------------------------------------
    ///[capvars]
    /**
     * mapping array for capabilities to variables
     * @var array $capvars
     */
    protected $capvars = array(
        'Buffer'=>array("ident"=>'Buffer',"type"=>self::VT_String,"name"=>'Buffer','profile'=>'~String',"pos"=>-1),
        'LastUpdate'=>array("ident"=>'LastUpdate',"type"=>self::VT_String,"name"=>'LastUpdate','profile'=>'~String',"pos"=>0),
        'Name'=>array("ident"=>'Name',"type"=>self::VT_String,"name"=>'Name','profile'=>'~String',"pos"=>0),
        "T1" => array("ident" => 'T1', "type" => self::VT_Integer, "name" => 'T1', "profile" => 'TempSolar', "pos" => 0),
        "T2" => array("ident" => 'T2', "type" => self::VT_Integer, "name" => 'T2', "profile" => 'TempSolar', "pos" => 1),
        "T3" => array("ident" => 'T3', "type" => self::VT_Integer, "name" => 'T3', "profile" => 'TempSolar', "pos" => 2),
        "T4" => array("ident" => 'T4', "type" => self::VT_Integer, "name" => 'T4', "profile" => 'TempSolar', "pos" => 3),
        "T5" => array("ident" => 'T5', "type" => self::VT_Integer, "name" => 'T5', "profile" => 'TempSolar', "pos" => 4),
        "T6" => array("ident" => 'T6', "type" => self::VT_Integer, "name" => 'T6', "profile" => 'TempSolar', "pos" => 5),
        "R1" => array("ident" => 'R1', "type" => self::VT_Integer, "name" => 'R1', "profile" => 'Intensity.1', "pos" => 6),
        "R2" => array("ident" => 'R2', "type" => self::VT_Integer, "name" => 'R2', "profile" => 'Intensity.1', "pos" => 7),
        "R3" => array("ident" => 'R3', "type" => self::VT_Integer, "name" => 'R3', "profile" => 'Intensity.1', "pos" => 8),
        "System" => array("ident" => 'System', "type" => self::VT_Integer, "name" => 'System', "profile" => '', "pos" => 9),
        "WMZ" => array("ident" => 'WMZ', "type" => self::VT_Boolean, "name" => 'Wärmemengenzählung', "profile" => '', "pos" => 10),
        "p_curr" => array("ident" => 'p_curr', "type" => self::VT_Float, "name" => 'Momentanleistung', "profile" => 'PowSolar', "pos" => 11),
        "p_comp" => array("ident" => 'p_comp', "type" => self::VT_Integer, "name" => 'Gesamtwärmemenge', "profile" => 'SolarWM', "pos" => 12),
        "radiation" => array("ident" => 'radiation', "type" => self::VT_Integer, "name" => 'Einstrahlung', "profile" => '', "pos" => 13),
        "Country" => array("ident" => 'Country', "type" => self::VT_String, "name" => 'Ländercode', "profile" => '~String', "pos" => 14),
        "Model" => array("ident" => 'Model', "type" => self::VT_String, "name" => 'Modellvariante', "profile" => '~String', "pos" => 15),
        "Tds" => array("ident" => "Tds", "type" => self::VT_Integer, "name" => 'temperatur Direktsensor', "profile" => 'TempSolar', "pos" => 16), //reversed state
        "v_flow" => array("ident" => "v_flow", "type" => self::VT_Integer, "name" => 'Volumenstrom', "profile" => 'FlowSolar', "pos" => 17),
        "Alarm" => array("ident" => "alarm", "type" => self::VT_Boolean, "name" => 'Alarm', "profile" => 'AlarmSolar', "pos" => 18)

    );
    ///[capvars]



    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        $json=__DIR__."/module.json";
        parent::__construct($InstanceID,$json);
        $data = @json_decode($json, true);
        $this->module_data = $data;
        $this->name = $data["name"];
        if (!isset($this->name)) {
            IPS_LogMessage(__CLASS__, "Reading Moduldata from module.json failed!");
            return false;
        }
        $this->DEBUGLOG = IPS_GetLogDir() . "/" . $data["name"] . "debug.log";
        return true;
    }

    //-------------------------------------------------------------------------------
    /**
     * Check profile by name if exists, else create
     * @param String $pname Name
     * @param integer $typ Variable Typ (0..3)
     * @param String $prefix Prefix before value
     * @param String $suffix Suffix after value
     * @param String $icon Icon Name
     * @param integer $min min value
     * @param integer $max max value
     * @param integer $step step value
     * @param integer $digit digits for formatting
     * @param boolean $drop drop existing profile first
     */
    protected function check_profile($pname, $typ, $prefix, $suffix, $icon, $min, $max, $step, $digit = 0, $drop = false)
    {
        //use logmessages instead of debug because this isnt available in early stage
        if (IPS_VariableProfileExists($pname) && $drop) IPS_DeleteVariableProfile($pname);
        if (!IPS_VariableProfileExists($pname)) {
            IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.") Create VariableProfile $pname");
            if (IPS_CreateVariableProfile($pname, $typ)) {
                IPS_SetVariableProfileText($pname, $prefix, $suffix);
                if (isset($min) && isset($max) && isset($step)) {
                    IPS_SetVariableProfileValues($pname, $min, $max, $step);
                }
                if (isset($digit)) {
                    IPS_SetVariableProfileDigits($pname, $digit);
                }
                if ($icon) {
                    IPS_SetVariableProfileIcon($pname, $icon);
                }
            } else {
                IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.") Cannot Create VariableProfile $pname");
            }
        }
    }

    //------------------------------------------------------------------------------
    /**
     * create status variables out of capvar definitions from device
     */
    protected function CreateStatusVars()
    {
        //use logmessages instead of debug because this isnt available in early stage
        $vid = 0;
        $caps = $this->capvars;
        //IPS_LogMessage(__CLASS__,__FUNCTION__. "::ID #".$this->InstanceID. " Caps ".print_r($caps,true));
        foreach (array_keys($caps) as $cap) {
            $var = $caps[$cap];
            $type = $var["type"];
            switch ($type) {
                case self::VT_Boolean:
                    $vid = $this->RegisterVariableBoolean($var["ident"], $var["name"], $var["profile"], $var["pos"]);
                    break;
                case self::VT_Integer:
                    $vid = $this->RegisterVariableInteger($var["ident"], $var["name"], $var["profile"], $var["pos"]);
                    break;
                case self::VT_Float:
                    $vid = $this->RegisterVariableFloat($var["ident"], $var["name"], $var["profile"], $var["pos"]);
                    break;
                case self::VT_String:
                    $vid = $this->RegisterVariableString($var["ident"], $var["name"], $var["profile"], $var["pos"]);
                    break;
                default:
                    //IPS_LogMessage(__CLASS__,__FUNCTION__."(#".$this->InstanceID.") Unknown Typ ($type)");

            }
            if ($vid) {
                IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.") Create Variable for Cap $cap ( $vid)");
            } else {

                IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.")  Create Variable failed");
            }


        }

    }


    //------------------------------------------------------------------------------
    /**
     * remove variables not matching actual capabilities
     */

    protected function SetStatusVariables()
    {
        //use logmessages instead of debug because this isnt available in early stage
        $caps = $this->GetCaps();
        $actions= $this->GetActions();
        IPS_LogMessage($this->name, __FUNCTION__ ."(#".$this->InstanceID.") entered");
        if (count($caps) < 1) {
            IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.") Caps empty");
            return;
        }
        foreach (array_keys($this->capvars) as $cap) {
            $var = $this->capvars[$cap];
            $ident = $var["ident"];
            $id = @$this->GetIDForIdent($ident);
            if ($id > 0) {
                IPS_LogMessage($this->name, __FUNCTION__ ."(#".$this->InstanceID.") Maintain Var for Cap $cap");
                if (!isset($caps[$cap])) {
                    $this->drop_var($ident);
                } else {
                    if (isset($var["hidden"])) {
                        IPS_SetHidden($id, $var["hidden"]);
                    }//hidden
                    if (isset($actions[$cap])) {
                        $action = $actions[$cap];
                        //if (is_bool($action)) {
                        if ($action) {
                            IPS_LogMessage($this->name,__FUNCTION__."(#".$this->InstanceID.") Enable Standard Action for $cap");
                            $this->EnableAction($cap);
                        } else {
                            //IPS_LogMessage($this->name,__FUNCTION__."Disable Standard Action for $cap");
                            $this->DisableAction($cap);
                        }
                        //}
                    }//actiom
                }//caps
            } else {
                IPS_LogMessage($this->name, __FUNCTION__ . "(#".$this->InstanceID.")  Var with Ident $ident not found");
            }//id
        }//for
    }//function
	
	
	

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
		$this->RegisterPropertyString('Category', 'STECA Devices');
        $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root
        $this->RegisterPropertyInteger('ParentInstance', 0); //parent cat is root
        $this->RegisterPropertyString('LogFile', '');
        $this->RegisterPropertyBoolean('AutoCreate', true);

        $this->RegisterPropertyBoolean('Active', false);

		//VariablenProfile
        $this->check_profile('TempSolar', 1, "", " °C", "Temperature", -200, +500, 1, 0, false);
        $this->check_profile('PowSolar', 2, "", " kW", "", 0, +1000, 0.1, 0, false);
        $this->check_profile('FlowSolar', 1, "", " l/min", "", 0, +50, 1, 0, false);
        $this->check_profile('SolarWM', 1, "", " kWh", "", 0, 0, 1, 0, false);

		$this->CreateVarProfileAlarmSolar();
		$this->CreateVarProfileIntensity();

        $this->CreateStatusVars();

        //Vars
     //   $this->RegisterVariableString('Buffer', 'Buffer', "", -1);
        IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
//      $this->RegisterVariableString('LastUpdate', 'LastUpdate', "", -4);
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), true);
//        $this->RegisterVariableInteger('T1', 'T1', "TempSolar",1);
//        $this->RegisterVariableInteger('T2', 'T2', "TempSolar",2);
//        $this->RegisterVariableInteger('T3', 'T3', "TempSolar",3);
//        $this->RegisterVariableInteger('T4', 'T4', "TempSolar",4);
//        $this->RegisterVariableInteger('T5', 'T5', "TempSolar",5);
//        $this->RegisterVariableInteger('T6', 'T6', "TempSolar",6);
//        $this->RegisterVariableInteger('R1', 'R1', "Intensity.1",7);
//        $this->RegisterVariableInteger('R2', 'R2', "Intensity.1",8);
//        $this->RegisterVariableInteger('R3', 'R3', "Intensity.1",9);
//
//        $this->RegisterVariableInteger('System', 'System', "",10);
//        $this->RegisterVariableBoolean('WMZ', 'Wärmemengenzählung', "",11);
//        $this->RegisterVariableFloat('p_curr', 'Momentanleistung', "PowSolar",12);
//        $this->RegisterVariableInteger('p_comp', 'Gesamtwärmemenge', "SolarWM",13);
//        $this->RegisterVariableInteger('radiation', 'Einstrahlung', "",14);
//        $this->RegisterVariableString('Country', 'Ländercode', "",15);
//        $this->RegisterVariableString('Model', 'Modellvariante', "",16);
//        $this->RegisterVariableInteger('Tds', 'Temperatur Direktsensor', "TempSolar",17);
//        $this->RegisterVariableInteger('v_flow', 'Volumenstrom', "FlowSolar",18);
//        $this->RegisterVariableBoolean('Alarm', 'Alarm', "AlarmSolar",19);

        //Timers
       $this->RegisterTimer('ReInit', 60000, $this->module_data['prefix'] . '_ReInitEvent($_IPS[\'TARGET\']);');

        //Connect Parent
        $this->RequireParent($this->module_interfaces['Cutter']);
        $pid = $this->GetParent();
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


	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
     public function ApplyChanges()
    {
        // Diese Zeile nicht loeschen
        parent::ApplyChanges();
        if (IPS_GetKernelRunlevel() == self::KR_READY) {
            if ($this->HasActiveParent()) {
                $this->SetStatus(self::ST_AKTIV);
            }else{
                $this->SetStatus(self::ST_NOPARENT);
            } //check status
        }
        //must be here!!
        $this->SetStatusVariables(); //Update Variables
        $this->SetReceiveDataFilter(".*");
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
            $this->debug(__FUNCTION__, "Parent Instance #$parent ");    
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

   //------------------------------------------------------------------------------
    /**
     * Get status variable Buffer
     * contains incoming data from IO, act as regVar
     * @return String
     */
	
	 protected function GetLocalBuffer()
    {
        if($this->useBufferVar) {
            $vid = @$this->GetIDForIdent('Buffer');
            if (!$vid) {
                $this->RegisterVariableString('Buffer','Buffer','',-1);
                $vid=$this->GetIDForIdent('Buffer');
                IPS_SetHidden($vid, true);
            }
            $val = GetValueString($vid);
        }else{
            $val=parent::GetBuffer('LocalBuffer');

        }
        $this->debug(__FUNCTION__,'LocalBuffer returned:'.$val);
        return $val;
    }


    //------------------------------------------------------------------------------
    /**
     * Set status variable Buffer
     * @param String $val
     */

    protected function SetLocalBuffer($val)
    {
        $this->debug(__FUNCTION__, 'set LocalBuffer:' . $val);
        if($this->useBufferVar) {
            $vid = @$this->GetIDForIdent('Buffer');
            if (!$vid) {
                $this->RegisterVariableString('Buffer','Buffer','',-1);
                $vid=$this->GetIDForIdent('Buffer');
                IPS_SetHidden($vid, true);
            }
            SetValueString($vid,$val);
        }else {


            parent::SetBuffer('LocalBuffer', $val);
        }
    }

	    //------------------------------------------------------------------------------
    /**
     * Get Property Caplist
     * @return string
     */
    protected function GetCapList()
    {
        return (String)@IPS_GetProperty($this->InstanceID, 'CapList');
    }


    //------------------------------------------------------------------------------
    /**
     * returns array of defined capabilities for this device
     * Format:"cap:action;"
     * @return array
     */
    protected function GetCaps()
    {
        $result = array();
        //define vars
        $caplist = $this->GetCapList();
        $caps = explode(";", $caplist);
        //$this->debug(__FUNCTION__,"CapVars:".print_r($this->capvars,true));
        foreach ($caps as $tag) {
            if(preg_match("/^\s*$/",$tag)) continue;
            $parts=explode(":",$tag);
            $cap=$parts[0];
            if (isset($this->capvars[$cap])) {
                $ident = $this->capvars[$cap]['ident'];
                if ($ident) {
                    $result[$cap] = $ident;
                    if (isset($parts[1])) {
                        $this->actions[$ident] = $parts[1];
                    }
                    //$this->debug(__FUNCTION__, "Cap '$cap': use Var '$ident''");
                }//ident
            } else {
                $this->debug(__FUNCTION__, "Cap $cap: No Variable configured");
            }//isset cap
        }//for
        return $result;
    }//function

	
	
	    //------------------------------------------------------------------------------
    /**
     * returns array of defined Actions for this device
     * @return array
     */
    protected function GetActions()
    {
        $this->GetCaps(); //actions are assigne in caps list
        $result = $this->actions;
        return $result;
    }//function

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
       // $this->SyncParent();
        $this->SetLocalBuffer('');
        $this->SetTimerInterval('ReInit', 60000);
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
        //status check triggered by data
             //trigger status check
        if ($this->HasActiveParent()) {
            $this->SetStatus(self::ST_AKTIV);
        }else{
            $this->SetStatus(self::ST_NOPARENT);
        }

        // decode Data from Device Instanz
        if (strlen($JSONString) > 0) {
            $this->debug(__FUNCTION__, 'Daten empfangen:' . $JSONString);
            $this->debuglog($JSONString);
            // decode Data from IO Instanz
            $data = json_decode($JSONString);
            //entry for data from parent

            $buffer = $this->GetLocalBuffer();
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
                    if ($bl > 100) {
                        $buffer = substr($buffer, 100);
                        $this->debug(__CLASS__, "Buffer length exceeded, dropping...");
                    }
                    $inbuf = $this->ReadRecord($buffer); //returns remaining chars
                    $this->SetLocalBuffer($inbuf);
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
	    $steca_data['p_curr'] = '';
	    $steca_data['p_comp'] = '';
	    $steca_data['radiation'] = '';
	    $steca_data['Country'] = '';
	    $steca_data['Model'] = '';
	
	    $steca_data['TDS'] = '';
	    $steca_data['v_flow'] = '';
	    $steca_data['Alarm'] = '';
		
		
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
