<?php
namespace SAT\Entity;

class Response {
    public $a;
    public $b;
    public $overlapN;
    public $overlapV;

    public $aInB;
    public $bInA;
    public $overlap;

    /**
     * Shape $a Object A
     * Shape $b Object B
     * Vector $overlapV Representing the minimum change necessary to extract the first object from the second one
     * Vector $overlapN A unit vector in that direction of $overlapV and the magnitude of the overlap
     * boolean aInB Whether the first object is entirely inside the second
     * boolean bInA Whether the second object is entirely inside the first
     */
    public function __construct(){
        $this->a = null;
        $this->b = null;
        $this->overlapV = new Vector();
        $this->overlapN = new Vector();
        $this->clear();
    }

    /**
     * Clear the response so that it is ready to be reused for another collision test.
     * @return $this
     */
    public function clear(){
        $this->aInB = true;
        $this->bInA = true;
        $this->overlap = INF;
        return $this;
    }

    /**
     * Returns a new Polygon whose edges are the edges of the box.
     * @return Polygon
     */
    public function toPolygon(){
        return new Polygon(new Vector($this->pos->x, $this->pos->y),
            array(
                new Vector(), new Vector($this->w, 0),
                new Vector($this->w,$this->h), new Vector(0,$this->h)
            )
        );
    }
}