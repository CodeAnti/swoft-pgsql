<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

use Swoft\Connection\Pool\Contract\ConnectionInterface;
use Swoft\Pgsql\Connection\Connection;

class QueryBuilder
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
    public $columns;


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
     * @return array
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     */
    public function findAll()
    {
        return $this->connection->select("SELECT * FROM regions;");
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

}
