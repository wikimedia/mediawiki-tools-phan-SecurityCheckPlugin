<?php

// Should be safe
echo (int)$_GET['foo'];

// Less so
echo (string)$_GET['bar'];
