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
    public static function pointInPolygon(Vector $point, Polygon $poly)
    {
        //BUG CODE
//        $UNIT_SQUARE = (new Box(new Vector(), 1, 1))->toPolygon();
//        $UNIT_SQUARE->pos = clone $point;
//        $T_RESPONSE = new Response();
//        $result = self::testPolygonPolygon($UNIT_SQUARE, $poly, $T_RESPONSE);
//        if ($result) {
//            $result = $T_RESPONSE->aInB;
//        }
//        return $result;
        $len = count($poly->calcPoints);
        $p = clone $point;
        $p->sub($poly->pos);
        $j = $len - 1;
        $flag = false;
        for ($i = 0; $i < $len; $i++) {
            $p1 = $poly->calcPoints[$i];
            $p2 = $poly->calcPoints[$j];
            if ($p1->x == $p->x && $p1->y == $p->y) {
                return true;
            }
            if ($p2->x == $p->x && $p2->y == $p->y) {
                return true;
            }
            // 判断线段两端点是否在射线两侧
            if (($p1->y < $p->y && $p2->y >= $p->y) || ($p1->y >= $p->y && $p2->y < $p->y)) {
                // 线段上与射线 Y 坐标相同的点的 X 坐标
                $x = $p1->x + ($p->y - $p1->y) * ($p2->x - $p1->x) / ($p2->y - $p1->y);
                // 点在多边形的边上
                if ($x === $p->x) {
                    return true;
                }
                // 射线穿过多边形的边界
                if ($x > $p->x) {
                    $flag = !$flag;
                }
            }
            $j = $i;
        }
        return $flag;
    }

    /**
     * Check if two circles collide.
     * @param Circle $a
     * @param Circle $b
     * @param Response $response
     * @return bool
     */
    public static function testCircleCircle(Circle $a, Circle $b, Response &$response = null)
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
    public static function testPolygonCircle(Polygon $polygon, Circle $circle, Response &$response = null)
    {
        $circlePos = clone ($circle->pos);
        $circlePos->sub($polygon->pos);
        $radius = $circle->r;
        $radius2 = $radius * $radius;
        $points = $polygon->calcPoints;
        $len = count($points);
        //遍历每一条边 只重点判断肯定不相交的情况
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
            //圆心在边的左边
            if ($region === Helper::LEFT_VORONOI_REGION) {
                $edge = clone $polygon->edges[$prev];
                $point2 = clone $circlePos;
                $point2->sub($points[$prev]);
                $region = Helper::voronoiRegion($edge, $point2);
                //圆心在上一条边的右边
                if ($region === Helper::RIGHT_VORONOI_REGION) {
                    $dist = $point->len();
                    //圆心到这条边和上一条边公共点的距离 如果大于半径 则不相交
                    if ($dist > $radius) {
                        return false;
                    } else if ($response) {
                        //否则相交 但是圆肯定不在多边形内部
                        $response->bInA = false;
                        $overlapN = $point->normalize();
                        $overlap = $radius - $dist;
                    }
                }
                //圆心在这条边的右边
            } else if ($region === Helper::RIGHT_VORONOI_REGION) {
                $edge = clone $polygon->edges[$next];
                $point = clone $circlePos;
                $point->sub($points[$next]);
                //圆心在下一条边的左边
                $region = Helper::voronoiRegion($edge, $point);
                if ($region === Helper::LEFT_VORONOI_REGION) {
                    $dist = $point->len();
                    //圆心到公共点的距离大于半径 不相交
                    if ($dist > $radius) {
                        return false;
                    } else if ($response) {
                        $response->bInA = false;
                        $overlapN = $point->normalize();
                        $overlap = $radius - $dist;
                    }
                }
            } else {
                //圆心在边的中间 求边的单位法向量
                $normal = $edge->perp()->normalize();
                $dist = $point->dot($normal);
                $distAbs = abs($dist);
                //圆心到边的距离=abs(point · normal) 如果dist>0则说明圆心在边的外侧
                //如果距离大于半径 则不相交
                if ($dist > 0 && $distAbs > $radius) {
                    return false;
                } else if ($response) {
                    $overlapN = $normal;
                    $overlap = $radius - $dist;
                    //如果圆心在边外侧或者有一部分圆在边外侧 则圆肯定不在多边形内部
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
    public static function testCirclePolygon(Circle $circle, Polygon $polygon, Response &$response = null)
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
    public static function testPolygonPolygon(Polygon $a, Polygon $b, Response &$response = null)
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