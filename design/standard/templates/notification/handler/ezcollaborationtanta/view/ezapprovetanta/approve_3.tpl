{let approval_content=$collaboration_item.content
     objectversion=fetch( content, version,
                          hash( object_id, $approval_content.content_object_id,
                                version_id, $approval_content.content_object_version ) )}
{set-block scope=root variable=subject}El contenido "{$objectversion.version_name|wash}" no ha sido aceptado{if $notification_creator_object} por "{$notification_creator_object.name|wash}"{/if}{/set-block}
El contenido "{$objectversion.version_name|wash}" no ha sido aceptado{if $notification_creator_object} por "{$notification_creator_object.name|wash}"{/if}.

{"If you do not want to continue receiving these notifications,
change your settings at:"|i18n( 'design/standard/notification' )}
http://{ezini( "SiteSettings", "SiteURL" )}{concat( "notification/settings/" )|ezurl( no )}

--
{"%sitename notification system"
 |i18n( 'design/standard/notification',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ) ) )}
{/let}