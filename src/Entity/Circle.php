<?php
namespace SAT\Entity;

class Circle {
    public $pos;
    public $r;

    /**
     * @param Vector $pos A vector representing the position of the center of the circle
     * @param int|float $r The radius of the circle
     */
    public function __construct(Vector $pos = null, $r = 0){
        $this->pos = $pos ? $pos : new Vector();
        $this->r = $r;
    }

    /**
     * 计算圆的外接矩形
     * Compute the axis-aligned bounding box.
     * @return Box
     */
    public function getAABB(){
        $r = $this->r;
        $pos = clone $this->pos;
        $corner = $pos->sub(new Vector($r, $r));
        return (new Box($corner, $r*2, $r*2))->toPolygon();
    }
}
