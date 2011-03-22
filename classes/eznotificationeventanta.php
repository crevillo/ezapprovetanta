<?php 
/**
 * File containing the eZNotificationEventTanta class. 
 *
 * @copyright <CopyrightString>
 * @license <LicenseString>
 * @version <VersionString>
 * @package <Package>
 * @subpackage <SubPackage>
 */

class eZNotificationEventTanta extends eZNotificationEvent
{
    static function create( $type, $params = array() )
    {
        $row = array(
            "id" => null,
            'event_type_string' => $type,
            'data_int1' => 0,
            'data_int2' => isset( $params['messagetype'] ) ?  $params['messagetype'] : 0,
            'data_int3' => 0,
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
