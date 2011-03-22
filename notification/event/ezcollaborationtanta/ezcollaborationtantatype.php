<?php
/**
 * File containing the eZCollaborationEventTantaType class. 
 *
 * @copyright <CopyrightString>
 * @license <LicenseString>
 * @version <VersionString>
 * @package <Package>
 * @subpackage <SubPackage>
 */

class eZCollaborationEventTantaType extends eZCollaborationEventType
{
    const NOTIFICATION_TYPE_STRING = 'ezcollaborationtanta';

    function __construct()
    {
        $this->eZNotificationEventType( self::NOTIFICATION_TYPE_STRING );
    }
}

eZNotificationEventType::register( eZCollaborationEventTantaType::NOTIFICATION_TYPE_STRING, 'eZCollaborationEventTantaType' );

?>

