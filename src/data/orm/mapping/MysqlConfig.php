<?php
namespace swiftphp\core\data\orm\mapping;

use swiftphp\core\data\types\Type;

/**
 * Mysql ORM配置
 * @author Tomix
 *
 */
class MysqlConfig extends Config
{
    /**
     * 映射缓存.键为表名,值为Table对象
     * @var array
     */
    private $m_tableCache=[];

    /**
     * 映射列集到表对象
     * @param Table $table 表对象
     * @return bool
     */
    public function mappingColumns(Table &$table)
    {
        //from cache
        if(array_key_exists($table->getName(), $this->m_tableCache)){
            $table=$this->m_tableCache[$table->getName()];
            return true;
        }

        // 数据库列名集
        $sql = "SHOW COLUMNS FROM " . $table->getName();
        $fields = $this->m_database->query($sql);

        // 数据库字段
        if($fields){
            foreach ($fields as $field) {
                $fieldName= $field["Field"];

                //列详细: Field Type Null Key Default
                $column = new Column();
                $column->setName($field["Field"]);
                $column->setDbType($field["Type"]);
                $column->setType($this->mappingType($field["Type"]));
                $column->setNullable(strtoupper($field["Null"]) == "YES" ? true : false);
                $column->setDefault($field["Default"]);

                // 主键字段集
                if (strtoupper($field["Key"]) == "PRI") {
                    $column->setPrimary(true);
                }

                // 唯一键字段集
                if (strtoupper($field["Key"]) == "UNI") {
                    $column->setUnique(true);
                }

                // 自动递增字段集
                if (strtoupper($field["Extra"]) == "AUTO_INCREMENT") {
                    $column->setIncrement(true);
                }

                //添加到表对象
                $table->addColumn($fieldName, $column);
            }
            $this->m_tableCache[$table->getName()]=$table;
            return true;
        }
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see Config::mappingType()
     * @return string
     */
    protected function mappingType($sqlType)
    {
        $sqlType=strtolower($sqlType);
        $pos=strpos($sqlType, "(");
        if($pos>0)
            $sqlType=substr($sqlType, 0,$pos);
        switch ($sqlType){
            case "int":         return Type::INTEGER;
            case "float":       return Type::DOUBLE;
            case "double":      return Type::DOUBLE;
            case "decimal":     return Type::DOUBLE;
            case "tinyint":     return Type::BOOLEAN;
            case "datetime":    return Type::DATETIME;
            case "date":        return Type::DATE;
            default:            return Type::STRING;
        }
    }
}