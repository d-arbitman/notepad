<?php

/*
 * class to access PostgreSQL server
 */
class pgsql {

  private $connection;
  private $host;
  private $port;
  private $user;
  private $passwd;
  private $db;
  private $usePersistent;
  private $result;
  private $queryCount;
  private $usedQueries;
  private $connected;
  private $lastQuery;
  private $currentSavePoint;
  private $deallocate;
  private $lastError;

  function __construct ($host ='localhost', $port = 5432, $db = 'postgres', $user = '', $passwd = '', $usePersistent = false, $d = false, $statementPrefix = "sth_") {
    $this->host   = $host;
    $this->port   = $port;
    $this->user   = $user;
    $this->passwd = $passwd;
    $this->db     = $db;
    $this->usePersistent = $usePersistent;
    $this->usedQueries = array ();
    $this->queryCount = 0;
    $this->connected = false;
    $this->currentSavePoint = "";
    $this->deallocate = $d;
    $this->statementPrefix = $statementPrefix;
    $this->lastError = "";
  }

  function connect () {
    if ($this->usePersistent) {
      $this->connection = pg_pconnect ("host = $this->host port = $this->port dbname = $this->db user = $this->user password = $this->passwd connect_timeout = 5");
    } else {
      $this->connection = pg_connect ("host = $this->host port = $this->port dbname = $this->db user = $this->user password = $this->passwd connect_timeout = 10");
    }
    if (!$this->connection) {
      throw new Exception ("Could not connect");
    } else {
      $this->connected = true;
    }
  }
  public function __destruct () {
    if (!$this->usePersistent) {
      $this->disconnect ();
    }
  }

  function disconnect () {
    if ($this->connected) {
      if ($this->deallocate) {
        for ($i = 0; $i<count ($this->usedQueries); $i++) {
          pg_query ($this->connection, "deallocate " . $this->statementPrefix . $this->usedQueries[$i]);
        }
      }
      pg_close ($this->connection);
      $this->connected = false;
    }
  }

  function setDeallocate ($newDeallocate) {
    $this->deallocate = $newDeallocate;
  }

  function prepare ($query, $useTransaction = true) {
    if (!$this->connected) {
      $this->connect ();
    }
    $queryKey = $this->getQueryKey ($query);
    $this->result = false;

    if ($queryKey === "") {
      /*while (!$this->result = pg_prepare ($this->connection, "cause_" . $this->queryCount, $query)) {
        //pg_query ($this->connection, "DEALLOCATE " . $this->statementPrefix . $this->queryCount);
//        $this->queryCount++;
    }*/
    if ($useTransaction) {
      $this->beginTransaction ();
    }
    try {
      $this->result = pg_prepare ($this->connection, $this->statementPrefix . $this->queryCount, $query);
      if (!$this->result) {
        $this->lastError = pg_last_error ($this->connection);
        throw new Exception (__ ("The database query failed: ") . pg_last_error ($this->connection));
      }
      $this->usedQueries[$this->statementPrefix . $this->queryCount] = $query;
      $queryKey = $this->statementPrefix . $this->queryCount;
      } catch (Exception $e) {
        if ($useTransaction) {
          $this->rollback ();
        }
      }
    }
    $this->commit ();
    $this->queryCount++;
    $this->lastQuery = $queryKey;
    return $queryKey;
  }

  function getConnection () {
    return $this->connection;
  }

  function execute ($params = [], $useTransaction = true) {
    $params = to_array ($params);
    try {
      if ($useTransaction) {
        $this->beginTransaction ();
      }
      if (!$this->result = pg_execute ($this->connection, $this->lastQuery, $params)) {
        $this->lastError = pg_last_error ($this->connection);
        throw new Exception (__ ("The database query failed: %[error]", ["error" => pg_last_error ($this->connection)]));
      }
    } catch (Exception $e) {
        $this->rollback ();
    }
    $this->commit ();
    return $this->result;
  }

  function getLastError () {
    return $this->lastError;
  }

  function query ($query, $params = [], $useTransaction = true) {
    if (is_array ($params)) {
      //$p = $params;
    } else if (is_string ($params)) {
      $p[] = $params;
      $params = $p;
    }

    if (!$this->connection) {
      $this->connect ();
    }
    try {
      if ($useTransaction) {
        $this->beginTransaction ();
      }
      /*$queryKey = $this->prepare ($query);
      $this->result = $this->execute ($p);*/

      $this->result = pg_query_params ($this->connection, $query, $params);
      if (!$this->result) {
          $this->lastError = pg_last_error ($this->connection);
          if (preg_match ("/duplicate key value violates/i", pg_last_error ($this->connection))) {
              throw new Exception (__ ("Duplicate entry", []));
          } else {
              throw new Exception (__ ("The database query failed: %[error]" , ["error" => pg_last_error ($this->connection)]));
          }
      }
    } catch (Exception $e) {
      if ($useTransaction) {
        $this->rollback ();
      }

    }
    if ($useTransaction) {
      $this->commit ();
    }
    $this->queryCount++;
    return $this->result;
  }

  function affectedRows () {
    return pg_affected_rows ($this->connection);
  }

  function getQueryKey ($query) {
    foreach ($this->usedQueries as $queryKey  => $q) {
      if ($q === $query) {
        return $queryKey;
      }
    }
    $result = pg_query ($this->connection, "select name from pg_prepared_statements where statement = '" . pg_escape_string($query) . "'");
    if (!$result) {
      return "";
    } else {
      if ($row = pg_fetch_array ($result, null, PGSQL_BOTH)) {
        return $row[0];
      } else {
        return "";
      }
    }
  }

  function getNextInSequence ($id) {
    if (!$this->connected) {
      $this->connect ();
    }
    $res = pg_query_params ($this->connection, "select nextval ($1)", [$id]);
    $row = pg_fetch_array ($res, null, PGSQL_BOTH);
    return $row[0];
  }

  function getNextId () {
    if (!$this->connected) {
      $this->connect ();
    }
    $res = pg_query ($this->connection, "select nextid () as nextid");
    $row = pg_fetch_array ($res, null, PGSQL_BOTH);
    return $row[0];
  }

  function numRows ($r = null) {
    $res = $r ?: $this->result;
    return pg_num_rows ($res);
  }

  function numRowsAffected ($r = null) {
    $res = $r ?: $this->result;
    return pg_affected_rows ($res);
  }

  function fetchObject ($r = null) {
    $res = $r ?: $this->result;
    $row = pg_fetch_object ($res);
    return $row;
  }
  function fetchRow ($r = null) {
    $res = $r ?: $this->result;
    $row = pg_fetch_row ($res);
    return $row;
  }

  function fetchArray ($r = null, $ret_type = PGSQL_BOTH, $character_mask = false) {
    $res = $r ?: $this->result;
    $row = pg_fetch_array ($res, null, $ret_type);
    if (!$row) {
      return $row;
    }
    if ($character_mask === false) {
      if (is_array ($row)) {
        return array_map('trim', $row);
      } else {
        return trim($row);
      }
    } else {
      array_map(function($value) use ($character_mask) {
        $value = trim($value, $character_mask);
      }, $row);
      return $row;
    }
  }

  function fetchArrayNum ($r = null) {
    $res = $r ?: $this->result;
    $row = pg_fetch_array ($res, null, PGSQL_NUM);
    if (is_array ($row)) {
      return array_map ('trim', $row);
    } else {
      return trim ($row);
    }
  }

  /* Return total number of queries executed during lifetime of this object */
  function numQueries () {
    return $this->querycount;
  }

  /* Return the number of fields in a result set */
  function numberFields ($r = null) {
    $res = $r ?: $this->result;
    $count = pg_num_fields ($res);
    return $count;
  }

  /* Return a field name given an integer offse. */
  function fieldName ($offset) {
    $field = pg_field_name ($this->result, $offset);
    return $field;
  }

  function getResultAsTable () {
    if ($this->numrows () > 0) {

      $resultHTML = "<table>\n<tr>";

      $fieldCount = $this->numberFields ();
      for ($i = 0; $i < $fieldCount; $i++) {
        $rowName     = $this->fieldName ($i);
        $sqlPosition = $i + 1;
        //$resultHTML .=  "<th><a href = \"" . $_SERVER['PHP_SELF'] . "?sort = $rowName\">$rowName</a></th>";
        $resultHTML .=  "<th>$rowName</th>";
      }

      $resultHTML .=  "</tr>\n";

      while ($row = $this->fetchRow ()) {
        $resultHTML .=  "<tr>";
        for ($i = 0; $i < $fieldCount; $i++)
          $resultHTML .=  "<td>" . htmlentities ($row[$i]) . "</td>";
        $resultHTML .=  "</tr>\n";
      }
      $resultHTML .=  "</table>";
    } else {
      $resultHTML = __ ("No results found", [], "error");
    }
    return $resultHTML;
  }

  function pageLinks ($totalpages, $currentpage, $pagesize, $parameter) {
    // SELECT select_list
    // FROM table_expression
    // [ ORDER BY ... ]
    // [ LIMIT { number | ALL } ] [ OFFSET number ]
    $page        = 1;
    $recordstart = 0;
    $pageLinks   = "";
    while ($page <=  $totalpages) {
      if ($page !=  $currentpage) {
        $pageLinks .=  "<a href = \"" . $_SERVER['PHP_SELF'] . "?$parameter = $recordstart\">$page</a> ";
      } else {
        $pageLinks .=  "$page ";
      }
      $recordstart +=  $pagesize;
      $page++;
    }
    return $pageLinks;
  }

  function beginTransaction () {
    pg_query($this->connection, 'BEGIN TRANSACTION;');
    $this->inTransaction = 1;
  }

  function commit () {
    pg_query($this->connection, 'COMMIT;');
    $this->inTransaction = 0;
  }

  function rollback () {
    pg_query($this->connection, 'ROLLBACK;');
  }

  function setSavePoint ($savePointName) {
    $this->query ("SAVEPOINT $savePointName");
    $this->currentSavePoint = $savePointName;
  }

  function rollbackToSavePoint ($savePointName) {
    $this->query ("ROLLBACK TO SAVEPOINT $savePointName");
  }

  function setTransactionParamsSerialisable () {
    $this->query ("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    $this->inTransaction = "SERIALIZABLE";
  }

  function setTransactionParamsReadCommited () {
      $this->query ("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
      $this->inTransaction = "READ COMMITTED";
  }
  function isConnected () {
    return ($this->connection!== null);
  }

  // The __call magic method is called whenever an unknown method for the instance is called.
  /*function __call ($fname, $fargs) {
    $statement = $fname . '__' . count ($fargs);
    if (!in_array ($statement, $this->usedQueries)) {
      $alist = array ();
      for ($i = 1; $i <=  count ($fargs); $i++) {
        $alist[$i] = '$' . $i;
      }
      $sql = 'select * from ' . $fname . ' (' . implode (',', $alist) . ')';
      $prep = pg_prepare ($this->connection, $statement, $sql);
      $this->usedQueries[] = $statement;
    }

    if ($this->result = pg_execute ($this->connection, $statement, $fargs)) {
      $rows = pg_num_rows ($res);
      $cols = pg_num_fields ($res);
      if ($cols > =  1) return $res; // return the cursor if more than 1 col
        else if ($rows = =  0) return null;
//        else if ($rows = =  1) return pg_fetch_result ($res, 0); // single result
        else return pg_fetch_all_columns ($res, 0); // get column as an array
    }
  }*/
}
