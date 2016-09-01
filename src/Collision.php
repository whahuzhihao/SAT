<?php
namespace SAT;

use SAT\Entity\Circle;
use SAT\Entity\Polygon;
use SAT\Entity\Response;
use SAT\Entity\Vector;
use SAT\Entity\Box;

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
        $differenceV = clone $p;
        $differenceV->sub($c->pos);
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
        $differenceV = clone ($b->pos);
        $differenceV->sub($a->pos);
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
            $response->overlapV = clone $differenceV;
            $response->overlapV->scale($response->overlap);
            $response->aInB = $a->r <= $b->r && $dist <= $b->r - $a->r;
            $response->bInA = $b->r <= $a->r && $dist <= $a->r - $b->r;
        }
        return true;
    }

    /**
     * Check if a polygon and a circle collide.
     * @param Polygon $polygon
     * @param Circle $circle
     * @param Response $response
     * @return bool
     */
    public static function testPolygonCircle(Polygon $polygon, Circle $circle, Response &$response)
    {
        $circlePos = clone ($circle->pos);
        $circlePos->sub($polygon->pos);
        $radius = $circle->r;
        $radius2 = $radius * $radius;
        $points = $polygon->calcPoints;
        $len = count($points);
        for ($i = 0; $i < $len; $i++) {
            $next = $i === $len - 1 ? 0 : $i + 1;
            $prev = $i === 0 ? $len - 1 : $i - 1;
            $overlap = 0;
            $overlapN = null;
            $edge = clone $polygon->edges[$i];
            $point = clone $circlePos;
            $point->sub($points[$i]);
            if ($response && $point->len2() > $radius2) {
                $response->aInB = false;
            }

            $region = Helper::voronoiRegion($edge, $point);
            if ($region === Helper::LEFT_VORONOI_REGION) {
                $edge = clone $polygon->edges[$prev];
                $point2 = clone $circlePos;
                $point2->sub($points[$prev]);
                $region = Helper::voronoiRegion($edge, $point2);
                if ($region === Helper::RIGHT_VORONOI_REGION) {
                    $dist = $point->len();
                    if ($dist > $radius) {
                        return false;
                    } else if ($response) {
                        $response->bInA = false;
                        $overlapN = $point->normalize();
                        $overlap = $radius - $dist;
                    }
                }
            } else if ($region === Helper::RIGHT_VORONOI_REGION) {
                $edge = clone $polygon->edges[$next];
                $point = clone $circlePos;
                $point->sub($points[$next]);
                $region = Helper::voronoiRegion($edge, $point);
                if ($region === Helper::LEFT_VORONOI_REGION) {
                    $dist = $point->len();
                    if ($dist > $radius) {
                        return false;
                    } else if ($response) {
                        $response->bInA = false;
                        $overlapN = $point->normalize();
                        $overlap = $radius - $dist;
                    }
                }
            } else {
                $normal = $edge->perp()->normalize();
                $dist = $point->dot($normal);
                $distAbs = abs($dist);
                if ($dist > 0 && $distAbs > $radius) {
                    return false;
                } else if ($response) {
                    $overlapN = $normal;
                    $overlap = $radius - $dist;
                    if ($dist >= 0 || $overlap < 2 * $radius) {
                        $response->bInA = false;
                    }
                }
            }
            if ($overlapN && $response && abs($overlap) < abs($response->overlap)) {
                $response->overlap = $overlap;
                $response->overlapN = clone $overlapN;
            }
        }
        if ($response) {
            $response->a = $polygon;
            $response->b = $circle;
            $response->overlapV = clone $response->overlapN;
            $response->overlapV->scale($response->overlap);
        }
        return true;
    }

    /**
     * Check if a circle and a polygon collide.
     * @param Circle $circle
     * @param Polygon $polygon
     * @param Response $response
     * @return mixed
     */
    public static function testCirclePolygon(Circle $circle, Polygon $polygon, Response &$response)
    {
        $result = self::testPolygonCircle($polygon, $circle, $response);
        if ($result && $response) {
            $a = $response->a;
            $aInB = $response->aInB;
            $response->overlapN->reverse();
            $response->overlapV->reverse();
            $response->a = $response->b;
            $response->b = $a;
            $response->aInB = $response->bInA;
            $response->bInA = $aInB;
        }
        return $result;
    }

    /**
     * Checks whether polygons collide.
     * @param Polygon $a
     * @param Polygon $b
     * @param Response $response
     * @return bool
     */
    public static function testPolygonPolygon(Polygon $a, Polygon $b, Response &$response)
    {
        $aPoints = $a->calcPoints;
        $aLen = count($aPoints);
        $bPoints = $b->calcPoints;
        $bLen = count($bPoints);
        for ($i = 0; $i < $aLen; $i++) {
            if (Helper::isSeparatingAxis($a->pos, $b->pos, $aPoints, $bPoints, $a->normals[$i], $response)) {
                return false;
            }
        }
        for ($i = 0; $i < $bLen; $i++) {
            if (Helper::isSeparatingAxis($a->pos, $b->pos, $aPoints, $bPoints, $b->normals[$i], $response)) {
                return false;
            }
        }
        if ($response) {
            $response->a = $a;
            $response->b = $b;
            $response->overlapV = clone $response->overlapN;
            $response->overlapV->scale($response->overlap);
        }
        return true;
    }
}