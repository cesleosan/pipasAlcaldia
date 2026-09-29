<?php
declare(strict_types=1);
final class Database {
    public static function connect(array $c, bool $selectDatabase = true): PDO {
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $c['db_name'])) throw new RuntimeException('Nombre de base inválido.');
        $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};charset=utf8mb4".($selectDatabase ? ";dbname={$c['db_name']}" : '');
        $db = new PDO($dsn,$c['db_user'],$c['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
        $db->exec("SET time_zone = '-06:00'");
        return $db;
    }
    public static function query(PDO $db,string $sql,array $params=[]): PDOStatement { $s=$db->prepare($sql); $s->execute($params); return $s; }
    public static function transaction(PDO $db, callable $fn): mixed {
        $db->beginTransaction();
        try { $result=$fn(); $db->commit(); return $result; }
        catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }
}
