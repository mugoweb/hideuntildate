<?php
//
// Definition of hideUntilDateType class
//

class hideUntilDateType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = "hideuntildate";

    /*!
     Constructor
    */
    function __construct()
    {
        include_once( 'kernel/common/i18n.php' );
        $this->eZWorkflowEventType( hideUntilDateType::WORKFLOW_TYPE_STRING, ezi18n( 'kernel/workflow/event', "Hide until date" ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }

    function execute( $process, $event )
    {
        // Get some information about the object being passed
        $parameters = $process->attribute( 'parameter_list' );
        $object = eZContentObject::fetch( $parameters['object_id'] );

        // Because this is also run by the cronjob, check to make sure the object hasn't been deleted
        if( !$object )
        {
            eZDebugSetting::writeError( 'workflow-hideuntildate','The object with ID '.$parameters['object_id'].' does not exist.', 'eZApproveType::execute() object is unavailable' );
            return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }
        
        // if the current version of an object is not the version the workflow pertains to, cancel the workflow
        $currentVersion = $object->attribute( 'current_version' );
        $version = $parameters['version'];
        if( $currentVersion != $version )
        {
            return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
        }
        
        // Get the data map for this object
        $objectAttributes = $object->attribute( 'data_map' );
        // Get the user configuration with class and attribute mapping and the boolean modify publish date setting
        $workflowSettings = $this->getWorkflowSettings( $event );
        
        foreach( $objectAttributes as $objectAttribute )
        {
            if( in_array( $objectAttribute->attribute( 'contentclassattribute_id' ), $workflowSettings['classattribute_map'] ) )
            {
                // Make sure this is of a date or datetime attribute
                if( in_array( $objectAttribute->attribute( 'data_type_string' ), array( 'ezdate', 'ezdatetime' ) ) )
                {
                    // If the publish date is in the future, hide the node
                    if( time() < $objectAttribute->toString() )
                    {
                        // Set a time for when this workflow should be tested again via the cronjob
                        // Store a description to be displayed in the Setup > Workflow processes list
                        // This must also be accompanied by overriding the workflow/processlist.tpl template
                        $parameters = array_merge( $parameters, array( 'event_description' => 'Publishing of object delayed until ' . $objectAttribute->attribute( 'content' )->toString( true ) ) );
                        $process->setParameters( $parameters );
                        $process->store();
                        
                        // Hide the object's nodes
                        $nodes = $object->attribute( 'assigned_nodes' );
                        foreach( $nodes as $node )
                        {
                            if( !$node->attribute( 'is_hidden' ) )
                            {
                                eZContentObjectTreeNode::hideSubTree( $node );
                            }
                        }
                        return eZWorkflowType::STATUS_DEFERRED_TO_CRON_REPEAT;
                    }
                    // The publish date is in the past, so unhide the object and publish it
                    elseif( $objectAttribute->hasContent() )
                    {
                        if( $workflowSettings['modify_publish_date'] )
                        {
                            $object->setAttribute( 'published', $objectAttribute->toString() );
                            $object->store();
                        }
                        $nodes = $object->attribute( 'assigned_nodes' );
                        foreach( $nodes as $node )
                        {
                            eZContentObjectTreeNode::unhideSubTree( $node );
                            eZContentCacheManager::clearContentCache( $parameters['object_id'] );
                            eZContentCacheManager::clearObjectViewCache( $parameters['object_id'] );
                        }
                        return eZWorkflowType::STATUS_ACCEPTED;
                    }
                }
                else
                {
                    // Attribute that matched was not a valid date or datetime attribute, so ignore
                    return eZWorkflowType::STATUS_ACCEPTED;
                }
            }
        }
        // No attributes matched the workflow configured by the user
        return eZWorkflowType::STATUS_ACCEPTED;
    }
    
    function getWorkflowSettings( $event )
    {
        $workflowSettings = array();
        $workflowSettings['classattribute_map'] = $this->workflowEventContent( $event )->attribute( 'classattribute_id_list' );
        $workflowSettings['modify_publish_date'] = $event->attribute( 'data_int1' );
        return $workflowSettings;
    }

    function attributes()
    {
        return array_merge( array( 'contentclass_list',
                                   'contentclassattribute_list',
                                   'has_class_attributes' ),
                            eZWorkflowEventType::attributes() );
    }

    function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    function attribute( $attr )
    {
        switch( $attr )
        {
            case 'contentclass_list' :
            {
                return eZContentClass::fetchList( eZContentClass::VERSION_STATUS_DEFINED, true );
            }break;
            case 'contentclassattribute_list' :
            {
//                $postvarname = 'WorkflowEvent' . '_event_hideuntildate_' .'class_' . $workflowEvent->attribute( 'id' ); and $http->hasPostVariable( $postvarname )
                if ( isset ( $GLOBALS['hideUntilDateSelectedClass'] ) )
                {
                    $classID = $GLOBALS['hideUntilDateSelectedClass'];
                }
                else
                {
                    // if nothing was preselected, we will use the first one:
                    // POSSIBLE ENHANCEMENT: in the common case, the contentclass_list fetch will be called twice
                    $classList = hideUntilDateType::attribute( 'contentclass_list' );
                    if ( isset( $classList[0] ) )
                        $classID = $classList[0]->attribute( 'id' );
                    else
                        $classID = false;
                }
                if ( $classID )
                {
                   return eZContentClassAttribute::fetchListByClassID( $classID );
                }
                return array();
            }break;
            case 'has_class_attributes' :
            {
                // for the backward compatibility:
                return 1;
            }break;
            default:
                return eZWorkflowEventType::attribute( $attr );
        }
    }

    function customWorkflowEventHTTPAction( $http, $action, $workflowEvent )
    {
        $id = $workflowEvent->attribute( "id" );
        switch ( $action )
        {
            case "new_classelement" :
            {
                $hideUntilDate = $workflowEvent->content( );

                $classIDList = $http->postVariable( 'WorkflowEvent' . '_event_hideuntildate_' . 'class_' . $workflowEvent->attribute( 'id' )  );
                $classAttributeIDList = $http->postVariable( 'WorkflowEvent' . '_event_hideuntildate_' . 'classattribute_' . $workflowEvent->attribute( 'id' )  );

                $hideUntilDate->addEntry(  $classAttributeIDList[0], $classIDList[0] );
                $workflowEvent->setContent( $hideUntilDate );
            }break;
            case "remove_selected" :
            {
                $version = $workflowEvent->attribute( "version" );
                $postvarname = "WorkflowEvent" . "_data_hideuntildate_remove_" . $workflowEvent->attribute( "id" );
                $arrayRemove = $http->postVariable( $postvarname );
                $hideUntilDate = $workflowEvent->content( );

                foreach( $arrayRemove as $entryID )
                {
                    $hideUntilDate->removeEntry( $id, $entryID, $version );
                }
            }break;
            case "load_class_attribute_list" :
            {
                $postvarname = 'WorkflowEvent' . '_event_hideuntildate_' .'class_' . $workflowEvent->attribute( 'id' );

                if ( $http->hasPostVariable( $postvarname ) )
                {
                    $classIDList = $http->postVariable( 'WorkflowEvent' . '_event_hideuntildate_' .'class_' . $workflowEvent->attribute( 'id' ) );
                    $GLOBALS['hideUntilDateSelectedClass'] = $classIDList[0];
                }
                else
                {
                    eZDebug::writeError( "no class selected" );
                }
            }break;
            default :
            {
                eZDebug::writeError( "Unknown custom HTTP action: " . $action, "eZEnumType" );
            }break;
        }

    }

    function fetchHTTPInput( $http, $base, $event )
    {
        $modifyDateVariable = $base . "_data_hideuntildate_modifydate_" . $event->attribute( "id" );
        if ( $http->hasPostVariable( $modifyDateVariable ) )
        {
            $modifyDateValue = (int)$http->postVariable( $modifyDateVariable );
            $event->setAttribute( 'data_int1', $modifyDateValue );
        }
    }

    function workflowEventContent( $event )
    {
        $id = $event->attribute( "id" );
        $version = $event->attribute( "version" );
        return new hideUntilDate( $id, $version );
    }

    function storeEventData( $event, $version )
    {
        $event->content()->setVersion( $version );
    }

    function storeDefinedEventData( $event )
    {
        $id = $event->attribute( 'id' );
        $version = 1;
        $hideUntilDateVersion1 = new hideUntilDate( $id, $version );
        $hideUntilDateVersion1->setVersion( 0 ); //strange name but we are creating version 0 here
        hideUntilDate::removeHideUntilDateEntries( $id, 1 );
    }
}

eZWorkflowEventType::registerEventType( hideUntilDateType::WORKFLOW_TYPE_STRING, "hideUntilDateType" );


?>
