<?php

class eZCollaborationEventTantaType extends eZCollaborationEventType
{
    const NOTIFICATION_TYPE_STRING = 'ezcollaborationtanta';

    /*!
     Constructor
    */
    function eZCollaborationEventTantaType()
    {
        $this->eZNotificationEventType( self::NOTIFICATION_TYPE_STRING );
    }    
}

eZNotificationEventType::register( eZCollaborationEventTantaType::NOTIFICATION_TYPE_STRING, 'eZCollaborationEventTantaType' );

?>

