{let approval_content=$collaboration_item.content
     objectversion=fetch( content, version,
                          hash( object_id, $approval_content.content_object_id,
                                version_id, $approval_content.content_object_version ) )}
{set-block scope=root variable=subject}El contenido "{$objectversion.version_name|wash}" ha sido aprobado{/set-block}
El contenido "{$objectversion.version_name|wash}" ha sido aprobado{if $notification_creator_object} por "{$notification_creator_object.name|wash}"{/if}{*, puedes verlo en esta dirección:
http://{ezini( "SiteSettings", "SiteURL" )}{fetch( 'content', 'object', hash( 'object_id', $objectversion.contentobject_id ) ).main_node.url_alias|ezurl( no )*}.

{"If you do not want to continue receiving these notifications,
change your settings at:"|i18n( 'design/standard/notification' )}
http://{ezini( "SiteSettings", "SiteURL" )}{concat( "notification/settings/" )|ezurl( no )}

--
{"%sitename notification system"
 |i18n( 'design/standard/notification',,
        hash( '%sitename', ezini( "SiteSettings", "SiteURL" ) ) )}
{/let}