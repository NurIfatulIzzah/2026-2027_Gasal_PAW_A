<?php
$students = array(
    array("Alex", "220401", "0812345678"),
    array("Bianca", "220402", "0812345687"),
    array("Candice", "220403", "0812345665")
);

$students[] = array("Darren", "220404", "0812345699");
$students[] = array("Ella", "220405", "0812345601");
$students[] = array("Farah", "220406", "0812345612");
$students[] = array("Gavin", "220407", "0812345623");
$students[] = array("Hanna", "220408", "0812345634");

echo "<h2>Daftar Data Mahasiswa</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr style='background-color: #f2f2f2; font-weight: bold;'>
        <td>No</td>
        <td>Nama</td>
        <td>NIM</td>
        <td>Nomor HP</td>
      </tr>";

for ($row = 0; $row < count($students); $row++) {
    echo "<tr>";
    echo "<td>" . ($row + 1) . "</td>"; // nomor urut
    for ($col = 0; $col < count($students[$row]); $col++) {
        echo "<td>" . $students[$row][$col] . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>