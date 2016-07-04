<?
/**
 * @file
 *
 * STECA Gateway IPSymcon PHP Splitter Module Class
 *
 * @author Thomas Westerhoff
 * @copyright Thomas Westerhoff 2009-2016
 * @version 1.0
 * @date 2016-05-04
 */


//include_once(__DIR__ . "/../module_helper.php");

/** @class STECA
 *
 * STECA Gateway IPSymcon PHP Splitter Module Class
 *
 *
 */
class STECA extends IPSModule
{
    //------------------------------------------------------------------------------
    //module const and vars
    //------------------------------------------------------------------------------
    /**
     * Timer constant
     * maxage of LastUpdate in sec before ReInit
     */
    const MAXAGE = 300;
    /**
     * How many sensors are attached
     * 8 external Temp/Hum Sensors(0-7), Kombisensor(8) and indoor Sensors(9)
     */
    const MAXSENSORS = 1; //no indoor record

    /**
     * Fieldlist for Logging
     */
    const fieldlist = "Time;Typ;Id;Name;Temp;Hum;Bat;Lost;Wind;Rain;IsRaining;RainCounter;Pressure;willi;";

    //--------------------------------------------------------
    // main module functions
    //--------------------------------------------------------
    /**
     * Constructor.
     * @param $InstanceID
     */
    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        $json = __DIR__ . "/module.json";
        parent::__construct($InstanceID, $json);

    }

    //--------------------------------------------------------
    /**
     * overload internal IPS_Create($id) function
     */
    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
        //Hint: $this->debug will not work in this stage! must use IPS_LogMessage
        //props
        $this->RegisterPropertyString('Category', 'STECA Devices');
        $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root

        $this->RegisterPropertyString('LogFile', '');
        //$this->RegisterPropertyInteger('RainPerCount', 295);
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyBoolean('Active', false);

        //Vars
        $this->RegisterVariableString('Buffer', 'Buffer', "", -1);
        IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
        $this->RegisterVariableString('LastUpdate', 'Last Update', "", -4);
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), true);


        //Timers
        $this->RegisterTimer('ReInit', 60000, $this->module_data["prefix"] . '_ReInitEvent($_IPS[\'TARGET\']);');


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
    //--------------------------------------------------------
    /**
     * Destructor
     */
    public function Destroy()
    {
        parent::Destroy();
    }
    //function
}//class
