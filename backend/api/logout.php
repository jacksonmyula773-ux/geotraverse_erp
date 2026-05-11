<?php
// backend/api/logout.php
session_start();
session_destroy();
sendResponse(true, null, "Logged out successfully");
?>