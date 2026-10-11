<?php
$fruits = array("Apple", "Banana", "Cherry");
echo "<h3>Array Awal:</h3>";
print_r($fruits);

array_push($fruits, "Durian", "Mango");
echo "<h3>Setelah array_push():</h3>";
print_r($fruits);

$moreFruits = array("Orange", "Papaya");
$merged = array_merge($fruits, $moreFruits);
echo "<h3>Setelah array_merge() dengan array baru:</h3>";
print_r($merged);

$assocArray = array("a" => "Avocado", "b" => "Blueberry", "c" =>
"Coconut");
$values = array_values($assocArray);
echo "<h3>Hasil array_values():</h3>";
print_r($values);

$searchItem = "Mango";
$position = array_search($searchItem, $merged);

echo "<h3>Hasil array_search() untuk '$searchItem':</h3>";
if ($position !== false) {
    echo "Elemen '$searchItem' ditemukan pada indeks ke-
$position.";
} else {
    echo "Elemen '$searchItem' tidak ditemukan.";
}

echo "<h3>Hasil array_filter():</h3>";
$longNamedFruits = array_filter($merged, function($fruit) {
    return strlen($fruit) > 5;
});
print_r($longNamedFruits);

echo "<h3>Hasil berbagai fungsi sorting:</h3>";

$sorted = $merged;
sort($sorted);
echo "sort() - Urutan menaik: ";
print_r($sorted);

$rsorted = $merged;
rsort($rsorted);
echo "<br>rsort() - Urutan menurun: ";
print_r($rsorted);

$asorted = $merged;
asort($asorted);
echo "<br>asort() - Urutan menaik (menjaga indeks): ";
print_r($asorted);

$arsorted = $merged;
arsort($arsorted);
echo "<br>arsort() - Urutan menurun (menjaga indeks): ";
print_r($arsorted);
?>