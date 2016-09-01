<?php
namespace SAT\Entity;

class Vector {
    public $x;
    public $y;

    /**
     * @param int $x The x position.
     * @param int $y The y position.
     */
    public function __construct($x = 0, $y = 0){
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Change this vector to be perpendicular to what it was before.
     * @return $this
     */
    public function perp(){
        $x = $this->x;
        $this->x = $this->y;
        $this->y = -$x;
        return $this;
    }

    /**
     * Rotate this vector counter-clockwise by the specified number of radians.
     * @param $angle
     * @return mixed
     */
    public function rotate($angle){
        $this->x = $this->x * cos($angle) - $this->y * sin($angle);
        $this->y = $this->x * sin($angle) + $this->y * cos($angle);
        return this;
    }

    /**
     * Reverse this Vector.
     * @return $this
     */
    public function reverse(){
        $this->x = -$this->x;
        $this->y = -$this->y;
        return $this;
    }

    /**
     * Make the Vector unit-lengthed.
     * @return $this
     */
    public function normalize(){
        $len = $this->len();
        if($len > 0) {
            $this->x = $this->x / $len;
            $this->y = $this->y / $len;
        }
        return $this;
    }

    /**
     * Add another Vector to this one.
     * @param Vector $other
     * @return $this
     */
    public function add(Vector $other){
        $this->x += $other->x;
        $this->y += $other->y;
        return $this;
    }

    /**
     * Subtract another Vector from this one.
     * @param Vector $other
     * @return $this
     */
    public function sub(Vector $other){
        $this->x -= $other->x;
        $this->y -= $other->y;
        return $this;
    }

    /**
     * Scale this Vector in the X and Y directions.
     * @param $x
     * @param $y
     * @return $this
     */
    public function scale($x, $y = 0){
        $this->x *= $x;
        $this->y *= $y ? $y : $x;
        return $this;
    }

    /**
     * Project this Vector onto another one.
     * @param Vector $other
     * @return $this
     */
    public function project(Vector $other){
        $amt = $this->dot($other) / $other->len2();
        $this->x = $amt * $other->x;
        $this->y = $amt * $other->y;
        return $this;
    }

    /**
     * Project this Vector onto a unit Vector.
     * @param Vector $other
     * @return $this
     */
    public function projectN(Vector $other){
        $amt = $this->dot($other);
        $this->x = $amt * $other->x;
        $this->y = $amt * $other->y;
        return $this;
    }

    /**
     * Reflect this Vector on an arbitrary axis Vector.
     * @param Vector $axis
     * @return $this
     */
    public function reflect(Vector $axis){
        $x = $this->x;
        $y = $this->y;
        $this->project($axis)->scale(2);
        $this->x -= $x;
        $this->y -= $y;
        return $this;
    }

    /**
     * Reflect this Vector on an arbitrary axis unit Vector.
     * @param Vector $axis
     * @return $this
     */
    public function reflectN(Vector $axis){
        $x = $this->x;
        $y = $this->y;
        $this->projectN($axis)->scale(2);
        $this->x -= $x;
        $this->y -= $y;
        return $this;
    }

    /**
     * Get the dot product of this Vector and another.
     * @param Vector $other
     * @return int
     */
    public function dot(Vector $other){
        return $this->x * $other->x + $this->y * $other->y;
    }

    /**
     * Get the length of this Vector
     * @return float
     */
    public function len(){
        return sqrt($this->len2());
    }

    /**
     * Get the length squared of this Vector.
     * @return int
     */
    public function len2(){
        return $this->dot($this);
    }
}