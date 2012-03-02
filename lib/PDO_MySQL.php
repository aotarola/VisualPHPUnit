<?php

/**
 * VisualPHPUnit
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright Copyright (c) 2011-2012, Nick Sinopoli
 * @license   http://opensource.org/licenses/bsd-license.php The BSD License
 */

class PDO_MySQL {

   /**
    *  The db handle.
    *
    *  @var object
    *  @access protected
    */
    protected $_dbh;

   /**
    *  The result set associated with a prepared statement.
    *
    *  @var PDOStatement
    *  @access protected
    */
    protected $_statement;

   /**
    *  Loads the configuration settings for a MySQL connection and connects
    *  to the database.
    *
    *  @param array $config    The configuration settings, which takes
    *                          five options:
    *                          `database` - The name of the database.
    *                          `host`     - The database host.
    *                          `port`     - The database port.
    *                          `username` - The database username.
    *                          `password` - The database password.
    *                          (By default, instances are destroyed at the
    *                          end of the request.)
    *  @access public
    *  @return void
    */
    public function __construct($config) {
        $this->connect($config);
    }

   /**
    *  Connects and selects database.
    *
    *  @param array $options       Contains the connection information.
    *                              Takes the following options:
    *                              `database` - The name of the database.
    *                              `host`     - The database host.
    *                              `port`     - The database port.
    *                              `username` - The database username.
    *                              `password` - The database password.
    *  @access public
    *  @return bool
    */
    public function connect($options) {
        $dsn = 'mysql:host=' . $options['host'] . ';port=' . $options['port']
            . ';dbname=' . $options['database'];
        try {
            $this->_dbh = new \PDO($dsn, $options['username'], $options['password']);
            $this->_dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return true;
        } catch ( PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }
    }

   /**
    *  Closes the connection.
    *
    *  @access public
    *  @return bool
    */
    public function close() {
        $this->_dbh = null;
        return true;
    }

   /**
    *  Returns an array containing all of the result set rows.
    *
    *  @access public
    *  @return mixed
    */
    public function fetch_all() {
        $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $this->_statement->fetchAll();
        $this->_statement->closeCursor();
        return $rows;
    }

   /**
    *  Performs a `SELECT FROM` query.
    *
    *  @see PDO_MySQL->query()
    *  @see PDO_MySQL->fetch()
    *  @see PDO_MySQL->_format_where()
    *  @param string|array $fields       The fields to be retrieved.
    *  @param string $table              The table to SELECT from.
    *  @param string|array $where        The WHERE clause of the SQL query.
    *  @param string $additional         Any additional SQL to be added at the end of the query.
    *  @access public
    *  @return void
    */
    public function find($fields, $table, $where = null, $additional = null) {
        $sql = 'SELECT ';
        if ( is_array($fields) ) {
            $sql .= '`' . implode('`, `', $fields) . '`';
        } else {
            $sql .= $fields;
        }

        $sql .= ' FROM `' . $table . '`';
        $sql .= $this->_format_where($where);
        if ( !is_null($additional) ) {
            $sql .= ' ' . $additional;
        }
        $this->query($sql, $where);
    }

   /**
    *  Parses a WHERE clause, which can be of any of the following formats:
    *  $where = 'id = 3';
    *  (produces ` WHERE id = 3`)
    *  $where = array(
    *      'id'       => 3,
    *      'username' => 'test'
    *  );
    *  (produces ` WHERE id = 3 and username = 'test'`)
    *  $where = array(
    *      'id' => array(
    *          'gte' => 20,
    *          'lt' => 30
    *      ),
    *      'username' => 'test'
    *  );
    *  (produces ` WHERE id >= 20 and id < 30 and username = 'test'`)
    *
    *  @param string|array $where        The clause to be parsed.
    *  @access protected
    *  @return string
    */
    protected function _format_where(&$where = null) {
        $sql = '';

        if ( is_null($where) ) {
            return $sql;
        }

        $sql = ' WHERE ';
        if ( is_string($where) ) {
            $sql .= $where;
        } elseif ( is_array($where) ) {
            foreach ( $where as $name => $val ) {
                if ( is_string($val) ) {
                    $sql .= '`' . $name . '`=:' . $name . ' and ';
                }
                elseif ( is_array($val) ) {
                    foreach ( $val as $sign => $constraint ) {
                        do {
                            $new_name = $name .  '__' . rand();
                        } while ( isset($where[$new_name]) );
                        $sql .=  '`' . $name . '` ';
                        switch ( $sign ) {
                            case 'gt':
                                $sql .= '>';
                                break;
                            case 'gte':
                                $sql .= '>=';
                                break;
                            case 'lt':
                                $sql .= '<';
                                break;
                            case 'lte':
                                $sql .= '<=';
                                break;
                            case 'e':
                            default:
                                $sql .= '=';
                                break;
                        }
                        $sql .= ':' . $new_name . ' and ';
                        $where[$new_name] = $constraint;
                        unset($where[$name]);
                    }
                }
            }
            $sql = substr($sql, 0, strlen($sql) - strlen(' and '));
        }

        return $sql;
    }

   /**
    *  Inserts a record into the database.
    *
    *  @param string $table        The table containing the record to be inserted.
    *  @param array $data          An array containing the data to be inserted. Format
    *                              should be as follows:
    *                              array('column_name' => 'column_value');
    *  @access public
    *  @return bool
    */
    public function insert($table, $data) {
        $sql = 'INSERT INTO `' . $table . '` ';

        $key_names = array_keys($data);
    	$fields = '`' . implode('`, `', $key_names) . '`';
        $values = ':' . implode(', :', $key_names);

    	$sql .= '(' . $fields . ') VALUES (' . $values . ')';

    	$statement = $this->_dbh->prepare($sql);

        try {
            $statement->execute($data);
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }

        return true;
    }

   /**
    *  Executes SQL query.
    *
    *  @param string $sql           The SQL query to be executed.
    *  @param array $parameters     An array containing the parameters to be bound.
    *  @access public
    *  @return bool
    */
    public function query($sql, $parameters = null) {
        $statement = $this->_dbh->prepare($sql);

        if ( is_array($parameters) ) {
            foreach ( $parameters as $field => &$value ) {
                $statement->bindParam(':' . $field, $value);
            }
        }
        try {
            $statement->execute();
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }

        $this->_statement = $statement;
    	return true;
    }

}

?>
