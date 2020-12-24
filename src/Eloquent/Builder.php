<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

use Closure;
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


    /**
     * Builder constructor.
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set the columns to be selected.
     * @param  array|mixed  $columns
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
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
     * @param string|array|\Closure $column
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
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
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
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Find All Data Rows
     * @return array
     * @throws ReflectionException
     */
    public function findAll()
    {
        return $this->selectExecute();
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
     * @param  string  $table
     * @return $this
     */
    public function setFrom($table)
    {
        $this->from = $table;
        return $this;
    }

    /**
     * Execute Select Sql
     * @throws ReflectionException
     */
    public function selectExecute()
    {
       return $this->connection->select($this->buildSql());
    }

    /**
     * Build Select Sql
     * @return string
     */
    public function buildSql()
    {
        $sql = "SELECT " . $this->buildColumns() . "FROM " . $this->from . $this->buildWheres();
        return $sql;
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
        if (empty($this->where) || empty($this->orWhere)) {
            return '';
        }
        $wheres = array_merge_recursive($this->where, $this->orWhere);

        $whereConditions = ' where ';
        foreach ($wheres as $key => $where) {
            if ($key != 0) {
                $whereConditions .= $where['boolean'];
            }

            $whereConditions = $whereConditions . $where['column'] . $where['operator'] . $where['value'];
        }
        
        return $whereConditions;
    }
}
