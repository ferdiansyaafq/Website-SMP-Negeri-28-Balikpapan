<?php
$password_baru = "198501012010011001";
$hash = password_hash($password_baru, PASSWORD_DEFAULT);

echo "Password yang akan kamu ketik saat login: <b>" . $password_baru . "</b><br><br>";
echo "Copy teks hasil hash di bawah ini (pastikan nge-blok dari lambang $ sampai akhir, jangan ada spasi lebih):<br>";
echo "<br><b>" . $hash . "</b>";
?>