<?php

// Copied from 0816_json_decode_objects.php

$content = json_decode($_GET['whatever']);
$content_type = is_object($content) ? $content->content_type : $content[0]->content_type;
