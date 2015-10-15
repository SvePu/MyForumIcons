# MyForumIcons
Set custom icons for your MyBB forums.

MyForumIcons allows you to set custom images for your forum icons.

You can edit these icons through your Forum Management settings in the "Edit Forum Settings" tabs.

#### This is a modified plugin version by Svepu

**This update requires a fully reinstall.**

If you want to keep your forum icon database entries open the old plugin file in MYBB_ROOT/inc/plugins/, search for:
```php
global $db, $mybb;
$db->drop_column('forums', 'myforumicons_icon');
```
Change it into:
```php
global $db, $mybb;
//$db->drop_column('forums', 'myforumicons_icon');
```
Save changes and uninstall the old version in your forum ACP.

After that copy all files of new version to your forum root on server, install plugin in forum ACP and check plugin settings.


Have Fun!!

