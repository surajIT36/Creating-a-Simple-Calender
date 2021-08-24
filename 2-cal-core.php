<?php
class Calendar {
  // (A) CONSTRUCTOR - CONNECT TO DATABASE
  private $pdo = null;
  private $stmt = null;
  public $error = "";
  function __construct(){
    try {
      $this->pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET, 
        DB_USER, DB_PASSWORD, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
      );
    } catch (Exception $ex) { die($ex->getMessage()); }
  }

  // (B) DESTRUCTOR - CLOSE DATABASE CONNECTION
  function __destruct(){
    if ($this->stmt!==null) { $this->stmt = null; }
    if ($this->pdo!==null) { $this->pdo = null; }
  }

  // (C) SAVE EVENT
  function save ($start, $end, $txt, $color, $id=null) {
    // (C1) START & END DATE QUICK CHECK 
    $uStart = strtotime($start);
    $uEnd = strtotime($end);
    if ($uEnd < $uStart) {
      $this->error = "End date cannot be earlier than start date";
      return false;
    }

    // (C2) SQL - INSERT OR UPDATE
    if ($id==null) {
      $sql = "INSERT INTO `events` (`evt_start`, `evt_end`, `evt_text`, `evt_color`) VALUES (?,?,?,?)";
      $data = [$start, $end, $txt, $color];
    } else {
      $sql = "UPDATE `events` SET `evt_start`=?, `evt_end`=?, `evt_text`=?, `evt_color`=? WHERE `evt_id`=?";
      $data = [$start, $end, $txt, $color, $id];
    }

    // (C3) EXECUTE
    try {
      $this->stmt = $this->pdo->prepare($sql);
      $this->stmt->execute($data);
    } catch (Exception $ex) {
      $this->error = $ex->getMessage();
      return false;
    }
    return true;
  }

  // (D) DELETE EVENT
  function del ($id) {
    try {
      $this->stmt = $this->pdo->prepare("DELETE FROM `events` WHERE `evt_id`=?");
      $this->stmt->execute([$id]);
    } catch (Exception $ex) {
      $this->error = $ex->getMessage();
      return false;
    }
    return true;
  }

  // (E) GET EVENTS FOR SELECTED MONTH
  function get ($month, $year) {
    // (E1) FIST & LAST DAY OF MONTH
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dayFirst = "{$year}-{$month}-01";
    $dayLast = "{$year}-{$month}-{$daysInMonth}";

    // (E2) GET EVENTS
    $this->stmt = $this->pdo->prepare("SELECT * FROM `events`
    WHERE `evt_start` BETWEEN ? AND ?
    OR `evt_end` BETWEEN ? AND ?");
    $this->stmt->execute([$dayFirst, $dayLast, $dayFirst, $dayLast]);
    
    /* $events = [
     *  "e" => [
     *    EVENT ID => [DATA],
     *    EVENT ID => [DATA],
     *    ...
     *  ],
     *  "d" => [
     *    DAY => [EVENT IDS],
     *    DAY => [EVENT IDS],
     *    ...
     *  ]
     * ]
     */
    $events = ["e" => [], "d" => []];
    while ($row = $this->stmt->fetch()) {
      $eStartMonth = substr($row['evt_start'], 5, 2);
      $eEndMonth = substr($row['evt_end'], 5, 2);
      $eStartDay = $eStartMonth==$month 
                 ? (int)substr($row['evt_start'], 8, 2) 
                 : 1 ;
      $eEndDay = $eEndMonth==$month 
               ? (int)substr($row['evt_end'], 8, 2) 
               : $daysInMonth ;
      for ($d=$eStartDay; $d<=$eEndDay; $d++) {
        if (!isset($events['d'][$d])) { $events['d'][$d] = []; }
        $events['d'][$d][] = $row['evt_id'];
      }
      $events['e'][$row['evt_id']] = $row;
      $events['e'][$row['evt_id']]['first'] = $eStartDay;
    }
    return $events;
  }
}

// (F) DATABASE SETTINGS - CHANGE TO YOUR OWN!
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_CHARSET', 'utf8');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// (G) NEW CALENDAR OBJECT
$CAL = new Calendar();