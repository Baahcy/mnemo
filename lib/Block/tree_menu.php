<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: mnemo/lib/Block/tree_menu.php,v 1.6 2009/06/10 03:46:19 chuck Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_mnemo_tree_menu extends Horde_Block {

    var $_app = 'mnemo';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $add = Horde::applicationUrl('memo.php')->add('actionID', 'add_memo');
	$icondir = (string)Horde_Themes::img();

        $tree->addNode($parent . '__new',
                       $parent,
                       _("New Note"),
                       $indent + 1,
                       false,
                       array('icon' => 'add.png',
                             'icondir' => $icondir,
                             'url' => $add));

        foreach (Mnemo::listNotepads() as $name => $notepad) {
	    if ($notepad->get('owner') != Horde_Auth::getAuth() &&
		!empty($GLOBALS['conf']['share']['hidden']) &&
		!in_array($notepad->getName(), $GLOBALS['display_notepads'])) {
		continue;
	    }
            $tree->addNode($parent . $name . '__new',
                           $parent . '__new',
                           sprintf(_("in %s"), $notepad->get('name')),
                           $indent + 2,
                           false,
                           array('icon' => 'add.png',
                                 'icondir' => $icondir,
                                 'url' => Horde_Util::addParameter($add, array('memolist' => $name))));
        }

        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'icondir' => (string)Horde_Themes::img(null, 'horde'),
                             'url' => Horde::applicationUrl('search.php')));
    }

}
