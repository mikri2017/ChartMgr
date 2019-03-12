<?php

/**
 * Класс, рисующий график типа Stacked Bar
 * 
 * @author Михаил Рыжков <2007mik007@mail.ru>
 */

namespace MIKRI\ChartMgr;

class StackedBarChartMgr
{
    /**
     * Массив размера области графика
     * 
     * @var array
     */
    private $graphArea;

    /**
     * Координата X - начало области графика
     * 
     * @var int
     */
    private $graphXStart;

    /**
     * Координата Y - начало области графика
     * 
     * @var int
     */
    private $graphYStart;

    /**
     * Массив размера области легенды графика
     * 
     * @var array
     */
    private $graphLegendArea;

    /**
     * Массив размера области графика с легендой
     * 
     * @var array
     */
    private $graphAreaWithLegend;

    /**
     * Массив значений оси X
     * 
     * @var array
     */
    private $graphXVals;

    /**
     * Массив массивов значений оси Y
     * 
     * @var array
     */
    private $graphYVals;

    /**
     * Количество пикселей в единице измерения по оси X
     * 
     * @var int
     */
    private $pxOneOnX;

    /**
     * Количество пикселей в единице измерения по оси Y
     */
    private $pxOneOnY;

    /**
     * Уровень оси X в пикселях оси Y
     */
    private $pxXCoordOnY;

    /**
     * Менеджер шрифтов и вывода текста на график
     * 
     * @var FontGDDrawMgr
     */
    private $fontMgr;

    /**
     * Текст последней ошибки
     */
    private $errorMsg;

    /**
     * Конструктор класса
     * 
     * @return void
     */
    public function __construct()
    {
        $this->graphAreaWithLegend = array(0, 0, 0, 0);
        $this->graphArea = array(0, 0, 0, 0);
        $this->graphXStart = 0;
        $this->graphYStart = 0;
        $this->graphLegendArea = array(0, 0, 0, 0);
        $this->graphXVals = array();
        $this->graphYVals = array();
        $this->pxOneOnX = 0;
        $this->pxOneOnY = 0;
        $this->pxXCoordOnY = 0;
        $this->fontMgr = new FontGDDrawMgr();
        $this->errorMsg = "";
    }

    /**
     * Пересчет количества пикселей на единицу
     * измерения по оси X
     * 
     * @return void
     */
    private function _calcPxOneOnX()
    {
        if (count($this->graphXVals) > 0) {
            $this->pxOneOnX = round($this->graphArea[2] / count($this->graphXVals));
        } else {
            $this->pxOneOnX = 0;
        }
    }

    /**
     * Пересчет количества пикселей на единицу
     * измерения по оси Y
     * 
     * @return void
     */
    private function _calcPxOneOnY()
    {
        if (count($this->graphYVals) > 0) {
            $first = true;
            $maxVal = 0;
            $minVal = 0;
            foreach ($this->graphYVals as $yVals) {
                foreach ($yVals['values'] as $yVal) {
                    if ($first) {
                        $maxVal = $yVal;
                        $minVal = $yVal;
                        $first = false;
                    } else {
                        if ($yVal > $maxVal) {
                            $maxVal = $yVal;
                        }

                        if ($yVal < $minVal) {
                            $minVal = $yVal;
                        }
                    }
                }
            }

            if (($minVal >= 0 && $maxVal >= 0)
                or ($minVal < 0 && $maxVal < 0)
            ) {
                if ($minVal < 0) {
                    // Если графики ниже оси X, приводим
                    // значения к положительным и обмениваем
                    $tmpVal = $minVal * (-1);
                    $minVal = $maxVal * (-1);
                    $maxVal = $tmpVal;
                    $this->pxXCoordOnY = 0;
                } else {
                    $this->pxXCoordOnY = $this->graphArea[3];
                }

                if ($maxVal > 0) {
                    $this->pxOneOnY = intval($this->graphArea[3] / $maxVal);
                } else {
                    $this->pxOneOnY = 0;
                }
            } else {
                // Графики и расположены и выше, и ниже оси X
                if ($minVal < 0) {
                    $minVal *= (-1);
                }

                if ($maxVal < 0) {
                    $maxVal *= (-1);
                }

                if ($minVal + $maxVal > 0) {
                    $this->pxOneOnY = intval($this->graphArea[3] / ($minVal + $maxVal));
                    $this->pxXCoordOnY = ($maxVal * $this->pxOneOnY);
                } else {
                    $this->pxOneOnY = 0;
                }
            }
        } else {
            $this->pxOneOnY = 0;
        }
    }

    /**
     * Пересчитываем данные в пиксели
     * 
     * @return void
     */
    private function _calcYValuesInPx()
    {
        foreach ($this->graphYVals as &$yVals) {
            foreach ($yVals['values'] as $key => $yVal) {
                $yVals['values_px'][$key] = $this->pxXCoordOnY - ($yVal * $this->pxOneOnY);
            }
        }
    }

    /**
     * Рисует блок данных на графике по переданным координатам
     * 
     * @param resource $handle Ресурс изображения от библиотеки GD
     * @param array    $color
     * @param int      $darkColorDelta
     * @param array    $blockArea
     * 
     * @return bool
     */
    private function _drawGraphDataBlock($handle, $color, $darkColorDelta, $blockArea)
    {
        // Задаем цвет блока и рисуем его
        $rectColor = \imagecolorallocate($handle, $color[0], $color[1], $color[2]);

        \imagefilledrectangle(
            $handle,
            $blockArea[0],
            $blockArea[1],
            $blockArea[2],
            $blockArea[3],
            $rectColor
        );

        // Задаем цвет рамки графика и рисуем ее
        foreach ($color as &$colorEl) {
            $colorEl -= $darkColorDelta;
            if ($colorEl < 0) {
                $colorEl = 0;
            }
        }

        $rectFrColor = \imagecolorallocate($handle, $color[0], $color[1], $color[2]);

        \imagerectangle(
            $handle,
            $blockArea[0],
            $blockArea[1],
            $blockArea[2],
            $blockArea[3],
            $rectFrColor
        );

        return true;
    }

    /**
     * Отрисовываем легенду на графике
     * 
     * @param resource $handle Ресурс изображения от библиотеки GD
     * 
     * @return void
     */
    private function _drawLegend($handle)
    {
        $strHeight = 20;
        $blockSize = 10;
        $darkColorDelta = 70;
        $i = 0;
        foreach ($this->graphYVals as $yVals) {
            $blockArea = array(
                $this->graphLegendArea[0],
                $this->graphLegendArea[1] + $i * $strHeight,
                $this->graphLegendArea[0] + $blockSize,
                $this->graphLegendArea[1] + $i * $strHeight + $blockSize
            );

            $this->_drawGraphDataBlock($handle, $yVals['color'], $darkColorDelta, $blockArea);

            $this->fontMgr->setFontParams(8, $yVals['color']);
            $this->fontMgr->drawText(
                $handle,
                $this->graphLegendArea[0] + $blockSize + 5,
                $this->graphLegendArea[1] + $i * $strHeight + $blockSize,
                $yVals['caption']
            );

            $i++;
        }
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
     * Задать размеры области графика в пикселях
     * 
     * @param int $width  Ширина области графика
     * @param int $height Высота области графика
     * 
     * @return void
     */
    public function setGraphArea($width, $height)
    {
        $this->graphAreaWithLegend[2] = intval($width);
        $this->graphAreaWithLegend[3] = intval($height);

        // Ширина легенды в 20% всего поля
        $graphLegendWidth = $this->graphAreaWithLegend[2] / 100 * 20;

        // Ширина описания оси Y в 10% всего поля
        $this->graphXStart = $this->graphAreaWithLegend[2] / 100 * 10;

        // Высота отступа сверху по оси Y в 3% всего поля
        $this->graphYStart = $this->graphAreaWithLegend[3] / 100 * 3;

        // Высота описания оси X в 20% всего поля
        $graphXAxisDescHeight = $this->graphAreaWithLegend[3] / 100 * 15;

        $this->graphLegendArea[0] = $this->graphAreaWithLegend[2] - $graphLegendWidth;
        $this->graphLegendArea[1] = $this->graphYStart;
        $this->graphLegendArea[2] = $this->graphAreaWithLegend[2];
        $this->graphLegendArea[3] = $this->graphAreaWithLegend[3];
        $this->graphArea[0] = 0;
        $this->graphArea[1] = 0;
        $this->graphArea[2] = $this->graphAreaWithLegend[2] - $this->graphXStart - $graphLegendWidth;
        $this->graphArea[3] = $this->graphAreaWithLegend[3] - $this->graphYStart - $graphXAxisDescHeight;
    }

    /**
     * Получить размеры области графика в пикселях
     * 
     * @return array
     */
    public function getGraphArea()
    {
        return $this->graphArea;
    }

    /**
     * Передача значений оси X на график
     * 
     * @param array $xValuesArray Массив значений оси X
     * 
     * @return bool
     */
    public function setXCoordinates($xValuesArray)
    {
        if (!\is_array($xValuesArray)) {
            $this->errorMsg = "В функцию передан не массив";
            return false;
        }

        $tmpArray = array();
        foreach ($xValuesArray as $value) {
            if (is_array($value)) {
                $this->errorMsg = "Переданный в функцию массив должен быть"
                    . " одномерным";
                return false;
            }

            array_push($tmpArray, $value);
        }

        $this->graphXVals = $tmpArray;
        $this->_calcPxOneOnX();
        return true;
    }

    /**
     * Получить текущий массив значений оси X
     * 
     * @return array
     */
    public function getXCoordinates()
    {
        return $this->graphXVals;
    }

    /**
     * Добавить массив значений оси Y - еще один график
     * 
     * @param array  $yValuesArray  Массив значений оси Y
     * @param array  $rgbColorArray Массив из 3-х элеметов типа int, составляющих
     *                              цвет: Красный, Зеленый, Синий
     * @param string $caption       Подпись к графику
     * 
     * @return bool
     */
    public function addYCoordinates($yValuesArray, $rgbColorArray, $caption = "")
    {
        $tmpArray = array();
        $tmpArray['color'] = array();
        $tmpArray['values'] = array();
        $tmpArray['caption'] = $caption;

        // Проверяем переданные значения
        if (\count($yValuesArray) <> \count($this->graphXVals)) {
            $this->errorMsg = "Количество переданных значений оси Y не совпадает с "
                . "количеством значений на оси X";
            return false;
        }

        foreach ($yValuesArray as $yValue) {
            if (!is_integer($yValue)) {
                $this->errorMsg = "Значения по оси Y на графике должно быть "
                    . "типа int";
                return false;
            }

            array_push($tmpArray['values'], $yValue);
        }

        // Проверяем переданный цвет
        if (\count($rgbColorArray) <> 3) {
            $this->errorMsg = "Массив цвета rgbColorArray должен содержать "
                . "3 элемента, составляющие цвета RGB: Красный, Зеленый, Синий";
            return false;
        }

        foreach ($rgbColorArray as $colorElem) {
            if (!is_integer($colorElem)) {
                $this->errorMsg = "Значения составляющих цвета RGB должны быть "
                    . "типа int";
                return false;
            }

            array_push($tmpArray['color'], $colorElem);
        }

        array_push($this->graphYVals, $tmpArray);
        $this->_calcPxOneOnY();
        return true;
    }

    /**
     * Убираем один из графиков - значения оси Y.
     * Учитывайте, что после удаления, индексы массива перестраиваются!
     * 
     * @param int $num ID массива значений оси Y для удаления
     * 
     * @return void
     */
    public function removeOneYCoordinates($num)
    {
        if (isset($this->graphYVals[$num])) {
            unset($this->graphYVals[$num]);
        }
    }

    /**
     * Получить массив значений оси Y текущих графиков
     * 
     * @return array
     */
    public function getYCoordinates()
    {
        return $this->graphYVals;
    }

    /**
     * Отрисовка и вывод графика в файл, либо в браузер
     * 
     * @param bool   $inFile   Сохранить ли изображение графика в файл,
     *                         по умолчанию, false
     * @param string $filePath Путь к файлу, в который сохранить график
     * 
     * @return bool
     */
    public function draw($inFile = false, $filePath = "")
    {
        $this->_calcYValuesInPx();

        $handle = \imagecreate(
            $this->graphAreaWithLegend[2],
            $this->graphAreaWithLegend[3]
        );

        if ($handle === false) {
            $errorInf = error_get_last();
            $this->errorMsg = "Ошибка при создании изображения: ("
                    . $errorInf['type'] . ") " . $errorInf['message']
                    . " в файле " . $errorInf['file'] . " строка "
                    . $errorInf['line'];
            return false;
        }

        $this->fontMgr->setFontFilePath("verdana.ttf");

        $bgColor = \imagecolorallocate($handle, 255, 255, 255);
        $darkColorDelta = 70;

        // Рисуем рамку графика
        $frameGraphColor = \imagecolorallocate($handle, 0, 0, 0);
        \imagerectangle(
            $handle,
            $this->graphArea[0] + $this->graphXStart,
            $this->graphArea[1] + $this->graphYStart,
            $this->graphArea[2] + $this->graphXStart,
            $this->graphArea[3] + $this->graphYStart,
            $frameGraphColor
        );

        // Рисуем рамку легенды
        \imagerectangle(
            $handle,
            $this->graphLegendArea[0],
            $this->graphLegendArea[1],
            $this->graphLegendArea[2],
            $this->graphLegendArea[3],
            $frameGraphColor
        );

        foreach ($this->graphXVals as $key => $value) {
            $arrayOnX = array();
            $arrayUnderX = array();
            for ($i = 0; $i < count($this->graphYVals); $i++) {
                if ($this->graphYVals[$i]['values_px'][$key] >= $this->pxXCoordOnY) {
                    $arrayOnX[$i] = $this->graphYVals[$i]['values_px'][$key];
                } else {
                    $arrayUnderX[$i] = $this->graphYVals[$i]['values_px'][$key];
                }
            }

            arsort($arrayOnX);
            asort($arrayUnderX);

            foreach ($arrayOnX as $i => $value) {
                $blockArea = array(
                    $this->pxOneOnX * $key + $this->graphXStart,
                    $this->graphYVals[$i]['values_px'][$key] + $this->graphYStart,
                    $this->pxOneOnX * ($key + 1) + $this->graphXStart,
                    $this->pxXCoordOnY + $this->graphYStart
                );

                $this->_drawGraphDataBlock(
                    $handle,
                    $this->graphYVals[$i]['color'],
                    $darkColorDelta,
                    $blockArea
                );
            }

            foreach ($arrayUnderX as $i => $value) {
                $blockArea = array(
                    $this->pxOneOnX * $key + $this->graphXStart,
                    $this->graphYVals[$i]['values_px'][$key] + $this->graphYStart,
                    $this->pxOneOnX * ($key + 1) + $this->graphXStart,
                    $this->pxXCoordOnY + $this->graphYStart,
                );

                $this->_drawGraphDataBlock(
                    $handle,
                    $this->graphYVals[$i]['color'],
                    $darkColorDelta,
                    $blockArea
                );
            }
        }

        $xCoordColor = \imagecolorallocate($handle, 0, 0, 0);
        \imagefilledrectangle(
            $handle,
            $this->graphXStart,
            $this->pxXCoordOnY - 1 + $this->graphYStart,
            $this->graphArea[2] + $this->graphXStart,
            $this->pxXCoordOnY + 1 + $this->graphYStart,
            $xCoordColor
        );

        // Рисуем легенду на графике
        $this->_drawLegend($handle);

        if ($inFile) {
            \imagepng($handle, $filePath);
        } else {
            \imagepng($handle);
        }

        \imagedestroy($handle);

        return true;
    }
}
