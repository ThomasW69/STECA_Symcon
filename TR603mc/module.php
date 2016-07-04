<?
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
