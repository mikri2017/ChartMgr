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
     * 
     * @var array
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
     * Задать путь до ttf файла шрифта
     * 
     * @param string $filePath Путь к tff файлу шрифта
     * 
     * @return bool
     */
    public function setFontFilePath($filePath)
    {
        if (!$this->fontMgr->setFontFilePath($filePath)) {
            $this->errorMsg = $this->fontMgr->getLastError();
            return false;
        }

        return true;
    }

    /**
     * Задаем параметры шрифта координатной оси
     * 
     * @param int   $size      Размер шрифта, по умолчанию, 10
     * @param array $color     Массив из 3-х целочисленных элементов: R, G, B
     * @param array $fontColor Цвет шрифта, используется, если захочется текст
     *                         координатной оси отобразить другим цветом, по
     *                         умолчанию, false - тот же цвет, что и у оси
     * @param int   $angle     Угол поворота текста, по умолчанинию,
     *                         0 - горизонтально
     * 
     * @return bool
     */
    public function setAxisFontAndColorParams($size = 10, $color = array(0, 0, 0),
        $fontColor = false, $angle = 0
    ) {
        if ($fontColor === false) {
        } else {
            if (\is_array($fontColor) and (\count($fontColor) == 3)) {
                foreach ($fontColor as $key => $value) {
                    $fontColor[$key] = intval($value);
                }
            } else {
                $this->errorMsg = "Ошибка! Передан некорректный массив fontColor: "
                    . print_r($fontColor, true);
                return false;
            }
        }

        if (\is_array($color) and (\count($color) == 3)) {
            $this->color[0] = intval($color[0]);
            $this->color[1] = intval($color[1]);
            $this->color[2] = intval($color[2]);
        } else {
            $this->errorMsg = "Ошибка! Передан некорректный массив color: "
                    . print_r($color, true);
            return false;
        }

        if ($fontColor === false) {
            // Задаем цвет шрифта, как у координатной оси
            $res = $this->fontMgr->setFontParams($size, $this->color, $angle);
        } else {
            $res = $this->fontMgr->setFontParams($size, $fontColor, $angle);
        }

        if (!$res) {
            $this->errorMsg = $this->fontMgr->getLastError();
            return false;
        }

        return true;
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

        $this->pxDeltaMark = 10;

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
            if ($textArea === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $textXHalfSize = intval($textArea['x_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $i - $textXHalfSize,
                    $markY + $textArea['y_size'],
                    $markCaption
                );

                if ($textArea === false) {
                    $this->errorMsg = $this->fontMgr->getLastError();
                    return false;
                }

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

                    if ($textArea === false) {
                        $this->errorMsg = $this->fontMgr->getLastError();
                        return false;
                    }

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
            if ($textArea === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $textXHalfSize = intval($textArea['x_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $i - $textXHalfSize,
                    $markY + $textArea['y_size'],
                    $markCaption
                );

                if ($textArea === false) {
                    $this->errorMsg = $this->fontMgr->getLastError();
                    return false;
                }

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

                    if ($textArea === false) {
                        $this->errorMsg = $this->fontMgr->getLastError();
                        return false;
                    }

                    $prevTextBegin = $i - $textArea['y_size'];
                }
            }
        }

        return true;
    }

    /**
     * Рисует горизонтальную координатную ось с текстовыми
     * отметками равноудаленными друг от друга на переданном
     * ресурсе изображения библиотеки GD
     * 
     * @param resource $handle      Ресурс изображения от библиотеки GD
     * @param int      $xBegin      Координата X на изображении начала оси
     * @param int      $xEnd        Координата X на изображении конца оси
     * @param int      $y           Координата Y на изображении начала оси
     * @param array    $textVals    Массив текстовых значений на оси X
     * @param bool     $fullSection Занять текстовым значением весь отрезок
     *                              от предыдущего до текущего значения оси
     * 
     * @return bool
     */
    public function drawHorizontallyTextVals($handle, $xBegin, $xEnd, $y, $textVals, $fullSection = true)
    {
        if (($xBegin < 0) or ($xEnd < 0) or ($y < 0)) {
            $this->errorMsg = "Переданные параметры должны быть не меньше нуля!"
                . " Переданные значения: xBegin: " . $xBegin . "; xEnd: " . $xEnd
                . "; y: " . $y;
            return false;
        }

        if ($xBegin > $xEnd) {
            $tmpX = $xBegin;
            $xBegin = $xEnd;
            $xEnd = $tmpX;
        }

        if (count($textVals) == 0) {
            $this->errorMsg = "Ошибка! Передан пустой массив текстовых"
                . " значений оси!";
            return false;
        }

        $this->pxDeltaMark =  intval(($xEnd - $xBegin) / count($textVals));

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
        $pxCurMark = $xBegin;
        $pxNextMark = $pxCurMark + $this->pxDeltaMark;
        foreach ($textVals as $value) {
            \imageline($handle, $pxNextMark, $markY0, $pxNextMark, $markY, $color);

            $textArea = $this->fontMgr->drawTextTest(0, 0, $value);
            if ($textArea === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $textXHalfSize = intval($textArea['x_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $pxCurMark + (($pxNextMark - $pxCurMark - $textArea['x_size']) / 2),
                    $markY + $textArea['y_size_fl'], // Подаем высоту 1-й строки!
                    $value
                );

                if ($textArea === false) {
                    $this->errorMsg = $this->fontMgr->getLastError();
                    return false;
                }

                $prevTextEnd = $pxCurMark + $textXHalfSize;
                $first = false;
            } else {
                if ($pxCurMark - $textXHalfSize > $prevTextEnd) {
                    $textArea = $this->fontMgr->drawText(
                        $handle,
                        $pxCurMark + (($pxNextMark - $pxCurMark - $textArea['x_size']) / 2),
                        $markY + $textArea['y_size_fl'], // Подаем высоту 1-й строки!
                        $value
                    );

                    if ($textArea === false) {
                        $this->errorMsg = $this->fontMgr->getLastError();
                        return false;
                    }

                    $prevTextEnd = $pxCurMark + $textXHalfSize;
                }
            }

            $pxCurMark = $pxNextMark;
            $pxNextMark = $pxCurMark + $this->pxDeltaMark;
        }

        return true;
    }

    /**
     * Рисует горизонтальную координатную ось с отметками
     * на переданном ресурсе изображения библиотеки GD
     * 
     * @param resource $handle  Ресурс изображения от библиотеки GD
     * @param int      $x       Координата X на изображении начала оси
     * @param int      $yBegin  Координата Y на изображении начала оси
     * @param int      $yEnd    Координата Y на изображении конца оси
     * @param int      $pxZero  Координата X - значения 0
     * @param float    $valsLen Минимальное значение на оси Y
     * 
     * @return bool
     */
    public function drawVertically($handle, $x, $yBegin, $yEnd, $pxZero, $valsLen)
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

        $this->pxDeltaMark = 10;

        $deltaVal = intval($valsLen * $this->pxDeltaMark / ($yEnd - $yBegin));

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
        $curVal = 0;
        for ($i = $pxZero; $i >= $yBegin; $i -= $this->pxDeltaMark) {
            \imageline($handle, $markX0, $i, $markX, $i, $color);

            // Рисуем подпись метки
            $markCaption = $curVal;
            $curVal += $deltaVal;

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            if ($textArea === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $textYHalfSize = intval($textArea['y_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $markX0 - $textArea['x_size'],
                    $i + $textYHalfSize,
                    $markCaption
                );

                if ($textArea === false) {
                    $this->errorMsg = $this->fontMgr->getLastError();
                    return false;
                }

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

                    if ($textArea === false) {
                        $this->errorMsg = $this->fontMgr->getLastError();
                        return false;
                    }

                    $prevTextBegin = $i - $textYHalfSize;
                }
            }
        }

        $first = true;
        $prevTextEnd = 0;
        $curVal = 0;
        for ($i = $pxZero; $i <= $yEnd; $i += $this->pxDeltaMark) {
            \imageline($handle, $markX0, $i, $markX, $i, $color);

            // Рисуем подпись метки
            $markCaption = $curVal;
            $curVal -= $deltaVal;

            $textArea = $this->fontMgr->drawTextTest(0, 0, $markCaption);
            if ($textArea === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $textYHalfSize = intval($textArea['y_size'] / 2);

            if ($first) {
                $textArea = $this->fontMgr->drawText(
                    $handle,
                    $markX0 - $textArea['x_size'],
                    $i + $textYHalfSize,
                    $markCaption
                );

                if ($textArea === false) {
                    $this->errorMsg = $this->fontMgr->getLastError();
                    return false;
                }

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

                    if ($textArea === false) {
                        $this->errorMsg = $this->fontMgr->getLastError();
                        return false;
                    }

                    $prevTextEnd = $i + $textYHalfSize;
                }
            }
        }

        return true;
    }
}
