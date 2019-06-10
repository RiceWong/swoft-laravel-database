<?php

namespace SwoftLaravel\Database;

use Illuminate\Database\MySqlConnection;
use PDO;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

class CoMySqlConnection extends MySqlConnection
{
    public function close(){
        return $this->pdo->close();
    }
}
