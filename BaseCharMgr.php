<?php

/**
 * Базовый класс, содержащий основу для классов
 * отрисовки отдельных типов графиков
 * 
 * @author Михаил Рыжков <2007mik007@mail.ru>
 */

namespace MIKRI\ChartMgr;

class BaseCharMgr
{
    /**
     * Массив размера области графика
     * 
     * @var array
     */
    protected $graphArea;

    /**
     * Координата X - начало области графика
     * 
     * @var int
     */
    protected $graphXStart;

    /**
     * Координата Y - начало области графика
     * 
     * @var int
     */
    protected $graphYStart;

    /**
     * Массив размера области легенды графика
     * 
     * @var array
     */
    protected $graphLegendArea;

    /**
     * Массив размера области графика с легендой
     * 
     * @var array
     */
    protected $graphAreaWithLegend;

    /**
     * Массив значений оси X
     * 
     * @var array
     */
    protected $graphXVals;

    /**
     * Массив массивов значений оси Y
     * 
     * @var array
     */
    protected $graphYVals;

    /**
     * Количество пикселей в единице измерения по оси X
     * 
     * @var int
     */
    protected $pxOneOnX;

    /**
     * Количество единиц измерения в одной единице на оси Y
     * 
     * @var int
     */
    protected $countOneOnY;

    /**
     * Количество пикселей в одной единице по оси Y
     * 
     * @var int
     */
    protected $pxOneOnY;

    /**
     * Уровень оси X в пикселях оси Y
     * 
     * @var int
     */
    protected $pxXCoordOnY;

    /**
     * Менеджер шрифтов и вывода текста на график
     * 
     * @var FontGDDrawMgr
     */
    protected $fontMgr;

    /**
     * Рисующий оси на графике
     * 
     * @var AxisDrawer
     */
    protected $axDrawer;

    /**
     * Текст последней ошибки
     * 
     * @var string
     */
    protected $errorMsg;

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
        $this->countOneOnY = 1;
        $this->pxOneOnY = 0;
        $this->pxXCoordOnY = 0;
        $this->fontMgr = new FontGDDrawMgr();
        $this->axDrawer = new AxisDrawer();
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
                    if ($maxVal > $this->graphArea[3]) {
                        // Если график по оси Y не влезает, масштабируем
                        $this->countOneOnY = ceil($maxVal / $this->graphArea[3]);
                    }

                    $this->pxOneOnY = intval($this->graphArea[3] / ($maxVal / $this->countOneOnY));
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

                $fromMinToMax = $minVal + $maxVal;

                if ($fromMinToMax > 0) {
                    if ($fromMinToMax > $this->graphArea[3]) {
                        // Если график по оси Y не влезает, масштабируем
                        $this->countOneOnY = ceil($fromMinToMax / $this->graphArea[3]);
                    }

                    $this->pxOneOnY = intval($this->graphArea[3] / ($fromMinToMax / $this->countOneOnY));
                    $this->pxXCoordOnY = ($maxVal / $this->countOneOnY * $this->pxOneOnY);
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
    protected function calcYValuesInPx()
    {
        foreach ($this->graphYVals as &$yVals) {
            foreach ($yVals['values'] as $key => $yVal) {
                $yVals['vals_px'][$key] = intval($this->pxXCoordOnY - ($yVal / $this->countOneOnY * $this->pxOneOnY));
            }
        }
    }

    /**
     * Рисует блок данных на графике по переданным координатам
     * 
     * @param resource $handle         Ресурс изображения от библиотеки GD
     * @param array    $color          Массив из 3-х целочисленных элементов: R, G, B
     * @param int      $darkColorDelta Сколько отнять от RGB каналов цвета для
     *                                 прорисовки темной рамки
     * @param array    $blockArea      Массив 4-х координат области блока
     * 
     * @return bool
     */
    protected function drawGraphDataBlock($handle, $color, $darkColorDelta, $blockArea)
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
     * @return bool
     */
    protected function drawLegend($handle)
    {
        $strHeight = 20;
        $blockSize = 10;
        $darkColorDelta = 70;
        $margin = 5;

        $i = 0;
        foreach ($this->graphYVals as $yVals) {
            $blockArea = array(
                $margin + $this->graphLegendArea[0],
                $margin + $this->graphLegendArea[1] + $i * $strHeight,
                $margin + $this->graphLegendArea[0] + $blockSize,
                $margin + $this->graphLegendArea[1] + $i * $strHeight + $blockSize
            );

            $this->drawGraphDataBlock($handle, $yVals['color'], $darkColorDelta, $blockArea);

            if (!$this->fontMgr->setFontParams(8, $yVals['color'])) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $res = $this->fontMgr->drawText(
                $handle,
                $margin + $this->graphLegendArea[0] + $blockSize + 5,
                $margin + $this->graphLegendArea[1] + $i * $strHeight + $blockSize,
                $yVals['caption']
            );

            if ($res === false) {
                $this->errorMsg = $this->fontMgr->getLastError();
                return false;
            }

            $i++;
        }

        return true;
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

        if (!$this->axDrawer->setFontFilePath($filePath)) {
            $this->errorMsg = $this->axDrawer->getLastError();
            return false;
        }

        return true;
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
        $graphXAxisDescHeight = $this->graphAreaWithLegend[3] / 100 * 20;

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
            if (is_integer($yValue) or is_float($yValue)) {
                array_push($tmpArray['values'], $yValue);
            } else {
                $this->errorMsg = "Значения по оси Y на графике должно быть "
                    . "типа int/float";
                return false;
            }
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
}
