<?php
$conn = new mysqli("127.0.0.1", "root", "wINDEX12!", "ss_wellington_local");
echo $conn->connect_error ? $conn->connect_error : "Connected OK";
