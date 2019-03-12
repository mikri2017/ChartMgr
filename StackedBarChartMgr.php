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
        $this->errorMsg = "";
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
     * @param $xValuesArray Массив значений оси X
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
