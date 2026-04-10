<?php

final class Node
{
    public Vector2 $partition;
    public Vector2 $partitionChange;
    public Rectangle $rightRectangle;
    public Rectangle $leftRectangle;
    public null | Node $leftChild;
    public null | Node $rightChild;
    public int $leftChildId;
    public int $rightChildId;

    public function __construct(
        Vector2 $partition,
        Vector2 $partitionChange,
        Rectangle $rightRectangle,
        Rectangle $leftRectangle,
        null | Node $leftChild,
        null | Node $rightChild,
        int $leftChildId,
        int $rightChildId,
    ) {
        $this->partition = $partition;
        $this->partitionChange = $partitionChange;
        $this->rightRectangle = $rightRectangle;
        $this->leftRectangle = $leftRectangle;
        $this->leftChild = $leftChild;
        $this->rightChild = $rightChild;
        $this->leftChildId = $leftChildId;
        $this->rightChildId = $rightChildId;
    }
}
