<?php
namespace SAT;

use SAT\Entity\Vector;
use SAT\Entity\Response;

abstract class Helper
{
    const LEFT_VORONOI_REGION = -1;
    const MIDDLE_VORONOI_REGION = 0;
    const RIGHT_VORONOI_REGION = 1;

    /**
     * Flattens the specified array of points onto a unit vector axis, resulting in a one dimensional range of the minimum and maximum value on that axis.
     * @param $points
     * @param Vector $normal
     * @param $result
     */
    public static function flattenPointsOn($points, Vector $normal, &$result)
    {
        $min = INF;
        $max = -INF;
        foreach ($points as $point) {
            $dot = $point->dot($normal);
            if ($dot < $min) $min = $dot;
            if ($dot > $max) $max = $dot;
        }
        $result[0] = $min;
        $result[1] = $max;
    }

    /**
     * Check whether two convex polygons are separated by the specified axis (must be a unit vector).
     * @param Vector $aPos
     * @param Vector $bPos
     * @param $aPoints
     * @param $bPoints
     * @param Vector $axis
     * @param $response
     * @return bool
     */
    public static function isSeparatingAxis(Vector $aPos, Vector $bPos, $aPoints, $bPoints, Vector $axis, Response &$response = null)
    {
        $rangeA = array();
        $rangeB = array();
        $tBpos = clone $bPos;
        $offsetV = $tBpos->sub($aPos);
        $projectedOffset = $offsetV->dot($axis);
        self::flattenPointsOn($aPoints, $axis, $rangeA);
        self::flattenPointsOn($bPoints, $axis, $rangeB);


        $rangeB[0] += $projectedOffset;
        $rangeB[1] += $projectedOffset;


        if ($rangeA[0] > $rangeB[1] || $rangeB[0] > $rangeA[1]) {
            return true;
        }

        if ($response) {
            $overlap = 0;
            if ($rangeA[0] < $rangeB[0]) {
                $response->aInB = false;
                if ($rangeA[1] < $rangeB[1]) {
                    $overlap = $rangeA[1] - $rangeB[0];
                    $response->bInA = false;
                } else {
                    $option1 = $rangeA[1] - $rangeB[0];
                    $option2 = $rangeB[1] - $rangeA[0];
                    $overlap = $option1 < $option2 ? $option1 : -$option2;
                }
            } else {
                $response->bInA = false;
                if ($rangeA[1] > $rangeB[1]) {
                    $overlap = $rangeA[0] - $rangeB[1];
                    $response->aInB = false;
                } else {
                    $option1 = $rangeA[1] - $rangeB[0];
                    $option2 = $rangeB[1] - $rangeA[0];
                    $overlap = $option1 < $option2 ? $option1 : -$option2;
                }
            }
            $absOverlap = abs($overlap);
            if ($absOverlap < $response->overlap) {
                $response->overlap = $absOverlap;
                $response->overlapN = clone $axis;
                if ($overlap < 0) {
                    $response->overlapN->reverse();
                }
            }
        }
        return false;
    }

    /**
     * 点在线的哪一边 line=AB point=AC x=point · line (x<0钝角在左边) (0<x<=|line|^2在线中间区域) (x>|line|^2在线右边)
     * Calculates which Voronoi region a point is on a line segment.
     * @param Vector $line
     * @param Vector $point
     * @return int
     */
    public static function voronoiRegion(Vector $line, Vector $point)
    {
        $len2 = $line->len2();
        $dp = $point->dot($line);
        if ($dp < 0) {
            return self::LEFT_VORONOI_REGION;
        } else if ($dp > $len2) {
            return self::RIGHT_VORONOI_REGION;
        } else {
            return self::MIDDLE_VORONOI_REGION;
        }
    }
}