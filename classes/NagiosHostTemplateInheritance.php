<?php



/**
 * Skeleton subclass for representing a row from the 'nagios_host_template_inheritance' table.
 *
 * Nagios Host Template Inheritance
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.
 */
class NagiosHostTemplateInheritance extends BaseNagiosHostTemplateInheritance {
	
	/**
	 * Initializes internal state of NagiosHostTemplateInheritance object.
	 * @see        parent::__construct()
	 */
	public function __construct()
	{
		// Make sure that parent constructor is always invoked, since that
		// is where any default values for this object are set.
		parent::__construct();
	}
	
	/**
	 * Checks to determine if inheritance creates a circular chain.  This is 
	 * done by recursively going through inheritance trees and seeing if the 
	 * source template is already found.  If so, this would create a circular 
	 * inheritance loop and destroy our world as we know it.
	 *
	 *@param int $targetTemplateId what template Id are we looking at
	 *@param int $originalSourceTemplateId what template Id are we looking for
	 */
	static function isCircular($targetTemplateId, $originalSourceTemplateId) {
		if($targetTemplateId == $originalSourceTemplateId)
			return true;
		else {
			// Get all the potential inheritance in which the target template id 
			// is the source
			$c = new Criteria();
			$c->add(NagiosHostTemplateInheritancePeer::SOURCE_TEMPLATE, $targetTemplateId);
			$inheritances = NagiosHostTemplateInheritancePeer::doSelect($c);
			foreach($inheritances as $inheritance) {
				if(NagiosHostTemplateInheritance::isCircular($inheritance->getTargetTemplate(), $originalSourceTemplateId))
				   return true;
			}
		}
		return false;
	}

    public function delete(PropelPDO $con = null) {

        $JobExport=new EoN_Job_Exporter();
		if($con == null || $con == ""){
			if($this->getSourceHost() == null) {
				$tmpTemplate = NagiosHostTemplatePeer::retrieveByPK($this->getSourceTemplate());
				$JobExport->insertAction($tmpTemplate->getName(),"hosttemplate","modify");
				$tmpHost = NagiosHostTemplatePeer::retrieveByPK($this->getTargetTemplate());
				if($tmpHost->getNagiosServices() !== null) {
					foreach($tmpHost->getNagiosServices() as $tmpService) {
						$JobExport->insertAction($tmpService->getDescription(),"service","delete",$tmpTemplate->getName(),"hosttemplate");	
					}
				}
			} else {
				$tmpHost = NagiosHostPeer::retrieveByPK($this->getSourceHost());
				$JobExport->insertAction($tmpHost->getName(),"host","modify");
				$tmpTemplate = NagiosHostTemplatePeer::retrieveByPK($this->getTargetTemplate());
				if($tmpTemplate->getNagiosServices() !== null) {
					foreach($tmpTemplate->getNagiosServices() as $tmpService) {
						$tmpHost = NagiosHostPeer::retrieveByPK($this->getSourceHost());
						$JobExport->insertAction($tmpService->getDescription(),"service","delete",$tmpHost->getName(),"host");	
					}
				}				
			}
		}

        parent::delete($con);

        // Check our service dependencies
        $targetTemplate = $this->getNagiosHostTemplateRelatedByTargetTemplate();
        $targetTemplate->integrityCheck(); 
    }
	
	public function save(PropelPDO $con = null) {
		if(NagiosHostTemplateInheritance::isCircular($this->getTargetTemplate(), $this->getSourceTemplate())) {
			throw new Exception("Adding that inheritance would create a circular chain.");
		}
		else {
			$JobExport=new EoN_Job_Exporter();
			if($con == null || $con == ""){
				if($this->getSourceHost() == null) {
					$tmpTemplate = NagiosHostTemplatePeer::retrieveByPK($this->getSourceTemplate());
					$JobExport->insertAction($tmpTemplate->getName(),"hosttemplate","modify");
					$tmpHost = NagiosHostTemplatePeer::retrieveByPK($this->getTargetTemplate());
					if($tmpHost->getNagiosServices() !== null) {
						foreach($tmpHost->getNagiosServices() as $tmpService) {
							$JobExport->insertAction($tmpService->getDescription(),"service","add",$tmpTemplate->getName(),"hosttemplate");	
						}
					}
				} else {
					$tmpHost = NagiosHostPeer::retrieveByPK($this->getSourceHost());
					$JobExport->insertAction($tmpHost->getName(),"host","modify");
					$tmpTemplate = NagiosHostTemplatePeer::retrieveByPK($this->getTargetTemplate());
					if($tmpTemplate->getNagiosServices() !== null) {
						foreach($tmpTemplate->getNagiosServices() as $tmpService) {
							$tmpHost = NagiosHostPeer::retrieveByPK($this->getSourceHost());
							$JobExport->insertAction($tmpService->getDescription(),"service","add",$tmpHost->getName(),"host");	
						}
					}				
				}
			}
			return parent::save($con);	// Okay, we've saved
		}
	}

} // NagiosHostTemplateInheritance
