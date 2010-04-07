<?php
/**
 * $Horde: mnemo/view.php,v 1.48 2009/11/24 04:13:44 chuck Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('MNEMO_BASE', dirname(__FILE__));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

/* Check if a passphrase has been sent. */
$passphrase = Horde_Util::getFormData('memo_passphrase');

/* We can either have a UID or a memo id and a notepad. Check for UID
 * first. */
$storage = Mnemo_Driver::singleton();
if ($uid = Horde_Util::getFormData('uid')) {
    $memo = $storage->getByUID($uid, $passphrase);
    if (is_a($memo, 'PEAR_Error')) {
        header('Location: ' . Horde::applicationUrl('list.php', true));
        exit;
    }

    $memo_id = $memo['memo_id'];
    $memolist_id = $memo['memolist_id'];
} else {
    /* If we aren't provided with a memo and memolist, redirect to
     * list.php. */
    $memo_id = Horde_Util::getFormData('memo');
    $memolist_id = Horde_Util::getFormData('memolist');
    if (!isset($memo_id) || !$memolist_id) {
        header('Location: ' . Horde::applicationUrl('list.php', true));
        exit;
    }

    /* Get the current memo. */
    $memo = Mnemo::getMemo($memolist_id, $memo_id, $passphrase);
}

$share = $GLOBALS['mnemo_shares']->getShare($memolist_id);
if (is_a($share, 'PEAR_Error')) {
    $notification->push(sprintf(_("There was an error viewing this notepad: %s"), $share->getMessage()), 'horde.error');
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
} elseif (!$share->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
    $notification->push(sprintf(_("You do not have permission to view the notepad %s."), $share->get('name')), 'horde.error');
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

/* If the requested note doesn't exist, display an error message. */
if (!$memo || !isset($memo['memo_id'])) {
    $notification->push(_("Note not found."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

/* Get the note's history. */
$userId = Horde_Auth::getAuth();
$createdby = '';
$modifiedby = '';
if (!empty($memo['uid'])) {
    $log = Horde_History::singleton()->getHistory('mnemo:' . $memolist_id . ':' . $memo['uid']);
    if ($log && !is_a($log, 'PEAR_Error')) {
	foreach ($log as $entry) {
            switch ($entry['action']) {
            case 'add':
                $created = $entry['ts'];
                if ($userId != $entry['who']) {
                    $createdby = sprintf(_("by %s"), Mnemo::getUserName($entry['who']));
                } else {
                    $createdby = _("by me");
                }
                break;

            case 'modify':
                $modified = $entry['ts'];
                if ($userId != $entry['who']) {
                    $modifiedby = sprintf(_("by %s"), Mnemo::getUserName($entry['who']));
                } else {
                    $modifiedby = _("by me");
                }
                break;
            }
        }
    }
}

/* Encryption tests. */
$show_passphrase = false;
if (is_a($memo['body'], 'PEAR_Error')) {
    /* Check for secure connection. */
    $secure_check = Horde::isConnectionSecure();
    if ($memo['body']->getCode() == MNEMO_ERR_NO_PASSPHRASE) {
        if ($secure_check) {
            $notification->push(_("This note has been encrypted, please provide the password below"), 'horde.message');
            $show_passphrase = true;
        } else {
            $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
            $memo['body'] = '';
        }
    } elseif ($memo['body']->getCode() == MNEMO_ERR_DECRYPT) {
        if ($secure_check) {
            $notification->push(_("This note cannot be decrypted:") . ' ' . $memo['body']->getMessage(), 'horde.message');
            $show_passphrase = true;
        } else {
            $notification->push(_("This note has been encrypted, and cannot be decrypted without a secure web connection"), 'horde.error');
            $memo['body'] = '';
        }
    } else {
        $notification->push($memo['body'], 'horde.error');
        $memo['body'] = '';
    }
}

/* Set the page title to the current note's name, if it exists. */
$title = $memo ? $memo['desc'] : _("Note Details");
$print_view = (bool)Horde_Util::getFormData('print');
if (!$print_view) {
    Horde::addScriptFile('popup.js', 'horde', true);
}
require MNEMO_TEMPLATES . '/common-header.inc';

if ($print_view) {
    require $registry->get('templates', 'horde') . '/javascript/print.js';
} else {
    $print_link = Horde_Util::addParameter('view.php', array('memo' => $memo_id,
                                                       'memolist' => $memolist_id,
                                                       'print' => 'true'));
    $print_link = Horde::url($print_link);
    Horde::addScriptFile('stripe.js', 'horde', true);
    require MNEMO_TEMPLATES . '/menu.inc';
}

require MNEMO_TEMPLATES . '/view/memo.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
