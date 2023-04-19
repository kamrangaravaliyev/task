<?php

$dbHost = 'db';
$dbName = 'test_db';
$dbPass = 'devpass';
$dbUser = 'devuser';
$csvFile = 'dataset.csv';


$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (($handle = fopen($csvFile, "r")) !== FALSE) {

    $stmt = $pdo->prepare("INSERT INTO clients (category, firstname, lastname, email, gender, birthdate) VALUES (:category, :firstname, :lastname, :email, :gender, :birthdate)");
    $batchSize = 1000;
    $rowCount = 0;
    $batchValues = array();
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $batchValues[] = array(
            'category' => $data[0],
            'firstname' => $data[1],
            'lastname' => $data[2],
            'email' => $data[3],
            'gender' => $data[4],
            'birthdate' => $data[5],
        );
        $rowCount++;
        if ($rowCount % $batchSize == 0) {

            foreach ($batchValues as $rowValues) {
                $stmt->execute($rowValues);
            }
            $batchValues = array();
        }
    }
    fclose($handle);

    if (!empty($batchValues)) {
        foreach ($batchValues as $rowValues) {
            $stmt->execute($rowValues);
        }
    }
}

$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$category = isset($_GET['category']) ? $_GET['category'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$date_of_birth = isset($_GET['date_of_birth']) ? $_GET['date_of_birth'] : '';
$age = isset($_GET['age']) ? $_GET['age'] : '';
$age_range_min = isset($_GET['age_range_min']) ? $_GET['age_range_min'] : '';
$age_range_max = isset($_GET['age_range_max']) ? $_GET['age_range_max'] : '';

// Query to get total number of records
$sql = "SELECT COUNT(*) as total FROM clients 
        WHERE category LIKE '%$category%' 
        AND gender LIKE '%$gender%' 
        AND date_of_birth LIKE '%$date_of_birth%' 
        AND (YEAR(CURDATE()) - YEAR(date_of_birth)) = '$age' 
        AND (YEAR(CURDATE()) - YEAR(date_of_birth)) BETWEEN '$age_range_min' AND '$age_range_max'";
$stmt = $pdo->query($sql);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_records = $data['total'];


$sql = "SELECT * FROM clients 
        WHERE category LIKE '%$category%' 
        AND gender LIKE '%$gender%' 
        AND date_of_birth LIKE '%$date_of_birth%' 
        AND (YEAR(CURDATE()) - YEAR(date_of_birth)) = '$age' 
        AND (YEAR(CURDATE()) - YEAR(date_of_birth)) BETWEEN '$age_range_min' AND '$age_range_max' 
        LIMIT $start, $limit";
$stmt = $pdo->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<thead><tr><th>ID</th><th>Name</th><th>Gender</th><th>Date of Birth</th><th>Category</th></tr></thead>";
echo "<tbody>";
foreach ($clients as $client) {
    echo "<tr>";
    echo "<td>" . $client['id'] . "</td>";
    echo "<td>" . $client['name'] . "</td>";
    echo "<td>" . $client['gender'] . "</td>";
    echo "<td>" . $client['date_of_birth'] . "</td>";
    echo "<td>" . $client['category'] . "</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";


$total_pages = ceil($total_records / $limit);
echo "<div class='pagination'>";
for ($i = 1; $i <= $total_pages; $i++) {
    $active = ($i == $page) ? "active" : "";
    echo "<a href='?page=$i' class='$active'>$i</a>";
}
echo "</div>";
