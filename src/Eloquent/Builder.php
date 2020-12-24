<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

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
     * The Select Conditions
     */
    public $where;


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
     * Find All Data Rows
     * @return array
     * @throws ReflectionException
     */
    public function findAll()
    {
        $this->where = '';
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
        $sql = "SELECT " . $this->buildColumns() . "FROM " . $this->from . " " . $this->where;
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

}
