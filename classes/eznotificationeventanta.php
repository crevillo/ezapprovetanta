<?php 
/**
 * File containing the eZNotificationEventTanta class
 * 
 * It extends provided eZNotificationEvent adding more info in each record of the table
 * This info will determine template used in each email and also decide if an email should
 * be sent to a specific user or not. For example, probably we don't need to email the approver
 * telling that he has approved something.
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 */

class eZNotificationEventTanta extends eZNotificationEvent
{
    /**
     * Create a custom notification event
     * 
     * @param string $type
     * @param array $params 
     * @return eZNotificationEvent
     */
    static function create( $type, $params = array() )
    {
        $row = array(
            "id" => null,
            'event_type_string' => $type,
            'data_int1' => 0,
            'data_int2' => isset( $params['messagetype'] ) ?  $params['messagetype'] : 0,
            'data_int3' => eZUser::currentUser()->id(), // record the user creating the notification event.
            'data_int4' => 0,
            'data_text1' => '',
            'data_text2' => isset( $params['messagetext'] ) ?  $params['messagetext'] : '',
            'data_text3' => '',
            'data_text4' => '' );
        $event = new eZNotificationEvent( $row );
        eZDebugSetting::writeDebug( 'kernel-notification', $event, "event" );
        $event->initializeEventType( $params );
        return $event;
    }
}

?>
