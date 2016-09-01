<?php
include 'vendor/autoload.php';

use SAT\Entity\Circle;
use SAT\Entity\Polygon;
use SAT\Entity\Response;
use SAT\Entity\Vector;
use SAT\Collision;

function dumpR(Response $r){
    echo "overlap:{$r->overlap} overlapV:[{$r->overlapV->x},{$r->overlapV->y}] overlapN:[{$r->overlapN->x},{$r->overlapN->y}] aInB:".($r->aInB?1:0)." bInA:".($r->bInA?1:0)."\n";
}

//pointInCircle
$point = new Vector(1, 1);
$circle = new Circle(new Vector(1, 1), 1);
var_dump(Collision::pointInCircle($point, $circle));

$point = new Vector(1, 2.1);
$circle = new Circle(new Vector(1, 1), 1);
var_dump(Collision::pointInCircle($point, $circle));

//pointInPolygon
$point = new Vector(1, 2.1);
$polygon = new Polygon(null, array(new Vector(3,1), new Vector(3,2), new Vector(2,3), new Vector(1,2),new Vector(1,1),new Vector(2,0)));
var_dump(Collision::pointInPolygon($point, $polygon));

$point = new Vector(0.9, 1);
var_dump(Collision::pointInPolygon($point, $polygon));

//testCircleCircle
$c1 = new Circle(new Vector(1, 1), 1);
$c2 = new Circle(new Vector(2, 2), 1);
$r = new Response();
var_dump(Collision::testCircleCircle($c1, $c2, $r));
dumpR($r);
$c3 = new Circle(new Vector(3, 1), 0.9);
var_dump(Collision::testCircleCircle($c1, $c3, $r));

$c4 = new Circle( new Vector(0.9,1), 0.1);
var_dump(Collision::testCircleCircle($c1, $c4, $r));
dumpR($r);

//testPolygonCircle
$p1 = new Polygon(new Vector(-1, 0), array(new Vector(3,1), new Vector(3,2), new Vector(2,3), new Vector(1,2),new Vector(1,1),new Vector(2,0)));
$c1 = new Circle(new Vector(0,0), 1/sqrt(2));
$r->clear();
var_dump(Collision::testPolygonCircle($p1, $c1, $r));
dumpR($r);
