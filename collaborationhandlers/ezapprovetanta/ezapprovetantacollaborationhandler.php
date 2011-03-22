<?php
//
// Definition of eZApproveCollaborationHandler class
//
// Created on: <23-Jan-2003 11:57:11 amos>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.1.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2011 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file
*/

/*!
  \class eZApproveCollaborationHandler ezapprovetantacollaborationhandler.php
  \brief Handles approval communication using the collaboration system

  The handler uses the fields data_int1, data_int2 and data_int3 to store
  information on the contentobject and the approval status.

  - data_int1 - The content object ID
  - data_int2 - The content object version
  - data_int3 - The status of the approval, see defines.

*/

class eZApproveTantaCollaborationHandler extends eZCollaborationItemHandler
{
    /// Approval message type
    const MESSAGE_TYPE_APPROVE = 1;

    /// Default status, no approval decision has been made
    const STATUS_WAITING = 0;

    /// The contentobject was approved and will be published.
    const STATUS_ACCEPTED = 1;

    /// The contentobject was denied and will be archived.
    const STATUS_DENIED = 2;

    /// The contentobject was deferred and will be a draft again for reediting.
    const STATUS_DEFERRED = 3;

    /*!
     Initializes the handler
    */
    function eZApproveTantaCollaborationHandler()
    {
        $this->eZCollaborationItemHandler( 'ezapprovetanta',
                                           ezpI18n::tr( 'kernel/classes', 'Tanta Approval' ),
                                           array( 'use-messages' => true,
                                                  'notification-types' => true,
                                                  'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE ) );
    }

    function title( $collaborationItem )
    {
        return ezpI18n::tr( 'kernel/classes', 'Approval' );
    }

    function content( $collaborationItem )
    {
        return array( "content_object_id" => $collaborationItem->attribute( "data_int1" ),
                      "content_object_version" => $collaborationItem->attribute( "data_int2" ),
                      "approval_status" => $collaborationItem->attribute( "data_int3" ) );
    }

    function notificationParticipantTemplate( $participantRole )
    {
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
        {
            return 'approve.tpl';
        }
        else if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_AUTHOR )
        {
            return 'author.tpl';
        }
        else
            return false;
    }

    /*!
     \return the content object version object for the collaboration item \a $collaborationItem
    */
    static function contentObjectVersion( $collaborationItem )
    {
        $contentObjectID = $collaborationItem->contentAttribute( 'content_object_id' );
        $contentObjectVersion = $collaborationItem->contentAttribute( 'content_object_version' );
        return eZContentObjectVersion::fetchVersion( $contentObjectVersion, $contentObjectID );
    }

    /*!
     Updates the last_read for the participant link.
    */
    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    /*!
     \return the number of messages for the approve item.
    */
    function messageCount( $collaborationItem )
    {
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );
    }

    /*!
     \return the number of unread messages for the approve item.
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

    /*!
     \static
     \return the status of the approval collaboration item \a $approvalID.
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

    /*!
     \static
     \return makes sure the approval item is activated for all participants \a $approvalID.
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

    /*!
     Adds a new comment, approves the item or denies the item.
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

    static function handleCollaborationEvent( $event, $item, &$parameters )
    {
        
        $participantList = eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => $item->attribute( 'id' ),
                                                                                             'participant_type' => eZCollaborationItemParticipantLink::TYPE_USER,
                                                                                             'as_object' => false ) );
        print_r( $participantList );
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
            $tpl = eZTemplate::factory();
            $tpl->resetVariables();
            $tpl->setVariable( 'collaboration_item', $item );
            $result = $tpl->fetch( 'design:notification/handler/ezcollaboration/view/plain.tpl' );
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

            $collection = eZNotificationCollection::create( $event->attribute( 'id' ),
                                                            eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID,
                                                            eZCollaborationNotificationHandler::TRANSPORT );

            $collection->setAttribute( 'data_subject', $subject );
            $collection->setAttribute( 'data_text', $result );
            $collection->store();
            
            foreach( $userList as $subscriber )
            {
                $collection->addItem( $subscriber['email'] );
            }
        }
        else if ( $collectionHandling == self::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE )
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

            $tpl = eZTemplate::factory();
            $tpl->resetVariables();
            foreach( $userCollection as $participantRole => $collectionItems )
            {
                $templateName = $itemHandler->notificationParticipantTemplate( $participantRole );
                if ( !$templateName )
                    $templateName = eZCollaborationItemHandler::notificationParticipantTemplate( $participantRole );

                $itemInfo = $itemHandler->attribute( 'info' );
                $typeIdentifier = $itemInfo['type-identifier'];
                $tpl->setVariable( 'collaboration_item', $item );
                $tpl->setVariable( 'collaboration_participant_role', $participantRole );
                $result = $tpl->fetch( 'design:notification/handler/ezcollaborationtanta/view/' . $typeIdentifier . '/' . $templateName . '_' );
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

                $collection = eZNotificationCollection::create( $event->attribute( 'id' ),
                                                                eZCollaborationNotificationHandler::NOTIFICATION_HANDLER_ID,
                                                                eZCollaborationNotificationHandler::TRANSPORT );

                $collection->setAttribute( 'data_subject', $subject );
                $collection->setAttribute( 'data_text', $result );
                $collection->store();
                print_r( $collection );
               
                foreach ( $collectionItems as $collectionItem )
                {
                    $collection->addItem( $collectionItem['email'] );
                }
                 
            }
        }
        else if ( $collectionHandling == self::NOTIFICATION_COLLECTION_PER_USER )
        {
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
