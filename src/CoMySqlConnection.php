<?php

namespace SwoftLaravel\Database;

use Closure;
use Exception;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\QueryException;
use PDO;
use Illuminate\Database\Connection;

class CoMySqlConnection extends MySqlConnection
{
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            $result = $callback($query, $bindings);
        }
        // 捕获异步客户端连接异常
        catch (CoPDOException $e){
            $e->setMysqlError($this);
            throw $e;
        }
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }
        return $result;
    }

    public function reconnect() {
        $this->getPdo()->reconnect();
    }
    public function close(){
        return $this->getPdo()->close();
    }

}
