<?php
/**
 * File containing the eZApproveTantaCollaborationHandler class. 
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 */

class eZApproveTantaCollaborationHandler extends eZCollaborationItemHandler
{
    // Approval message type
    const MESSAGE_TYPE_APPROVE = 1;

    // Default status, no approval decision has been made
    const STATUS_WAITING = 0;

    // The contentobject was approved and will be published.
    const STATUS_ACCEPTED = 1;

    // The contentobject was denied and will be archived.
    const STATUS_DENIED = 2;

    // The contentobject was deferred and will be a draft again for reediting.
    const STATUS_DEFERRED = 3;

    /**
     * Initializes the handler
     * 
     */
    function eZApproveTantaCollaborationHandler()
    {
        $this->eZCollaborationItemHandler( 'ezapprovetanta',
                                           ezpI18n::tr( 'kernel/classes', 'Tanta Approval' ),
                                           array( 'use-messages' => true,
                                                  'notification-types' => true,
                                                  'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_USER ) );
    }

    /**
     * Returns title of the collaboration handler
     * 
     * @param eZCollaborationItem $collaborationItem
     * @return string
     */
    function title( $collaborationItem )
    {
        return ezpI18n::tr( 'kernel/classes', 'Approval' );
    }

    /**
     * Returns some info about the collaboration item
     * 
     * @param eZCollaborationItem $collaborationItem
     * @return array
     */
    function content( $collaborationItem )
    {
        return array( "content_object_id" => $collaborationItem->attribute( "data_int1" ),
                      "content_object_version" => $collaborationItem->attribute( "data_int2" ),
                      "approval_status" => $collaborationItem->attribute( "data_int3" ) );
    }

    function notificationParticipantTemplateType( $participantRole, $type )
    {
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
        {
            return 'approve_' . $type .'.tpl';
        }
        else if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_AUTHOR )
        {
            return 'author_' . $type .'.tpl';
        }
        else
            return false;
    }
    /**
     * Returns the content object version object for the collaboration item
     * 
     * @param eZCollaborationItem $collaborationItem
     * @return eZContentObjectVersion
     */
    static function contentObjectVersion( $collaborationItem )
    {
        $contentObjectID = $collaborationItem->contentAttribute( 'content_object_id' );
        $contentObjectVersion = $collaborationItem->contentAttribute( 'content_object_version' );
        return eZContentObjectVersion::fetchVersion( $contentObjectVersion, $contentObjectID );
    }

    /**
     * Updates the last_read for the participant link.
     * 
     * @param eZCollaborationItem $collaborationItem
     * @param bool $viewMode
     */
    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    /**
     * Returns the number of messages for the collaboration item.
     * 
     * @param eZCollaborationItem $collaborationItem
     * @return int
     */
    function messageCount( $collaborationItem )
    {
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );
    }

    /**
     * Returns the number of unread messages for the collaboration item.
     * 
     * @param eZCollaborationItem $collaborationItem
     * @return int
     */
    function unreadMessageCount( $collaborationItem )
    {
        $lastRead = 0;
        $status = $collaborationItem->attribute( 'user_status' );
        if ( $status )
            $lastRead = $status->attribute( 'last_read' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ),
                                                                      'conditions' => array( 'modified' => array( '>', $lastRead ) ) ) );
    }

    /**
     * Returns the status of the approval collaboration item
     * 
     * @param int $approvalID
     * @return bool
     */
    static function checkApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            return $collaborationItem->attribute( 'data_int3' );
        }
        return false;
    }

    /**
     * Makes sure the approval item is activated for all participants 
     * 
     * @param int $approvalID
     * @return bool
     */
    static function activateApproval( $approvalID )
    {
        $collaborationItem = eZCollaborationItem::fetch( $approvalID );
        if ( $collaborationItem !== null )
        {
            $collaborationItem->setAttribute( 'data_int3', self::STATUS_WAITING );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_ACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->store();
            $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => $approvalID ) );
            foreach( $participantList as $participantLink )
            {
                $collaborationItem->setIsActive( true, $participantLink->attribute( 'participant_id' ) );
            }
            return true;
        }
        return false;
    }

    /**
     * Creates a new approval collaboration item for the content object and the version
     * The item will be added to the author and the approver array.
     *
     * @param int $contentObjectID
     * @param int $contentObjectVersion
     * @param int $authorID 
     * @param array $approverIDArray
     * @param int $type
     * @param string message
     * @return eZCollaborationItem
     */
    static function createApproval( $contentObjectID, $contentObjectVersion, $authorID, $approverIDArray, $type = 0, $message = '' )
    {
       
        $collaborationItem = eZCollaborationItem::create( 'ezapprovetanta', $authorID );
        $collaborationItem->setAttribute( 'data_int1', $contentObjectID );
        $collaborationItem->setAttribute( 'data_int2', $contentObjectVersion );
        $collaborationItem->store();
        $collaborationID = $collaborationItem->attribute( 'id' );

        $participantList = array( array( 'id' => array( $authorID ),
                                         'role' => eZCollaborationItemParticipantLink::ROLE_AUTHOR ),
                                  array( 'id' => $approverIDArray,
                                         'role' => eZCollaborationItemParticipantLink::ROLE_APPROVER ) );
        foreach ( $participantList as $participantItem )
        {
            foreach( $participantItem['id'] as $participantID )
            {
                $participantRole = $participantItem['role'];
                $link = eZCollaborationItemParticipantLink::create( $collaborationID, $participantID,
                                                                    $participantRole, eZCollaborationItemParticipantLink::TYPE_USER );
                $link->store();

                $profile = eZCollaborationProfile::instance( $participantID );
                $groupID = $profile->attribute( 'main_group' );
                eZCollaborationItemGroupLink::addItem( $groupID, $collaborationID, $participantID );
            }
        }

        // Create the notification
        $collaborationitemtanta = new eZCollaborationItemTanta();      
        $event = $collaborationitemtanta->createNotificationEvent( $collaborationItem, false, $type, $message );
        
        return $collaborationItem;
    }

    /**
     * Adds a new comment, approves the item or denies the item.
     *
     * @param eZModule $module
     * @param eZCollaborationItem $collaborationItem
     */
    function handleCustomAction( $module, $collaborationItem )
    {
        $redirectView = 'item';
        $redirectParameters = array( 'full', $collaborationItem->attribute( 'id' ) );
        $addComment = false;

        if ( $this->isCustomAction( 'Comment' ) )
        {
            $addComment = true;
        }
        else if ( $this->isCustomAction( 'Accept' ) or
                  $this->isCustomAction( 'Deny' ) or
                  $this->isCustomAction( 'Defer' ) )
        {
            // check user's rights to approve
            $user = eZUser::currentUser();
            $userID = $user->attribute( 'contentobject_id' );
            $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );

            $approveAllowed = false;
            foreach( $participantList as $participant )
            {
                if ( $participant->ParticipantID == $userID &&
                     $participant->ParticipantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
                {
                    $approveAllowed = true;
                    break;
                }
            }
            if ( !$approveAllowed )
            {
                return $module->redirectToView( $redirectView, $redirectParameters );
            }

            $contentObjectVersion = $this->contentObjectVersion( $collaborationItem );
            $status = self::STATUS_DENIED;
            if ( $this->isCustomAction( 'Accept' ) )
                $status = self::STATUS_ACCEPTED;
//             else if ( $this->isCustomAction( 'Defer' ) )
//                 $status = self::STATUS_DEFERRED;
//             else if ( $this->isCustomAction( 'Deny' ) )
//                 $status = self::STATUS_DENIED;
            else if ( $this->isCustomAction( 'Defer' ) or
                      $this->isCustomAction( 'Deny' ) )
                $status = self::STATUS_DENIED;
            $collaborationItem->setAttribute( 'data_int3', $status );
            $collaborationItem->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
            $timestamp = time();
            $collaborationItem->setAttribute( 'modified', $timestamp );
            $collaborationItem->setIsActive( false );
            $redirectView = 'view';
            $redirectParameters = array( 'summary' );
            $addComment = true;
            $messageText = $this->customInput( 'ApproveComment' );
            $collaborationitemtanta = new eZCollaborationItemTanta();
            $msgtype = $this->isCustomAction( 'Accept' ) ? 2 : 3;
            $event = $collaborationitemtanta->createNotificationEvent( $collaborationItem, false, $msgtype, $messageText );
        }
        if ( $addComment )
        {
            $messageText = $this->customInput( 'ApproveComment' );
            if ( trim( $messageText ) != '' )
            {
                $message = eZCollaborationSimpleMessage::create( 'ezapprovetanta_comment', $messageText );
                $message->store();
                eZCollaborationItemMessageLink::addMessage( $collaborationItem, $message, self::MESSAGE_TYPE_APPROVE );
                // create notification event with type 1 (there is a comment only )
                $collaborationitemtanta = new eZCollaborationItemTanta();
                $event = $collaborationitemtanta->createNotificationEvent( $collaborationItem, false, 1, $messageText );
            }
        }
        $collaborationItem->sync();
        return $module->redirectToView( $redirectView, $redirectParameters );
    }

    /**
     * Handles the Collaboration Event. 
     * Chooses what template the use depending on the notification event type 
     * (new collaboration item, item commented, item rejected or approved) 
     *
     * In some cases, there's no need to notify the user. For example, if a user is approving something, 
     * there's no need to notify it by email. 
     * @todo make this customizable
     *
     * We'll have following options
     *  Type = 0, meaning a new collaboration item is created, well work just default eZ Publish 
     *            collaboration does. will use same templates also. 
     *
     *  Type = 1, 2, 3, meaning a collaboration item is commented (1), approved (2), rejected (3): 
     *                  - An email will be sent to the creator of the item
     *                  - Another one will one for all the participants (approvers) except the one commenting 
     *                
     *
     * @param eZNotificationEvent $event
     * @param eZCollaborationItem $item
     * @param array $parameters
     */
    static function handleCollaborationEvent( $event, $item, &$parameters )
    {
        
        $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => $item->attribute( 'id' ),
                                                                                            'participant_type' => eZCollaborationItemParticipantLink::TYPE_USER,
                                                                                            'as_object' => false ) );
        $userIDList = array();
        $participantMap = array();

        foreach ( $participantList as $participant )
        {
            $userIDList[] = $participant['participant_id'];
            $participantMap[$participant['participant_id']] = $participant;
        }

        $collaborationIdentifier = $event->attribute( 'data_text1' );
        $ruleList = eZCollaborationNotificationRule::fetchItemTypeList( $collaborationIdentifier, $userIDList, false );
        $userIDList = array();
        foreach ( $ruleList as $rule )
        {
            $userIDList[] = $rule['user_id'];
        }
        $userList = array();
        if ( count( $userIDList ) > 0 )
        {
            $db = eZDB::instance();
            $userIDListText = $db->generateSQLINStatement( $userIDList, 'contentobject_id', false, false, 'int' );
            $userList = $db->arrayQuery( "SELECT contentobject_id, email FROM ezuser WHERE $userIDListText" );
        }
        else
            return eZNotificationEventHandler::EVENT_SKIPPED;

        $itemHandler = $item->attribute( 'handler' );
        $collectionHandling = $itemHandler->notificationCollectionHandling();

        $db = eZDB::instance();
        $db->begin();
        if ( $collectionHandling == self::NOTIFICATION_COLLECTION_ONE_FOR_ALL )
        {
            // @TODO
        }
        else if ( $collectionHandling == self::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE )
        {
            // @TODO
        }
        else if ( $collectionHandling == self::NOTIFICATION_COLLECTION_PER_USER )
        {

            $userCollection = array();

            foreach( $userList as $subscriber )
            {
                $contentObjectID = $subscriber['contentobject_id'];
                $participant = $participantMap[$contentObjectID];
                $participantRole = $participant['participant_role'];
                $userItem = array( 'participant' => $participant,
                                   'email' => $subscriber['email'] );
                if ( !isset( $userCollection[$participantRole] ) )
                    $userCollection[$participantRole] = array();
                $userCollection[$participantRole][] = $userItem;
            }

            // get notification event type 
            $notificationType = $event->attribute( 'data_int2' );

            // get creator of the notification event
            $notificationCreator = $event->attribute( 'data_int3' );

            // get message text
            $notificationMessage = $event->attribute( 'data_text2' );

            $tpl = eZTemplate::factory();
            $tpl->resetVariables();

            if ( $creator = eZUser::fetch( $notificationCreator ) )
            {
				$tpl->setVariable( 'notification_creator', $creator );		
				$creatorID = $creator->attribute( 'contentobject_id' );
				if ( $creatorObject = eZContentObject::fetch( $creatorID ) )
					$tpl->setVariable( 'notification_creator_object', $creatorObject );
            }
            
            
            switch ( $notificationType )
            {
                case 0:
                {
                    // work just like the default one, except for the template location. 
                    foreach( $userCollection as $participantRole => $collectionItems )
                    {
                        $templateName = $itemHandler->notificationParticipantTemplateType( $participantRole, $notificationType  );
                        if ( !$templateName )
                            $templateName = eZCollaborationItemHandler::notificationParticipantTemplateType( $participantRole, $notificationType );
                            
                        $itemInfo = $itemHandler->attribute( 'info' );
                        $typeIdentifier = $itemInfo['type-identifier'];
                        $tpl->setVariable( 'collaboration_item', $item );
                        $tpl->setVariable( 'collaboration_participant_role', $participantRole );
                        
                       
                        $result = $tpl->fetch( 'design:notification/handler/ezcollaborationtanta/view/' . $typeIdentifier . '/' . $templateName );
                        $subject = $tpl->variable( 'subject' );
                        if ( $tpl->hasVariable( 'message_id' ) )
                            $parameters['message_id'] = $tpl->variable( 'message_id' );
                        if ( $tpl->hasVariable( 'references' ) )
                            $parameters['references'] = $tpl->variable( 'references' );
                        if ( $tpl->hasVariable( 'reply_to' ) )
                            $parameters['reply_to'] = $tpl->variable( 'reply_to' );
                        if ( $tpl->hasVariable( 'from' ) )
                            $parameters['from'] = $tpl->variable( 'from' );
                        if ( $tpl->hasVariable( 'content_type' ) )
                            $parameters['content_type'] = $tpl->variable( 'content_type' );

                        $collection = eZNotificationCollection::create( $event->attribute( 'id' ), eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID, eZCollaborationNotificationHandler::TRANSPORT );

                        $collection->setAttribute( 'data_subject', $subject );
                        $collection->setAttribute( 'data_text', $result );
                        $collection->store();
                        foreach ( $collectionItems as $collectionItem )
                        {
                            $collection->addItem( $collectionItem['email'] );
                        }
                    }
                }break;

                case 1:
                case 2:
                case 3:
                {
                    foreach( $userCollection as $participantRole => $collectionItems )
                    {
                        foreach ( $collectionItems as $collectionItem )
                        {
                            if( $collectionItem['participant']['participant_id'] != $notificationCreator )
                            {
                                $templateName = $itemHandler->notificationParticipantTemplateType( $participantRole, $notificationType );
                                if ( !$templateName )
                                    $templateName = self::notificationParticipantTemplateForEvent( $participantRole, $notificationType );
                                $itemInfo = $itemHandler->attribute( 'info' );
                                $typeIdentifier = $itemInfo['type-identifier'];
                                $tpl->setVariable( 'collaboration_item', $item );
                                $tpl->setVariable( 'collaboration_participant_role', $participantRole );
                                $tpl->setVariable( 'message', $notificationMessage );
                                $result = $tpl->fetch( 'design:notification/handler/ezcollaborationtanta/view/' . $typeIdentifier . '/' . $templateName );
                               
                                $subject = $tpl->variable( 'subject' );
                                if ( $tpl->hasVariable( 'message_id' ) )
                                    $parameters['message_id'] = $tpl->variable( 'message_id' );
                                if ( $tpl->hasVariable( 'references' ) )
                                    $parameters['references'] = $tpl->variable( 'references' );
                                if ( $tpl->hasVariable( 'reply_to' ) )
                                    $parameters['reply_to'] = $tpl->variable( 'reply_to' );
                                if ( $tpl->hasVariable( 'from' ) )
                                    $parameters['from'] = $tpl->variable( 'from' );
                                if ( $tpl->hasVariable( 'content_type' ) )
                                    $parameters['content_type'] = $tpl->variable( 'content_type' );

                                $collection = eZNotificationCollection::create( $event->attribute( 'id' ), eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID, eZCollaborationNotificationHandler::TRANSPORT );

                                $collection->setAttribute( 'data_subject', $subject );
                                $collection->setAttribute( 'data_text', $result );
                                $collection->store();

                                $collection->addItem( $collectionItem['email'] );
                            }
                        }
                    }
                   
                }break;
            }
        }
        else
        {
            eZDebug::writeError( "Unknown collaboration notification collection handling type '$collectionHandling', skipping notification", __METHOD__ );
        }
        $db->commit();

        return eZNotificationEventHandler::EVENT_HANDLED;
    }

}

?>
