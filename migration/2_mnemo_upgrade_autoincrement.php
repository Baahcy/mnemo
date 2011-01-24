<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Mnemo
 */
class MnemoUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('mnemo_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => true));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('mnemo_shares', 'share_id', 'integer', array('null' => false));
    }

}