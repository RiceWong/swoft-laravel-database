<?php

namespace SwoftLaravel\Database;

use Closure;
use Illuminate\Database\MySqlConnection;
use PDO;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Database\ConnectionInterface;
class CoMySqlConnectionProxy implements ConnectionInterface{
    protected $_proxy;
    public function __construct($connection, $name, $manager, $attrs=[]) {
        $this->_proxy = [
            'connection' => $connection,
            'name'       => $name,
            'manager'    => $manager,
            'attrs'      => $attrs
        ];
    }

    public function __destruct() {
        list($connection, $name, $manager, $attrs) = array_values($this->_proxy);
        $this->_proxy = null;
        $manager->releaseConnection($name, $connection, $attrs);
    }
    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table) {
        return $this->_proxy['connection']->table(...func_get_args());
    }

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value) {
        return $this->_proxy['connection']->raw(...func_get_args());
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true) {
        return $this->_proxy['connection']->selectOne(...func_get_args());
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true) {
        return $this->_proxy['connection']->select(...func_get_args());
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true) {
        return $this->_proxy['connection']->cursor(...func_get_args());
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = []) {
        return $this->_proxy['connection']->insert(...func_get_args());
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update($query, $bindings = []) {
        return $this->_proxy['connection']->update(...func_get_args());
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete($query, $bindings = []) {
        return $this->_proxy['connection']->delete(...func_get_args());
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement($query, $bindings = []) {
        return $this->_proxy['connection']->statement(...func_get_args());
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []) {
        return $this->_proxy['connection']->affectingStatement(...func_get_args());
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared($query) {
        return $this->_proxy['connection']->unprepared(...func_get_args());
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings) {
        return $this->_proxy['connection']->prepareBindings(...func_get_args());
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param \Closure $callback
     * @param int $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1) {
        return $this->_proxy['connection']->transaction(...func_get_args());
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction() {
        return $this->_proxy['connection']->beginTransaction(...func_get_args());
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit() {
        return $this->_proxy['connection']->commit(...func_get_args());
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack() {
        return $this->_proxy['connection']->rollBack(...func_get_args());
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel() {
        return $this->_proxy['connection']->transactionLevel(...func_get_args());
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param \Closure $callback
     * @return array
     */
    public function pretend(Closure $callback) {
        return $this->_proxy['connection']->pretend(...func_get_args());
    }
    public function close(){
        return $this->_proxy['connection']->close();
    }
    public function __call($method, $arguments) {
        return $this->_proxy['connection']->$method(...$arguments);
    }
}
