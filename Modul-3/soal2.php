<?php
$age = array(
    "Andy" => 25,
    "Barry" => 30,
    "Charlie" => 22
);

echo "<h3>3.2.2 Menampilkan isi array berdasarkan key</h3>";
echo "Umur Andy adalah " . $age["Andy"] . " tahun<br>";
echo "Umur Barry adalah " . $age["Barry"] . " tahun<br>";
echo "Umur Charlie adalah " . $age["Charlie"] . " tahun<br>";

echo "<h3>3.2.3 Menampilkan isi array dengan foreach</h3>";
foreach ($age as $name => $umur) {
    echo "Nama: " . $name . " - Umur: " . $umur . " tahun<br>";
}
?>