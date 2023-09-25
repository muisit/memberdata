<?php

namespace WPElo\Models;

class Migration0001 extends MigrationObject
{
    public function up()
    {
        $this->createTable("memberdata_member", "(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `modified` datetime NOT NULL,
            `modifier` int(11) NOT NULL,
            `softdeleted` datetime NULL,
            `deletor` int(11) NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB");

        $this->createTable("memberdata_eva", "(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `member_id` int(11) NOT NULL,
            `attribute` varchar(40) NOT NULL,
            `value` text NULL,
            `modified` datetime NOT NULL,
            `modifier` int(11) NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB");
        return true;
    }

    public function down()
    {
        $this->dropTable("memberdata_member");
        $this->dropTable("memberdata_eva");
        return true;
    }
}
