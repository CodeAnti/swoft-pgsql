<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use Swoft\Connection\Pool\Contract\ConnectionInterface;
use Swoft\Pgsql\Connection\Connection;

class Builder
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $columns = ['*'];

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    public $from;

    /**
     * The model being queried.
     * @var Model
     */
    public $model;

    /**
     * The Select Where Conditions
     */
    public $where = [];

    /**
     * The Select OrWhere Conditions
     * @var array
     */
    public $orWhere = [];

    /**
     * The Query Order By
     * @var array
     */
    public $orderBy = [];

    /**
     * The Query Limit
     * @var Int
     */
    public $limit = 0;

    /**
     * The Query Offset
     * @var Int
     */
    public $offset = 0;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    public const EXECUTE_ACTION_DELETE = 'DELETE';
    public const EXECUTE_ACTION_UPDATE = 'UPDATE';
    public const EXECUTE_ACTION_SAVE = 'SAVE';
    public const EXECUTE_ACTION_INSERT = 'INSERT';

    public const SELECT_ACTION_FIND_ALL = 'FIND_ALL';
    public const SELECT_ACTION_FIRST = 'FIRST';
    public const SELECT_ACTION_FIND = 'FIND';
    public const SELECT_ACTION_COUNT = 'COUNT';
    public const SELECT_ACTION_SUM = 'SUM';

    /**
     * Builder constructor.
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the columns to be selected.
     * @param array|mixed $columns
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
    }

    /**
     * Query Paginate
     * @param int $perPage
     * @param int $currentPage
     * @return array
     * @throws ReflectionException
     */
    public function paginate($perPage = 15, $currentPage = 1)
    {
        if ($perPage <= 0) {
            $perPage = 15;
        }

        // total count
        $count = $this->selectExecute(self::SELECT_ACTION_COUNT);

        // total pageNum
        $pageNum = intval(ceil($count / $perPage));
        if ($currentPage > $pageNum) {
            $currentPage = $pageNum;
        }

        // offset limit
        $this->offset = ($currentPage - 1) * $perPage;
        $this->limit = $perPage;

        // items
        $items = $this->selectExecute(self::SELECT_ACTION_FIND_ALL);

        return [
            'current_page' => $currentPage,
            'total_page' => $pageNum,
            'total_records' => $count,
            'data' => $items
        ];
    }

    public function with()
    {

    }

    public function hasOne()
    {

    }

    public function belongsTo()
    {

    }

    public function hasMany()
    {

    }

    public function belongsToMany()
    {

    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string|array|Closure $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return Builder
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure) {
            // TODO
        } else {
            // Here we will make some assumptions about the operator. If only 2 values are
            // passed to the method, we will assume that the operator is an equals sign
            // and keep going. Otherwise, we'll require the operator to be passed in.
            list($value, $operator) = $this->prepareValueAndOperator(
                $value, $operator, func_num_args() == 2
            );

            if ($boolean == 'or') {
                array_push($this->orWhere, ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean]);
            }

            if ($boolean == 'and') {
                array_push($this->where, ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean]);
            }
        }

        return $this;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string|array|Closure $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return Builder
     */
    public function orWhere($column, $operator = null, $value = null, $boolean = 'or')
    {
        return $this->where(...func_get_args());
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param string $value
     * @param string $operator
     * @param bool $useDefault
     * @return array
     * @throws InvalidArgumentException
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            !in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Find All Data Rows
     * @return array
     * @throws ReflectionException
     */
    public function findAll()
    {
        return $this->selectExecute(self::SELECT_ACTION_FIND_ALL);
    }

    /**
     * Query Order By
     * @param array $orderBy
     * @return Builder
     */
    public function orderBy(Array $orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Query Limit
     * @param Int $limit
     * @return Builder
     */
    public function limit(Int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Query Offset
     * @param Int $offset
     * @return Builder
     */
    public function offset(Int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Delete All Data Rows
     * @return Int
     * @throws Exception
     */
    public function delete()
    {
        return $this->sqlExecute(self::EXECUTE_ACTION_DELETE);
    }

    /**
     * Update All Data Rows
     * @param array $attributes
     * @return Int
     * @throws Exception
     */
    public function update(Array $attributes)
    {
        return $this->sqlExecute(self::EXECUTE_ACTION_UPDATE, $attributes);
    }

    /**
     * Insert Data
     * @param array $attributes
     * @return Int
     * @throws Exception
     */
    public function insert(Array $attributes)
    {
        return $this->sqlExecute(self::EXECUTE_ACTION_INSERT, $attributes);
    }

    /**
     * Save All Data Rows
     * @return Int
     * @throws Exception
     */
    public function save()
    {
        return $this->sqlExecute(self::EXECUTE_ACTION_SAVE);
    }

    /**
     * Set a model instance for the model being queried.
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->setFrom($model->getTable());
        return $this;
    }

    /**
     * Set the table which the query is targeting.
     * @param string $table
     * @return $this
     */
    public function setFrom($table)
    {
        $this->from = $table;
        return $this;
    }

    /**
     * Execute Select Sql
     * @param string $action
     * @return array
     * @throws ReflectionException
     * @throws Exception
     */
    public function selectExecute($action = '')
    {
        switch ($action) {
            case self::SELECT_ACTION_COUNT:
                $count = $this->connection->select($this->buildSelectCountSql());
                return $count[0]['count'];
            case self::SELECT_ACTION_FIND_ALL:
                return $this->connection->select($this->buildSelectSql());
            default:
                throw new Exception(sprintf('select sql action not exists. action:%s', $action));
        }
    }

    /**
     * Execute Update/Insert/Delete/Save Sql
     * @param String $action
     * @param array $attributes
     * @return Int
     * @throws Exception
     */
    public function sqlExecute(String $action, Array $attributes = [])
    {
        switch ($action) {
            case self::EXECUTE_ACTION_DELETE:
                $sql = $this->buildDeleteSql();
                return $this->connection->executeQuery($sql);
            case self::EXECUTE_ACTION_UPDATE:
                $sql = $this->buildUpdateSql($attributes);
                return $this->connection->executeQuery($sql);
            case self::EXECUTE_ACTION_INSERT:
                $sql = $this->buildInsertSql($attributes);
                return $this->connection->executeQuery($sql);
            case self::EXECUTE_ACTION_SAVE:
                return $this->executeSave();
            default:
                throw new Exception(sprintf('execute sql action not exists. action:%s', $action));
        }
    }

    /**
     * Build Select Sql
     * @return string
     */
    public function buildSelectSql()
    {
        $sql = "SELECT " . $this->buildColumns() . " FROM " . $this->from . $this->buildWheres() . $this->buildOrderBy() . $this->buildLimit() . $this->buildOffset();
        echo $sql;
        return $sql;
    }

    /**
     * Build Select Count sql
     */
    public function buildSelectCountSql()
    {
        $sql = "SELECT COUNT(*) FROM " . $this->from . $this->buildWheres();
        return $sql;
    }

    /**
     * Build Delete Sql
     * @return string
     */
    public function buildDeleteSql()
    {
        $sql = "DELETE FROM " . $this->from . $this->buildWheres();
        return $sql;
    }

    /**
     * Build Update Sql
     * @param array $attributes
     * @return string
     */
    public function buildUpdateSql(Array $attributes)
    {
        $sql = "UPDATE " . $this->from . " SET " . $this->buildAttributesToString($attributes) . $this->buildWheres();
        return $sql;
    }

    /**
     * Build Insert Sql
     * @param array $attributes
     * @return string
     */
    public function buildInsertSql(Array $attributes)
    {
        $sql = "INSERT INTO " . $this->from . $this->buildInsertAttributes($attributes) . " RETURNING " . $this->model->primaryKey;
        return $sql;
    }

    /**
     * Execute Save Sql
     * @return array|bool|Int
     * @throws Exception
     */
    public function executeSave()
    {
        $sql = $this->buildSaveSql();
        if (isset($attributes[$this->model->primaryKey])) {
            // update data
            return $this->connection->executeQuery($sql);
        } else {
            // insert data
            $result = $this->connection->selectQuery($sql);
            return $result[$this->model->primaryKey];
        }
    }

    /**
     * Build Save Sql
     * @return string
     */
    public function buildSaveSql()
    {
        $attributes = $this->model->attributes;

        if (isset($attributes[$this->model->primaryKey])) {
            // update data
            $this->where($this->model->primaryKey, $attributes[$this->model->primaryKey]);
            return $this->buildUpdateSql($attributes);
        } else {
            // insert data
            return $this->buildInsertSql($attributes);
        }
    }

    /**
     * Build Insert Attributes
     * @param array $attributes
     * @return string
     */
    protected function buildInsertAttributes(Array $attributes)
    {
        $keyList = [];
        $attributeList = [];
        foreach ($attributes as $key => $attribute) {
            array_push($keyList, $key);
            array_push($attributeList, "'" . $attribute . "'");
        }

        $sql = "(" . implode(",", $keyList) . ")" . " VALUES " . "(" . implode(",", $attributeList) . ")";
        return $sql;
    }

    /**
     * Build Attributes To String
     * @param array $attributes
     * @return string
     */
    protected function buildAttributesToString(Array $attributes)
    {
        $attributeSql = '';
        $index = 0;
        foreach ($attributes as $key => $attribute) {
            if ($index > 0) {
                $attributeSql .= ",";
            }
            $attributeSql .= $key . "=" . "'" . $attribute . "'";
            $index++;
        }
        return $attributeSql;
    }

    /**
     * Build Columns
     * @return string
     */
    protected function buildColumns()
    {
        return trim(implode(",", $this->columns), ',');
    }

    /**
     * Build Wheres
     * @return string
     */
    protected function buildWheres()
    {
        if (empty($this->where) && empty($this->orWhere)) {
            return '';
        }
        $wheres = array_merge_recursive($this->where, $this->orWhere);

        $whereConditions = ' WHERE ';
        foreach ($wheres as $key => $where) {
            if ($key != 0) {
                $whereConditions .= $where['boolean'];
            }

            $whereConditions = $whereConditions . $where['column'] . $where['operator'] . $where['value'];
        }

        return $whereConditions;
    }

    /**
     * Build Order By
     * @return string
     */
    protected function buildOrderBy()
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $index = 0;
        $orderByCondition = ' ORDER BY ';
        foreach ($this->orderBy as $key => $orderBy) {
            if ($index != 0) {
                $orderByCondition .= ',';
            }
            $orderByCondition .= $key . ' ' . $orderBy;
            $index++;
        }
    }

    /**
     * Build Limit
     * @return string
     */
    protected function buildLimit()
    {
        if ($this->limit === 0) {
            return '';
        }

        return ' LIMIT ' . $this->limit;
    }


    /**
     * Build Limit
     * @return string
     */
    protected function buildOffset()
    {
        if ($this->offset === 0) {
            return '';
        }

        return ' OFFSET ' . $this->offset;
    }
}
