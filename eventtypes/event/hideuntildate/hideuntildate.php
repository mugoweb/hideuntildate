<?php
//
// Definition of hideUntilDate class
//

class hideUntilDate
{
    public function __construct( $eventID, $eventVersion )
    {
        $this->WorkflowEventID = $eventID;
        $this->WorkflowEventVersion = $eventVersion;
        $this->Entries = hideUntilDateValue::fetchAllElements( $eventID, $eventVersion );
    }

    public function attributes()
    {
        return array( 'workflow_event_id',
                      'workflow_event_version',
                      'entry_list',
                      'classattribute_id_list' );
    }

    public function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    public function attribute( $attr )
    {
        switch ( $attr )
        {
            case "workflow_event_id" :
            {
                return $this->WorkflowEventID;
            }break;
            case "workflow_event_version" :
            {
                return $this->WorkflowEventVersion;
            }break;
            case "entry_list" :
            {
                return $this->Entries;
            }break;
            case 'classattribute_id_list' :
            {
                return $this->classAttributeIDList();
            }
            default :
            {
                eZDebug::writeError( "Attribute '$attr' does not exist", 'hideUntilDate::attribute' );
                return null;
            }break;
        }
    }
    public static function removeHideUntilDateEntries( $workflowEventID, $workflowEventVersion )
    {
         hideUntilDateValue::removeAllElements( $workflowEventID, $workflowEventVersion );
    }
    /*!
     Adds an enumeration
    */
    function addEntry( $contentClassAttributeID, $contentClassID = false )
    {
        if ( !isset( $contentClassAttributeID ) )
        {
            return;
        }
        if ( !$contentClassID )
        {
            $contentClassAttribute = eZContentClassAttribute::fetch( $contentClassAttributeID );
            $contentClassID = $contentClassAttribute->attribute( 'contentclass_id' );
        }
        // Checking if $contentClassAttributeID and $contentClassID already exist (in Entries)
        foreach ( $this->Entries as $entrie )
        {
            if ( $entrie->attribute( 'contentclass_attribute_id' ) == $contentClassAttributeID and
                 $entrie->attribute( 'contentclass_id' ) == $contentClassID )
                return;
        }
        $hideUntilDateValue = hideUntilDateValue::create( $this->WorkflowEventID, $this->WorkflowEventVersion, $contentClassAttributeID, $contentClassID );
        $hideUntilDateValue->store();
        $this->Entries = hideUntilDateValue::fetchAllElements( $this->WorkflowEventID, $this->WorkflowEventVersion );
    }

    function removeEntry( $workflowEventID, $id, $version )
    {
       hideUntilDateValue::removeByID( $id, $version );
       $this->Entries = hideUntilDateValue::fetchAllElements( $workflowEventID, $version );
    }

    function classAttributeIDList()
    {
        $attributeIDList = array();
        foreach ( $this->Entries as $entry )
        {
            $attributeIDList[] = $entry->attribute( 'contentclass_attribute_id' );
        }
        return $attributeIDList;
    }

    function setVersion( $version )
    {
        if ( $version == 1 && count( $this->Entries ) == 0 )
        {
            $this->Entries = hideUntilDateValue::fetchAllElements( $this->WorkflowEventID, 0 );
            foreach( $this->Entries as $entry )
            {
                $entry->setAttribute( "workflow_event_version", 1 );
                $entry->store();
            }
        }
        if ( $version == 0 )
        {
            hideUntilDateValue::removeAllElements( $this->WorkflowEventID, 0 );
            foreach( $this->Entries as $entry )
            {
                $oldversion = $entry->attribute( "workflow_event_version" );
                $id = $entry->attribute( "id" );
                $workflowEventID = $entry->attribute( "workflow_event_id" );
                $contentClassID = $entry->attribute( "contentclass_id" );
                $contentClassAttributeID = $entry->attribute( "contentclass_attribute_id" );
                $entryCopy = hideUntilDateValue::createCopy( $id,
                                                               $workflowEventID,
                                                               0,
                                                               $contentClassID,
                                                               $contentClassAttributeID );

                $entryCopy->store();
            }
        }
    }
    
    public $WorkflowEventID;
    public $WorkflowEventVersion;
    public $Entries;

}


?>
