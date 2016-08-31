<?php
namespace SAT\Entity;

class Circle {
    public $pos;
    public $r;

    public function __construct(Vector $pos = null, $r = 0){
        $this->pos = $pos ? $pos : new Vector();
        $this->r = $r;
    }

    /**
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