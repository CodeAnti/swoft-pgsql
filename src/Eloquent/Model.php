<?php
namespace CodeAnti\Swoft\Pgsql\Eloquent;

use CodeAnti\Swoft\Pgsql\Traits\HelperTraits;
use CodeAnti\Swoft\Pgsql\Traits\StrTraits;
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
     * Begin querying the model.
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



}
