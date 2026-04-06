<?php
$conn = pg_connect("host=127.0.0.1 port=5432 dbname=postgres user=postgres password=mydb171819");
$dbs = [];
$result = pg_query($conn, "SELECT datname FROM pg_database WHERE datistemplate = false");
while ($row = pg_fetch_assoc($result)) {
    $dbs[] = $row['datname'];
}
pg_close($conn);

echo "Scanning databases for ffactures data...\n";
foreach ($dbs as $db) {
    $c = @pg_connect("host=127.0.0.1 port=5432 dbname=$db user=postgres password=mydb171819");
    if ($c) {
        $res = @pg_query($c, "SELECT count(*) FROM ffactures");
        if ($res) {
            $row = pg_fetch_row($res);
            echo "Database [$db] -> ffactures count: " . $row[0] . "\n";
        }
        
        $res2 = @pg_query($c, "SELECT count(*) FROM fbls");
        if ($res2) {
            $row = pg_fetch_row($res2);
            echo "Database [$db] -> fbls count: " . $row[0] . "\n";
        }
        pg_close($c);
    }
}
