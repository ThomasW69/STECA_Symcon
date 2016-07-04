<?

//Steca Solarregler Modul
// Thomas Westerhoff


//Klassendefinition
class STECA extends IPSModule
{

    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        parent::__construct($InstanceID);

    }

    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
		
        $this->RegisterPropertyString('Category', 'STECA Devices');
        $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root
        $this->RegisterPropertyString('LogFile', '');
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyBoolean('Active', false);

        //Vars
        $this->RegisterVariableString('Buffer', 'Buffer', "", -1);
        IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
        $this->RegisterVariableString('LastUpdate', 'Last Update', "", -4);
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), true);


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
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
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
?>
