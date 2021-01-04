<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

use CodeAnti\Swoft\Pgsql\Traits\HelperTraits;
use CodeAnti\Swoft\Pgsql\Traits\StrTraits;
use Exception;
use ReflectionException;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Pgsql\Exception\PgsqlException;
use Swoft\Pgsql\Pool;

/**
 * Class Test
 *
 * @since 2.0
 *
 * @Bean()
 */
class Model
{
    use HelperTraits, StrTraits;

    /**
     * @Inject("pgsql.pool")
     * @var Pool
     */
    protected $pgsql;

    /**
     * The connection name for the model.
     * @var string
     */
    protected $connection;

    /**
     * The table name for the model.
     * @var string
     */
    protected $table;

    /**
     * primary key
     * @var string 
     */
    public $primaryKey = 'id';

    /**
     * The model attributes.
     * @var array
     */
    public $attributes = [];


    /**
     * Begin query the model.
     *
     * @return Builder
     * @throws ReflectionException
     * @throws PgsqlException
     */
    public function query()
    {
        return (new Builder($this->pgsql->createConnection()))->setModel($this);
    }

    /**
     * Begin save the model.
     *
     * @return void
     * @throws PgsqlException
     * @throws ReflectionException
     * @throws Exception
     */
    public function save()
    {
         $primaryKeyValue = (new Builder($this->pgsql->createConnection()))->setModel($this)->save();
         if (!isset($this->attributes[$this->primaryKey])) {
             $this->attributes[$this->primaryKey] = $primaryKeyValue;
         }
    }
    
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            return str_replace(
                '\\', '', $this->snake($this->plural($this->class_basename($this)))
            );
        }

        return $this->table;
    }

    /**
     * Set Attribute Value
     * 
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Get Attributes Value
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return null;
    }
}
