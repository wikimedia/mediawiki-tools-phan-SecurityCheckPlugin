<?php

$escaped = $_GET['a'] |> htmlspecialchars(...);
echo $escaped; // Safe
echo htmlspecialchars( $escaped ); // DoubleEscaped

$shellRes = $_GET['cmd'] |> shell_exec(...); // ShellInjection
echo $shellRes; // XSS
