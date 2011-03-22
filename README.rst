eZApproveTanta is an extension for eZ Publish, adding the posibility of sending
mails whenever a collaboration item is commented, approved or rejected. 

Current eZ Publish functionality only sends e-mails when collaboration process
starts

How it works
-------------

They key part is how notification events are created. Default eZ Publish collaboration functionality fills some
files of the eznotificationevent table, but not all. 

This extension will use two more fields of that table. First one will indicate type of collaboration message:

- New item

- Item is commented ( but no decision has been made about approve or rejecting )

- Item is approved

- Item is rejected

For these purpouses the extension adds

- a workflow: just to add distinct identifier to the collaboration items created

- a custom collaboration handler: for adding notification events also when a collab item is commented, approved or rejected

- a custon notification event:

- a custom notification handler:
