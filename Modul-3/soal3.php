<?php
$height = array(
    "Andy" => "176",
    "Barry" => "165",
    "Charlie" => "170"
);

$height["Diana"] = "160";
$height["Eka"] = "172";
$height["Farah"] = "168";
$height["Gilang"] = "175";
$height["Hani"] = "162";

$keys = array_keys($height);
$lastKey = end($keys);
echo "Nilai dengan indeks terakhir dari \$height adalah: " .
$height[$lastKey] . " cm.<br><br>";

unset($height["Barry"]);

$keys = array_keys($height);
$lastKey = end($keys);
echo "Setelah menghapus 'Barry', nilai dengan indeks terakhir
adalah: " . $height[$lastKey] . " cm.<br><br>";

$weight = array(
    "Andy" => "65",
    "Charlie" => "70",
    "Diana" => "55"
);

$values = array_values($weight);
echo "Data ke-2 dari array \$weight adalah: " . $values[1] . "
kg.";
?>