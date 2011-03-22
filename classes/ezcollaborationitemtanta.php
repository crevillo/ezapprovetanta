<?php 
class eZCollaborationItemTanta
{
    function createNotificationEvent( $collaborationItem, $subType = false, $messagetype = 0, $messagetext = '' )
    {
        $handler = $collaborationItem->attribute( 'handler' );
        $info = $handler->attribute( 'info' );
        $type = $info['type-identifier'];
        if ( $subType )
            $type .= '_' . $subType;
        $event = eZNotificationEventTanta::create( 'ezcollaborationtanta', array( 'collaboration_id' => $collaborationItem->attribute( 'id' ),
                                                                             'collaboration_identifier' => $type,
                                                                             'messagetype' => $messagetype,
                                                                             'messagetext' => $messagetext ) );
        $event->store();
        return $event;
    }
}
?>
