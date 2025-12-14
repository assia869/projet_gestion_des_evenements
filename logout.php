<?php
session_start();
session_destroy();
header('Location: /gestion-evenements/');
exit;
?>