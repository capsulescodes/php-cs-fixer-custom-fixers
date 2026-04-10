<?php

final class Node
{
    public function __construct(
        public Vector2 $partition,
        public Vector2 $partitionChange,
        public Rectangle $rightRectangle,
        public Rectangle $leftRectangle,
        public null | Node $leftChild,
        public null | Node $rightChild,
        public int $leftChildId,
        public int $rightChildId,
    ) {}
}
