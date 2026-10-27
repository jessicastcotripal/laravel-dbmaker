<?php

/**
 * Created by syscom.
 * User: syscom
 * Date: 17/06/2019
 * Time: 15:50
 */
namespace DBMaker\ODBC;

use PDO;

class DBMakerPdo extends PDO {
    /**
     * Get the column listing for a given table.
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $passwd
     * @param  array   $options
     * @return array
     */
    public function __construct($dsn, $username, $passwd, $options = [])
    {
        if (isset($options['dbidcap']) && $options['dbidcap'] == 1) {
            $options[PDO::ATTR_CASE] = PDO::CASE_LOWER;
        }
        parent::__construct($dsn, $username, $passwd, $options);
    }
    
    /**
     *
     * @param  string  $statement
     * @param  array  $driver_options
     * @return Dbmaker\Odbc\DBMakerPdo
     */        
    public function prepare($statement,$driver_options = null)
    {
        return parent::prepare($statement); 
    }
    
}
