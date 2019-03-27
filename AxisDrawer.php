<?php

/**
 * Класс для отрисовки оси координаты
 * 
 * @author Михаил Рыжков <2007mik007@mail.ru>
 */

namespace MIKRI\ChartMgr;

class AxisDrawer
{
    /**
     * Расстояние в пикселях между ближайшими отметками
     * 
     * @var int
     */
    private $pxDeltaMark;

    /**
     * Массив со значениями RGB - цвет координатной оси
     */
    private $color;

    /**
     * Менеджер шрифтов и вывода текста на график
     * 
     * @var FontGDDrawMgr
     */
    private $fontMgr;

    /**
     * Текст последней ошибки
     * 
     * @var string
     */
    private $errorMsg;

    /**
     * Конструктор класса
     * 
     * @return void
     */
    public function __construct()
    {
        $this->pxDeltaMark = 10;
        $this->color = array(0, 0, 0);
        $this->fontMgr = new FontGDDrawMgr();
        $this->fontMgr->setFontFilePath("verdana.ttf");
        $this->fontMgr->setFontParams(8, array(0, 0, 0));
    }

    /**
     * Получить последнюю ошибку
     * 
     * @return string
     */
    public function getLastError()
    {
        return $this->errorMsg;
    }

    /**
     * Рисует горизонтальную координатную ось с отметками
     * на переданном ресурсе изображения библиотеки GD
     * 
     * @param resource $handle Ресурс изображения от библиотеки GD
     * @param int      $xBegin Координата X на изображении начала оси
     * @param int      $xEnd   Координата X на изображении конца оси
     * @param int      $y      Координата Y на изображении начала оси
     * @param int      $pxZero Координата X - значения 0
     * 
     * @return bool
     */
    public function drawHorizontally($handle, $xBegin, $xEnd, $y, $pxZero)
    {
        if (($xBegin < 0) or ($xEnd < 0) or ($y < 0) or ($pxZero < 0)) {
            $this->errorMsg = "Переданные параметры должны быть не меньше нуля!"
                . " Переданные значения: xBegin: " . $xBegin . "; xEnd: " . $xEnd
                . "; y: " . $y . "; pxZero: " . $pxZero;
            return false;
        }

        if ($xBegin > $xEnd) {
            $tmpX = $xBegin;
            $xBegin = $xEnd;
            $xEnd = $tmpX;
        }

        if (($pxZero < $xBegin) or ($pxZero > $xEnd)) {
            $this->errorMsg = "Ошибка! Точка со значением 0 не лежит"
                . " на отрезке оси!";
            return false;
        }

        $color = \imagecolorallocate(
            $handle,
            $this->color[0],
            $this->color[1],
            $this->color[2]
        );
        $axisHalfHeight = 5;
        $markY0 = $y - $axisHalfHeight;
        $markY = $y + $axisHalfHeight;

        \imageline($handle, $xBegin, $y, $xEnd, $y, $color);

        $first = true;
        $prevTextEnd = 0;
        for ($i = $pxZero; $i <= $xEnd; $i += $this->pxDeltaMark) {
            \imageline($handle, $i, $markY0, $i, $markY, $color);

            // Рисуем подпись метки
            $markCaption = $i - $pxZero;

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            $textXHalfSize = intval($textArea['x_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $i - $textXHalfSize,
                    $markY + $textArea['y_size'],
                    $markCaption
                );

                $prevTextEnd = $i + $textXHalfSize;
                $first = false;
            } else {
                if ($i - $textXHalfSize > $prevTextEnd) {
                    $textArea = $this->fontMgr->drawText(
                        $handle,
                        $i - $textXHalfSize,
                        $markY + $textArea['y_size'],
                        $markCaption
                    );

                    $prevTextEnd = $i + $textXHalfSize;
                }
            }
        }

        $first = true;
        $prevTextBegin = 0;
        for ($i = $pxZero; $i >= $xBegin; $i -= $this->pxDeltaMark) {
            \imageline($handle, $i, $markY0, $i, $markY, $color);

            // Рисуем подпись метки
            $markCaption = $i - $pxZero;

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            $textXHalfSize = intval($textArea['x_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $i - $textXHalfSize,
                    $markY + $textArea['y_size'],
                    $markCaption
                );

                $prevTextBegin = $i - $textXHalfSize;
                $first = false;
            } else {
                if ($i + $textXHalfSize < $prevTextBegin) {
                    $textArea = $this->fontMgr->drawText(
                        $handle,
                        $i - $textXHalfSize,
                        $markY + $textArea['y_size'],
                        $markCaption
                    );

                    $prevTextBegin = $i - $textArea['y_size'];
                }
            }
        }

        return true;
    }

    /**
     * Рисует горизонтальную координатную ось с отметками
     * на переданном ресурсе изображения библиотеки GD
     * 
     * @param resource $handle Ресурс изображения от библиотеки GD
     * @param int      $x      Координата X на изображении начала оси
     * @param int      $yBegin Координата Y на изображении начала оси
     * @param int      $yEnd   Координата Y на изображении конца оси
     * @param int      $pxZero Координата X - значения 0
     * 
     * @return bool
     */
    public function drawVertically($handle, $x, $yBegin, $yEnd, $pxZero)
    {
        if (($x < 0) or ($yBegin < 0) or ($yEnd < 0) or ($pxZero < 0)) {
            $this->errorMsg = "Переданные параметры должны быть не меньше нуля!"
                . " Переданные значения: x: " . $x . "; yBegin: " . $yBegin
                . "; yEnd: " . $yEnd . "; pxZero: " . $pxZero;
            return false;
        }

        if ($yBegin > $yEnd) {
            $tmpY = $yBegin;
            $yBegin = $yEnd;
            $yEnd = $tmpY;
        }

        if (($pxZero < $yBegin) or ($pxZero > $yEnd)) {
            $this->errorMsg = "Ошибка! Точка со значением 0 не лежит"
                . " на отрезке оси!";
            return false;
        }

        $color = \imagecolorallocate(
            $handle,
            $this->color[0],
            $this->color[1],
            $this->color[2]
        );
        $axisHalfWidth = 5;
        $markX0 = $x - $axisHalfWidth;
        $markX = $x + $axisHalfWidth;

        \imageline($handle, $x, $yBegin, $x, $yEnd, $color);

        $first = true;
        $prevTextBegin = 0;
        for ($i = $pxZero; $i >= $yBegin; $i -= $this->pxDeltaMark) {
            \imageline($handle, $markX0, $i, $markX, $i, $color);

            // Рисуем подпись метки
            $markCaption = (-1) * ($i - $pxZero);

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            $textYHalfSize = intval($textArea['y_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $markX0 - $textArea['x_size'],
                    $i + $textYHalfSize,
                    $markCaption
                );

                $prevTextBegin = $i - $textYHalfSize;
                $first = false;
            } else {
                if ($i + $textYHalfSize < $prevTextBegin) {
                    $textArea = $this->fontMgr->drawText(
                        $handle,
                        $markX0 - $textArea['x_size'],
                        $i + $textYHalfSize,
                        $markCaption
                    );

                    $prevTextBegin = $i - $textYHalfSize;
                }
            }
        }

        $first = true;
        $prevTextEnd = 0;
        for ($i = $pxZero; $i <= $yEnd; $i += $this->pxDeltaMark) {
            \imageline($handle, $markX0, $i, $markX, $i, $color);

            // Рисуем подпись метки
            $markCaption = (-1) * ($i - $pxZero);

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            $textYHalfSize = intval($textArea['y_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $markX0 - $textArea['x_size'],
                    $i + $textYHalfSize,
                    $markCaption
                );

                $prevTextEnd = $i + $textYHalfSize;
                $first = false;
            } else {
                if ($i - $textYHalfSize > $prevTextEnd) {
                    $textArea = $this->fontMgr->drawText(
                        $handle,
                        $markX0 - $textArea['x_size'],
                        $i + $textYHalfSize,
                        $markCaption
                    );

                    $prevTextEnd = $i + $textYHalfSize;
                }
            }
        }

        return true;
    }
}
