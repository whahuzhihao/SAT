<?php
namespace SAT;

use SAT\Entity\Circle;
use SAT\Entity\Polygon;
use SAT\Entity\Response;
use SAT\Entity\Vector;

abstract class Collision
{
    /**
     * Check if a point is inside a circle.
     * @param Vector $p
     * @param Circle $c
     * @return bool
     */
    public static function pointInCircle(Vector $p, Circle $c)
    {
        $tempP = clone $p;
        $differenceV = $tempP->sub($c->pos);
        $radiusSq = $c->r * $c->r;
        $distanceSq = $differenceV->len2();
        return $distanceSq <= $radiusSq;
    }

    /**
     * Check if a point is inside a convex polygon.
     * @param Vector $p
     * @param Polygon $poly
     * @return mixed
     */
    public static function pointInPolygon(Vector $p, Polygon $poly)
    {
        $UNIT_SQUARE = (new Box(new Vector(), 1, 1))->toPolygon();
        $UNIT_SQUARE->pos = clone $p;
        $T_RESPONSE = new Response();
        $result = self::testPolygonPolygon($UNIT_SQUARE, $poly, $T_RESPONSE);
        if ($result) {
            $result = $T_RESPONSE->aInB;
        }
        return $result;
    }

    /**
     * Check if two circles collide.
     * @param Circle $a
     * @param Circle $b
     * @param Response $response
     * @return bool
     */
    public static function testCircleCircle(Circle $a, Circle $b, Response &$response)
    {
        $tempB = clone ($b->pos);
        $differenceV = $tempB->sub($a->pos);
        $totalRadius = $a->r + $b->r;
        $totalRadiusSq = $totalRadius * $totalRadius;
        $distanceSq = $differenceV->len2();

        if ($distanceSq > $totalRadiusSq) {
            return false;
        }

        if ($response) {
            $dist = sqrt($distanceSq);
            $response->a = $a;
            $response->b = $b;
            $response->overlap = $totalRadius - $dist;
            $response->overlapN = clone ($differenceV->normalize());
            $tempV = clone $differenceV;
            $response->overlapV = $tempV->scale($response->overlap);
            $response->aInB = $a->r <= $b->r && $dist <= $b->r - $a->r;
            $response->bInA = $b->r <= $a->r && $dist <= $a->r - $b->r;
        }
        return true;
    }
}