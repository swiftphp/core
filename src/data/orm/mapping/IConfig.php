<?php
namespace swiftphp\core\data\orm\mapping;

use swiftphp\core\data\orm\mapping\Table;

/**
 * ORM配置接口
 * @author Tomix
 *
 */
interface IConfig
{
    /**
     * 获取所有表对象
     * @return Table[]
     */
    function getTables();

    /**
     * 根据对象或类名获取表对象
     * @param object|string $class
     * @return Table
     */
    function getTable($class);

    /**
     * 映射列集到表对象
     * @param Table $table 表对象
     * @return bool
     */
    function mappingColumns(Table &$table);
}

