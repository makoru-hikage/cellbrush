<?php

namespace Donquixote\Cellbrush\Matrix;

use Donquixote\Cellbrush\Cell\CellInterface;
use Donquixote\Cellbrush\Cell\PlaceholderCell;
use Donquixote\Cellbrush\Cell\ShadowCell;

/**
 * Matrix of table cells by index.
 */
class CellMatrix {

  /**
   * @var ShadowCell
   */
  private $shadowCell;

  /**
   * @var CellInterface[][]
   *   Format: $[$rowIndex][$colIndex] = new TableCell(..)
   */
  private $cells;

  /**
   * @param int $nRows
   * @param int $nColumns
   *
   * @return static
   */
  public static function create($nRows, $nColumns) {
    $emptyRow = $nColumns
      ? array_fill(0, $nColumns, new PlaceholderCell())
      : [];
    return new static($nRows, $emptyRow);
  }

  /**
   * @param int $nRows
   * @param CellInterface[] $emptyRow
   */
  public function __construct($nRows, array $emptyRow) {
    $this->shadowCell = new ShadowCell();
    // Create an empty n*m matrix.
    $this->cells = $nRows
      ? array_fill(0, $nRows, $emptyRow)
      : [];
  }

  /**
   * @param BrushInterface $brush
   * @param CellInterface $cell
   */
  public function addCell(BrushInterface $brush, CellInterface $cell) {
    if ($brush->hasRange()) {
      $this->paintShadow($brush);
    }
    list($iRow, $iCol) = $brush->getPosition();

    // Set the cell.
    $this->cells[$iRow][$iCol] = $cell
      ->setRowspan($brush->nRows())
      ->setColspan($brush->nCols());
  }

  /**
   * @param BrushInterface $brush
   *   The current area occupied by the cell.
   *
   * @return RangedBrush
   *   The area occupied by the cell after the operation.
   */
  public function brushCellGrowRight(BrushInterface $brush) {
    $cell =& $this->brushRequireCell($brush);
    $colspan = $cell->getColspan();
    $nextBrush = $brush;
    while ($nextBrush = $nextBrush->getNext()) {
      $nextBrush->iColSup();
      if (!$this->brushIsFree($nextBrush)) {
        break;
      }
      $this->paintShadow($nextBrush);
      $colspan += $nextBrush->nCols();
    }
    $cell = $cell->setColspan($colspan);

    // Return a brush representing the new area occupied by the cell.
    return $brush->setColspan($colspan);
  }

  /**
   * Finds a cell that matches exactly the brush, or throws an exception.
   *
   * @param BrushInterface $brush
   *
   * @return CellInterface
   */
  public function &brushRequireCell(BrushInterface $brush) {
    $iCol = $brush->iCol();
    $iRow = $brush->iRow();
    if (!isset($this->cells[$iRow][$iCol])) {
      throw new \RuntimeException('Illegal growing.');
    }
    $cell =& $this->cells[$iRow][$iCol];
    if ($cell instanceof PlaceholderCell) {
      throw new \RuntimeException('Illegal growing.');
    }
    if ($cell instanceof ShadowCell) {
      throw new \RuntimeException('Illegal growing.');
    }
    if ($cell->getRowspan() !== $brush->nRows()) {
      throw new \RuntimeException('Illegal growing.');
    }
    if ($cell->getColspan() !== $brush->nCols()) {
      throw new \RuntimeException('Illegal growing.');
    }
    return $cell;
  }

  /**
   * @param BrushInterface $brush
   *
   * @return bool
   *   true, if the brush area is free to add new cells.
   */
  public function brushIsFree(BrushInterface $brush) {
    $colIndices = $brush->getColIndices();
    foreach ($brush->getRowIndices() as $iRow) {
      if (!isset($this->cells[$iRow])) {
        return false;
      }
      $rowCells = $this->cells[$iRow];
      foreach ($colIndices as $iCol) {
        if (!isset($rowCells[$iCol])) {
          return false;
        }
        if (!$rowCells[$iCol] instanceof PlaceholderCell) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * @param BrushInterface $brush
   */
  private function paintShadow(BrushInterface $brush) {
    $colIndices = $brush->getColIndices();
    foreach ($brush->getRowIndices() as $rowIndex) {
      if (!Isset($this->cells[$rowIndex])) {
        throw new \RuntimeException('Illegal row index');
      }
      $rowCells =& $this->cells[$rowIndex];
      foreach ($colIndices as $colIndex) {
        if (!isset($rowCells[$colIndex])) {
          throw new \RuntimeException('Illegal col index');
        }
        if (!$rowCells[$colIndex] instanceof PlaceholderCell) {
          throw new \RuntimeException('Illegal position');
        }
        $rowCells[$colIndex] = $this->shadowCell;
      }
    }
  }

  /**
   * @return \Donquixote\Cellbrush\Cell\CellInterface[][]
   */
  public function getCells() {
    return $this->cells;
  }
}