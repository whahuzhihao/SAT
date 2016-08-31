<?php
include 'vendor/autoload.php';

$p = new SAT\Entity\Vector(1,2);
$c = new SAT\Entity\Circle(new SAT\Entity\Vector(1,1), 0.5);
var_dump(SAT\Collision::pointInCircle($p, $c));