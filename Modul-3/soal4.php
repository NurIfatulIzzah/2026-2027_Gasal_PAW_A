<?php
$height = array(
    "Andy" => "176",
    "Barry" => "165",
    "Charlie" => "170"
);

$height["David"] = "180";
$height["Ella"] = "160";
$height["Fiona"] = "172";
$height["George"] = "168";
$height["Hannah"] = "158";

echo "<h3>3.4.1 Menampilkan seluruh data \$height setelah
penambahan 5 data baru:</h3>";

foreach ($height as $name => $value) {
    echo "Key = " . $name . ", Value = " . $value . " cm<br>";
}

$weight = array(
    "Andy" => 70,
    "Barry" => 65,
    "Charlie" => 68
);

echo "<h3>3.4.2 Menampilkan seluruh data dari array
\$weight:</h3>";

foreach ($weight as $name => $value) {
    echo "Key = " . $name . ", Value = " . $value . " kg<br>";
}
?>