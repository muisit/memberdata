<?php

namespace MemberData\Lib\Services;

use MemberData\Lib\Display;
use MemberData\Models\Member;
use MemberData\Models\QueryBuilder;

class MemberRetrieveService
{
    private static $joinAliases = [];

    public static function countMembers($settings)
    {
        $sheet = intval($settings['sheet'] ?? 0);
        $filter = $settings['filter'] ?? null;
        $sorter = $settings['sorter'] ?? null;

        $memberModel = new Member();
        self::$joinAliases = [];
        $qb = $memberModel->select($memberModel->tableName() . '.id')->where('sheet_id', $sheet);
        $qb = self::combineWithEva($qb, $filter, $sorter, $sheet);
        $qb = self::addFilter($qb, empty($filter) ? [] : $filter, $sheet);
        $count = $qb->count();

        return $count;
    }

    public static function retrieveMembers($settings)
    {
        $sheet = intval($settings['sheet'] ?? 0);
        $offset = intval($settings['offset'] ?? 0);
        $pagesize = intval($settings['pagesize'] ?? 0);
        $filter = $settings['filter'] ?? null;
        $sorter = $settings['sorter'] ?? null;
        $sortDirection = $settings['sortDirection'] ?? 'asc';
        $cutoff = $settings['cutoff'] ?? 100;
        $count = self::countMembers($settings);

        $memberModel = new Member();
        self::$joinAliases = [];
        $qb = $memberModel->select($memberModel->tableName() . '.id')->where('sheet_id', $sheet);
        $qb = self::combineWithEva($qb, $filter, $sorter, $sheet);
        $qb = self::addSorter($qb, $memberModel, $sorter, $sortDirection);
        $qb = self::addFilter($qb, empty($filter) ? [] : $filter, $sheet);

        // use cutoff to determine if we can return the whole set, or just a page
        if ($count > $cutoff && $pagesize > 0 && $offset >= 0) {
            $qb->offset($offset)->limit($pagesize);
        }

        $results = $memberModel->collectAttributes($qb->get());

        $prevList = $settings['list'] ?? [];
        $settings['list'] = array_merge($prevList, $results);
        $settings['count'] = ($settings['count'] ?? 0) + $count;
        return $settings;
    }

    private static function addFilter(QueryBuilder $qb, array $filter, int $sheet)
    {
        $config = self::getConfig($sheet);
        foreach ($config as $attribute) {
            $aname = $attribute['name'] ?? '';
            if (isset($filter[$aname])) {
                $search = $filter[$aname]["search"] ?? null;
                if ($search == null) {
                    $search = '';
                }
                $search = strtolower(trim($search));

                if (strlen($search) || count($filter[$aname]["values"]) > 0) {
                    $alias = self::$joinAliases[$aname];
                    $sub = $qb->sub();

                    if (strlen($search)) {
                        $sub->orWhere('LOWER(' . $alias . '.value)', 'like', '%' . $search . '%');
                    }

                    foreach ($filter[$aname]["values"] as $filtervar) {
                        if ($filtervar === null) {
                            $sub->orWhere($alias . '.value', null);
                        }
                        else {
                            $sub->orWhere($alias . '.value', $filtervar);
                        }
                    }
                    $qb->where($sub->get());
                }
            }
        }
        if (!isset($filter['withTrashed'])) {
            $qb->where('softdeleted', null);
        }
        return $qb;
    }

    private static function addSorter(QueryBuilder $qb, $memberModel, $sorter, $sortDirection)
    {
        if (!empty($sorter)) {
            $dir = 'asc';
            if (in_array($sortDirection, ['asc', 'desc'])) {
                $dir = $sortDirection;
            }
            if ($sorter != 'id') {
                $qb->orderBy('eva.value IS NULL', 'asc')->orderBy('eva.value', $dir);
            }
            else {
                $qb->orderBy($memberModel->tableName() . '.id', $dir);
            }
        }
        return $qb;
    }

    private static function combineWithEva(QueryBuilder $qb, $filter, $sorter, $sheet)
    {
        if (!empty($sorter) && $sorter != 'id') {
            $qb->withEva($sorter, 'eva');
        }

        if (!empty($filter)) {
            $config = self::getConfig($sheet);
            foreach ($config as $attribute) {
                $aname = $attribute['name'];
                if (isset($filter[$aname]) && count($filter[$aname])) {
                    $alias = "al" . count(self::$joinAliases);
                    if ($aname == $sorter) {
                        $alias = 'eva';
                    }
                    else {
                        $qb->withEva($aname, strtolower($alias));
                    }
                    self::$joinAliases[$aname] = $alias;
                }
            }
        }
        return $qb;
    }

    private static function getConfig(int $sheet)
    {
        $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
        if (empty($config)) {
            $config = [];
        }
        if (isset($config['sheet-' . $sheet])) {
            return $config['sheet-' . $sheet];
        }
        return [];
    }
}
