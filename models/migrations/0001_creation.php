<?php

namespace WPElo\Models;

class Migration0001 extends MigrationObject
{
    public function up()
    {
        $this->createTable("wpelo_player", "(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(200) NOT NULL,
            `groupname` varchar(20) NOT NULL,
            `modified` datetime NOT NULL,
            `modifier` int(11) NOT NULL,
            `state` varchar(20) COLLATE utf8_bin NULL,
            `rank` int(11) NOT NULL DEFAULT(1000),
            `remark` TEXT NULL,
            `softdeleted` datetime NULL,
            `deletor` int(11) NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB");

        $this->createTable("wpelo_match", "(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `player_1` int(11) NOT NULL,
            `player_1_rank` int(11) NOT NULL,
            `player_1_score` int(11) NOT NULL,
            `player_1_expect` int(11) NOT NULL,
            `player_1_change` int(11) NOT NULL,
            `player_2` int(11) NOT NULL,
            `player_2_rank` int(11) NOT NULL,
            `player_2_score` int(11) NOT NULL,
            `player_2_expect` int(11) NOT NULL,
            `player_2_change` int(11) NOT NULL,
            `c_value` int(11) NOT NULL,
            `s_value` int(11) NOT NULL,
            `l_value` int(11) NOT NULL,
            `k_value` int(11) NOT NULL,
            `modified` datetime NOT NULL,
            `modifier` int(11) NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB");
        return true;
    }

    public function down()
    {
        $this->dropTable("wpelo_player");
        $this->dropTable("wpelo_match");
        return true;
    }
}