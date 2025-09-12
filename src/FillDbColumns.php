<?php

namespace Goletter\Traits;

use Illuminate\Support\Facades\Schema;

trait FillDbColumns
{
    protected static $tableColumnsCache = [];

    public function fillDb(array $data)
    {
        $table = $this->getTable();

        // 缓存表字段，避免每次都查询数据库
        if (!isset(self::$tableColumnsCache[$table])) {
            self::$tableColumnsCache[$table] = Schema::getColumnListing($table);
        }

        $columns = self::$tableColumnsCache[$table];

        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $this->$key = $value;
            }
        }

        return $this;
    }
}
