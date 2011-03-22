<?php

class eZCollaborationNotificationTantaHandler extends eZCollaborationNotificationHandler
{
    const NOTIFICATION_HANDLER_ID = 'ezcollaborationtanta';
    const TRANSPORT = 'ezmail';

    /*!
     Constructor
    */
    function eZCollaborationNotificationTantaHandler()
    {
        $this->eZNotificationEventHandler( self::NOTIFICATION_HANDLER_ID, "Tanta Collaboration Handler" );
    }    

    function handle( $event )
    {       
        eZDebugSetting::writeDebug( 'kernel-notification', $event, "trying to handle event" );
        if ( $event->attribute( 'event_type_string' ) == self::NOTIFICATION_HANDLER_ID )
        {
            $parameters = array();
            $status = $this->handleCollaborationEvent( $event, $parameters );
          
            if ( $status == eZNotificationEventHandler::EVENT_HANDLED )
                $this->sendMessage( $event, $parameters );
            else
                return false;
        }
        return true;
    }

    function handleCollaborationEvent( $event, &$parameters )
    {
        $collaborationItem = $event->attribute( 'content' );
        if ( !$collaborationItem )
            return eZNotificationEventHandler::EVENT_SKIPPED;
        $collaborationHandler = $collaborationItem->attribute( 'handler' );
        return $collaborationHandler->handleCollaborationEvent( $event, $collaborationItem, $parameters );
    }   
}

?>

