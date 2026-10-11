<?php
$fruits = array("Apple", "Banana", "Orange");

echo "<h3>3.1.2 Menampilkan Isi Array</h3>";
echo "Buah pertama adalah: " . $fruits[0] . "<br>";
echo "Buah kedua adalah: " . $fruits[1] . "<br>";
echo "Buah ketiga adalah: " . $fruits[2] . "<br>";

echo "<h3>3.1.3 Menampilkan Isi Array dengan foreach</h3>";
foreach ($fruits as $fruit) {
    echo $fruit . "<br>";
}
?>