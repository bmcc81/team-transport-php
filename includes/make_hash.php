<?php
echo password_hash($argv[1] ?? 'admin123', PASSWORD_DEFAULT) . PHP_EOL;