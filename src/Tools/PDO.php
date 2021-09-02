<?php

namespace SITE\Tools;

use PDO as PDOphp;

class PDO
{
    private $dbs     = [];
    private $options_persistent = [
        PDOphp::ATTR_ERRMODE            => PDOphp::ERRMODE_EXCEPTION,
        PDOphp::ATTR_PERSISTENT         => true,
        PDOphp::ATTR_EMULATE_PREPARES   => false,
        PDOphp::ATTR_DEFAULT_FETCH_MODE => PDOphp::FETCH_ASSOC,
    ];
    private $options = [
        PDOphp::ATTR_ERRMODE            => PDOphp::ERRMODE_EXCEPTION,
        PDOphp::ATTR_EMULATE_PREPARES   => false,
        PDOphp::ATTR_DEFAULT_FETCH_MODE => PDOphp::FETCH_ASSOC,
    ];
    private $history = [];
    private $devmode;
    // Av p� med persistent connections
    private $persistent_mode = true;
    public function __construct($devmode = false)
    {
        $this->devmode = $devmode;
    }
    public function __destruct()
    {
        if (!$this->persistent_mode) {
            foreach ($this->dbs as $key => $db) {
                $this->dbs[$key] = null;
            }
        }
    }
    public function lastInsertId($db)
    {
        $pdo = $this->getPDO($db);
        return $pdo->lastInsertId();
    }
    // Returnerar totalen rader. OBS! SQL_CALC_FOUND_ROWS m�ste ligga med direkt efter SELECT, annars �r den baserad p� LIMIT
    public function totalRows($db)
    {
        $pdo = $this->getPDO($db);
        return $pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    }
    // Querylog i devl�ge
    public function getLog()
    {
        return $this->history;
    }
    /**
     * N�r man beh�ver ha en IN() s� kan denna skapa in-variabler samt key/value-pair som beh�vs f�r statment
     * query l�gger du inne i IN() och param k�r du array_merge p� params.
     *
     * @param [type] $array
     *
     * @return array
     */
    public function inStatment($array)
    {
        $in    = [];
        $inkey = 'a' . uniqid();
        foreach ($array as $key => $val) {
            $in[$inkey . $key] = $val;
        }
        return [
            'query' => ':' . implode(',:', array_keys($in)),
            'param' => $in,
        ];
    }
    /**
     * Genererar en update-query av dom f�lt som skickas in, s� att man inte skriver �ver andra f�lt man inte skickar in.
     *
     * @param string $db
     * @param string $table
     * @param array  $data
     *
     * @return array
     */
    public function updateStatement($db, $table, $data)
    {
        if (empty($table) || empty($data) || empty($db)) {
            throw new \Exception('Missing parameters, table/data', 1);
        }
        // H�mta info om table
        $desc_res    = $this->run($db, "DESCRIBE `{$table}`")->fetchAll();
        $description = [];
        foreach ($desc_res as $col) {
            $description[$col['Field']] = $col;
        }
        $filtered_data = [];
        $duplicate     = '';
        // Filtrera ut bara dom columner som finns
        foreach ($data as $key => $val) {
            if (in_array($key, array_keys($description))) {
                $filtered_data[$key] = $val;
                // f�r att den inte ska uppdatera ID vid update
                if ($description[$key]['Extra'] !== 'auto_increment') {
                    $duplicate .= '`' . $key . '`' . ' = VALUES(`' . $key . "`),\n";
                }
            }
        }
        $keys      = '`' . implode('`,`', array_keys($filtered_data)) . '`';
        $values    = ':' . implode(',:', array_keys($filtered_data));
        $duplicate = rtrim($duplicate, ",\n");
        $query = "INSERT INTO `{$table}` ({$keys}) \n VALUES ({$values})\n ON DUPLICATE KEY UPDATE \n {$duplicate}";
        return [
            'keys'   => array_keys($filtered_data),
            'query'  => $query,
            'values' => $filtered_data,
        ];
    }
    // K�r query returnar ett statement s� de �r bara kjedja p� typ fetch() eller fetchAll() fetchObject() osv.
    public function run($db, $query, $args = [])
    {
        if ($this->devmode) {
            $this->history[] = [
                'db'    => $db,
                'query' => $this->debug($db, $query, $args),
            ];
        }
        if (!$args) {
            return $this->query($db, $query);
        }
        $pdo  = $this->getPDO($db);
        $stmt = $pdo->prepare($query);
        $stmt->execute($args);
        return $stmt;
    }
    // Prepare query f�r att tex. k�ra multi-line inserts
    public function prep($db, $query)
    {
        if ($this->devmode) {
            $this->history[] = $this->debug($db, $query, []);
        }
        $pdo  = $this->getPDO($db);
        return $pdo->prepare($query);
    }
    // Slaskfunktion f�r att debugga
    public function debug($db, $query, array $params = null)
    {
        if (!empty($params)) {
            $indexed = $params == array_values($params);
            foreach ($params as $k => $v) {
                if (is_object($v)) {
                    if ($v instanceof \DateTime) {
                        $v = $v->format('Y-m-d H:i:s');
                    } else {
                        continue;
                    }
                } elseif (is_string($v)) {
                    $v = "'{$v}'";
                } elseif ($v === null) {
                    $v = 'NULL';
                } elseif (is_array($v)) {
                    $v = implode(',', $v);
                }
                if ($indexed) {
                    $query = preg_replace('/\?/', $v, $query, 1);
                } else {
                    if ($k[0] != ':') {
                        $k = ':' . $k;
                    }
                    //add leading colon if it was left out
                    $query = str_replace($k, $v, $query);
                }
            }
        }
        // Finns parameter som inte matchat
        // if (preg_match('/=\s?:/', $query)) {
        //     echo '<big><strong style="color:red">FINNS PARAMTER SOM EJ MATCHAT</strong></big><br>';
        // }
        // $msg = '<h1>' . $db . '</h1>';
        // $msg .= '<pre>' . trim($query) . '</pre>';
        return trim($query);
    }
    // H�mtar r�tt db f�r query
    private function getPDO($db)
    {
        if (empty($db)) {
            throw new \Exception('No Db set');
        }
        if (!empty($this->dbs[$db])) {
            return $this->dbs[$db];
        }
        // templ�sning f�r MYSQL_MASTER_HOST har p:
        // $host = '192.168.0.1';
        $host = str_replace('p:', '', MYSQL_MASTER_HOST);
        $dsn            = 'mysql:host=' . $host . ';dbname=' . $db . ';charset=latin1';
        $options        = $this->persistent_mode ? $this->options_persistent : $this->options;
        $this->dbs[$db] = new PDOphp($dsn, MYSQL_MASTER_USER, MYSQL_MASTER_PASSWORD, $options);
        return $this->dbs[$db];
    }
    // Intern query
    private function query($db, $query)
    {
        $pdo = $this->getPDO($db);
        return $pdo->query($query);
    }
}