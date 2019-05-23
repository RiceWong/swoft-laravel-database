<?php

namespace SwoftLaravel\Database;

use PDO;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
class CoMysqlConnector extends Connector implements ConnectorInterface {
    /**
     * Establish a database connection.
     *
     * @param array $config
     * @return \PDO
     */
    public function connect(array $config) {

        $config = $this->getCoMysqlConfig($config);
        $connection = new CoPDOConnection($config);
        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        $this->setModes($connection, $config);

        return $connection;
    }

    protected function getCoMysqlConfig($config) {
        $coMysqlConfig = [
            'host'        => $config['host'],
            'port'        => $config['port'],
            'user'        => $config['username'],
            'password'    => $config['password'],
            'database'    => $config['database'],
            'timeout'     => isset($config['timeout']) ?: 30,
            'charset'     => $config['charset'],
            'strict_type' => false, // //开启严格模式，query方法返回的数据也将转为强类型
            'fetch_mode'  => true, //开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        ];
        return $coMysqlConfig;
    }

    /**
     * Set the connection character set and collation.
     *
     * @param \PDO $connection
     * @param array $config
     * @return void
     */
    protected function configureEncoding($connection, array $config) {
        if (!isset($config['charset'])) {
            return $connection;
        }

        $connection->prepare("set names '{$config['charset']}'" . $this->getCollation($config))->execute();
    }

    /**
     * Get the collation for the configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getCollation(array $config) {
        return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /**
     * Set the timezone on the connection.
     *
     * @param \PDO $connection
     * @param array $config
     * @return void
     */
    protected function configureTimezone($connection, array $config) {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
    }

    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config) {
        return $this->hasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * @param array $config
     * @return bool
     */
    protected function hasSocket(array $config) {
        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getSocketDsn(array $config) {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getHostDsn(array $config) {
        extract($config, EXTR_SKIP);

        return isset($port) ? "mysql:host={$host};port={$port};dbname={$database}" : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Set the modes for the connection.
     *
     * @param \PDO $connection
     * @param array $config
     * @return void
     */
    protected function setModes(PDOConnectionInterface $connection, array $config) {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare($this->strictMode($connection))->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /**
     * Set the custom modes on the connection.
     *
     * @param \PDO $connection
     * @param array $config
     * @return void
     */
    protected function setCustomModes(PDOConnectionInterface  $connection, array $config) {
        $modes = implode(',', $config['modes']);

        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /**
     * Get the query to enable strict mode.
     *
     * @param \PDO $connection
     * @return string
     */
    protected function strictMode(PDOConnectionInterface  $connection) {
        if (version_compare($connection->getAttribute(PDO::ATTR_SERVER_VERSION), '8.0.11') >= 0) {
            return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
    }
}
