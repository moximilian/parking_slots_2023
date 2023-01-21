<?php
require("session.php");
session_destroy();
header('Location: auth.php');
?>