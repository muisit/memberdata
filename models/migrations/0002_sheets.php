<?php

namespace MemberData\Models;

use MemberData\Models\Sheet;
use MemberData\Models\Member;
use MemberData\Lib\Display;

class Migration0002 extends MigrationObject
{
    public function up()
    {
        $this->createTable("memberdata_sheet", "(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `modified` datetime NOT NULL,
            `modifier` int(11) NOT NULL,
            `softdeleted` datetime NULL,
            `deletor` int(11) NULL,
            PRIMARY KEY (`id`)) ENGINE=InnoDB");

        $this->addColumn("memberdata_member", "sheet_id", "int(11) NOT NULL DEFAULT(0)");

        $sheet = new Sheet();
        $sheet->name = "Sheet 1";
        $sheet->save();

        $memberModel = new Member();
        $this->rawQuery("UPDATE " . $memberModel->tableName() . " set sheet_id='" . $sheet->getKey() . "';");

        $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
        if (empty($config)) {
            $config = [];
            add_option(Display::PACKAGENAME . '_configuration', json_encode($config));
        }
        $config = ["sheet-" . $sheet->getKey() => $config];
        update_option(Display::PACKAGENAME . '_configuration', json_encode($config));

        return true;
    }

    public function down()
    {
        $this->dropColumn("memberdata_member", "sheet_id");
        $this->dropTable("memberdata_sheet");
        return true;
    }
}
