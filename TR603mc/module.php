<?

class STECA extends IPSModule
{

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
        parent::__construct($InstanceID);

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
