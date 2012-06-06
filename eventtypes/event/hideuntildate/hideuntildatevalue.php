<?php
//
// Definition of hideUntilDateValue class
//

class hideUntilDateValue extends eZPersistentObject
{
    /*!
     Constructor
    */
    public function __construct ( $row )
    {
        $this->eZPersistentObject( $row );
        $this->ClassName = null;
        $this->ClassAttributeName = null;

    }

    static function definition()
    {
        return array( "fields" => array( "id" => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "workflow_event_id" => array( 'name' => "WorkflowEventID",
                                                                       'datatype' => 'integer',
                                                                       'default' => 0,
                                                                       'required' => true,
                                                                       'foreign_class' => 'eZWorkflowEvent',
                                                                       'foreign_attribute' => 'id',
                                                                       'multiplicity' => '1..*' ),
                                         "workflow_event_version" => array( 'name' => "WorkflowEventVersion",
                                                                            'datatype' => 'integer',
                                                                            'default' => 0,
                                                                            'required' => true ),
                                         "contentclass_id" => array( 'name' => "ContentClassID",
                                                                     'datatype' => 'integer',
                                                                     'default' => 0,
                                                                     'required' => true,
                                                                     'foreign_class' => 'eZContentClass',
                                                                     'foreign_attribute' => 'id',
                                                                     'multiplicity' => '1..*' ),
                                         "contentclass_attribute_id" => array( 'name' => "ContentClassAttributeID",
                                                                               'datatype' => 'integer',
                                                                               'default' => 0,
                                                                               'required' => true,
                                                                               'foreign_class' => 'eZContentClassAttribute',
                                                                               'foreign_attribute' => 'id',
                                                                               'multiplicity' => '1..*' ) ),
                      "keys" => array( "id", "workflow_event_id", "workflow_event_version" ),
                      "function_attributes" => array( "class_name" => "className",
                                                      "classattribute_name" => "classAttributeName" ),
                      "increment_key" => "id",
                      "sort" => array( "id" => "asc" ),
                      "class_name" => "hideUntilDateValue",
                      "name" => "ezwaituntildatevalue" );
    }

    function className()
    {
        if ( $this->ClassName === null )
        {
            $contentClass = eZContentClass::fetch( $this->attribute( 'contentclass_id' ) );
            $this->ClassName = $contentClass->attribute( 'name' );
        }
        return $this->ClassName;
    }

    function classAttributeName()
    {
        if ( $this->ClassAttributeName === null )
        {
            $contentClassAttribute = eZContentClassAttribute::fetch( $this->attribute( 'contentclass_attribute_id' ) );
            $this->ClassAttributeName = $contentClassAttribute->attribute( 'name' );
        }
        return $this->ClassAttributeName;
    }

    function __clone()
    {
        unset( $this->ClassName );
        unset( $this->ClassAttributeName );
    }

    static function create( $workflowEventID, $workflowEventVersion, $contentClassAttributeID, $contentClassID )
    {
        $row = array( "id" => null,
                      "workflow_event_id" => $workflowEventID,
                      "workflow_event_version" => $workflowEventVersion,
                      "contentclass_id" => $contentClassID,
                      "contentclass_attribute_id" => $contentClassAttributeID
                      );
        return new hideUntilDateValue( $row );
    }

    static function createCopy( $id, $workflowEventID, $workflowEventVersion,  $contentClassID , $contentClassAttributeID )
    {
        $row = array( "id" => $id,
                      "workflow_event_id" => $workflowEventID,
                      "workflow_event_version" => $workflowEventVersion,
                      "contentclass_id" => $contentClassID,
                      "contentclass_attribute_id" => $contentClassAttributeID );
        return new hideUntilDateValue( $row );
    }


    static function removeAllElements( $workflowEventID, $version )
    {
        eZPersistentObject::removeObject( hideUntilDateValue::definition(),
                                          array( "workflow_event_id" => $workflowEventID,
                                                 "workflow_event_version" => $version) );
    }

    static function removeByID( $id , $version )
    {
        eZPersistentObject::removeObject( hideUntilDateValue::definition(),
                                          array( "id" => $id,
                                                 "workflow_event_version" => $version) );
    }

    function fetch( $id, $version, $asObject = true )
    {
        return eZPersistentObject::fetchObject( hideUntilDateValue::definition(),
                                                null,
                                                array( "id" => $id,
                                                       "workflow_event_version" => $version),
                                                $asObject );
    }

    static function fetchAllElements( $workflowEventID, $version, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( hideUntilDateValue::definition(),
                                                    null,
                                                    array( "workflow_event_id" => $workflowEventID,
                                                           "workflow_event_version" => $version ),
                                                    null,
                                                    null,
                                                    $asObject );
    }

    public $ClassName;
    public $ClassAttributeName;
}

?>
