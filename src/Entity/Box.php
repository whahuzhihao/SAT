<?php
namespace SAT\Entity;

class Box {
    public $pos;
    public $w;
    public $h;

    /**
     * @param Vector $pos A vector representing the bottom-left of the box (i.e. the smallest x and smallest y value).
     * @param int $w The width of the box.
     * @param int $h The height of the box.
     */
    public function __construct(Vector $pos = null, $w = 0, $h = 0){
        $this->pos = $pos ? $pos : new Vector();
        $this->w = $w;
        $this->h = $h;
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