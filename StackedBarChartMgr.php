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
     * Массив размера поля графика
     */
    private $graphArea;

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
        $this->graphArea = array(0, 0, 0, 0);
        $this->graphXVals = array();
        $this->graphYVals = array();
        $this->pxOneOnX = 0;
        $this->pxOneOnY = 0;
        $this->pxXCoordOnY = 0;
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
     * Пересчитываем данные пиксели
     * 
     * @return void
     */
    public function _calcYValuesInPx()
    {
        foreach ($this->graphYVals as &$yVals) {
            foreach ($yVals['values'] as $key => $yVal) {
                $yVals['values_px'][$key] = $this->pxXCoordOnY - ($yVal * $this->pxOneOnY);
            }
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
        $this->graphArea[2] = intval($width);
        $this->graphArea[3] = intval($height);
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
     * Рисуем график
     * 
     * @return void
     */
    public function draw()
    {
        $this->_calcYValuesInPx();

        header("Content-type: image/png");

        $handle = ImageCreate($this->graphArea['2'], $this->graphArea['3']);
        if ($handle === false) {
            die("Cannot Create image");
        }

        $bgColor = ImageColorAllocate($handle, 255, 255, 255);

        $xCoordColor = ImageColorAllocate($handle, 0, 0, 0);

        $rectColor = ImageColorAllocate($handle, 255, 0, 0);

        //echo "<p>Ось X: " . $this->pxXCoordOnY . "</p>";
        //echo "<textarea>" . print_r($this->graphYVals, true) . "</textarea>";

        foreach ($this->graphXVals as $key => $value) {
            //echo "<p>" . ($this->pxOneOnX * $key) . ", " . $this->graphYVals[0]['values_px'][$key] . ", "
            //    . ($this->pxOneOnX * ($key + 1)) . ", " . $this->pxXCoordOnY . "</p>";
            imagefilledrectangle(
                $handle,
                $this->pxOneOnX * $key,
                $this->graphYVals[0]['values_px'][$key],
                $this->pxOneOnX * ($key + 1),
                $this->pxXCoordOnY,
                $rectColor
            );
        }

        /*$txt_color = ImageColorAllocate ($handle, 0, 0, 0);

        ImageString ($handle, 5, 5, 18, "PHP.About.com", $txt_color);*/

        ImagePng($handle);
    }
}
