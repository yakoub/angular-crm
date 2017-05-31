<?php
include 'dal.first_grade.php';
//print_r(Dal\listVideos(0));
$dbh = Dal\Connection();
print_r($dbh->getAttribute(\PDO::ATTR_CASE));
