<?php

/**
 * Класс, рисующий график типа Stacked Bar
 * 
 * @author Михаил Рыжков <2007mik007@mail.ru>
 */

namespace MIKRI\ChartMgr;

class LineChartMgr extends BaseCharMgr
{
    /**
     * Конструктор класса
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pxYCoordOnX = 0;
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
        $this->calcYValuesInPx();

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

        // Рисуем 
        foreach ($this->graphXVals as $key => $value) {
            for ($i = 0; $i < count($this->graphYVals); $i++) {
                $lineColor = \imagecolorallocate(
                    $handle,
                    $this->graphYVals[$i]['color'][0],
                    $this->graphYVals[$i]['color'][1],
                    $this->graphYVals[$i]['color'][2]
                );

                if ($key > 0) {
                    $prevX = $key - 1;
                } else {
                    $prevX = $key;
                }

                \imageline(
                    $handle,
                    $this->pxOneOnX * $prevX + $this->graphXStart,
                    $this->graphYVals[$i]['vals_px'][$prevX] + $this->graphYStart,
                    $this->pxOneOnX * $key + $this->graphXStart,
                    $this->graphYVals[$i]['vals_px'][$key] + $this->graphYStart,
                    $lineColor
                );

                // Подсвечиваем точки графика жирнее
                \imagerectangle(
                    $handle,
                    $this->pxOneOnX * $key + $this->graphXStart - 1,
                    $this->graphYVals[$i]['vals_px'][$key] + $this->graphYStart - 1,
                    $this->pxOneOnX * $key + $this->graphXStart + 1,
                    $this->graphYVals[$i]['vals_px'][$key] + $this->graphYStart + 1,
                    $lineColor
                );
            }
        }

        $xCoordColor = \imagecolorallocate($handle, 0, 0, 0);
        \imagefilledrectangle(
            $handle,
            $this->graphXStart,
            $this->pxXCoordOnY + $this->graphYStart,
            $this->graphArea[2] + $this->graphXStart,
            $this->pxXCoordOnY + $this->graphYStart,
            $xCoordColor
        );

        $res = $this->axDrawer->drawHorizontally(
            $handle,
            $this->graphArea[0] + $this->graphXStart,
            $this->graphArea[2] + $this->graphXStart,
            $this->graphArea[3] + $this->graphYStart,
            $this->graphArea[0] + $this->graphXStart
        );

        if (!$res) {
            $this->errorMsg = $this->axDrawer->getLastError();
            return false;
        }

        $res = $this->axDrawer->drawVertically(
            $handle,
            $this->graphArea[0] + $this->graphXStart,
            $this->graphArea[1] + $this->graphYStart,
            $this->graphArea[3] + $this->graphYStart,
            $this->pxXCoordOnY + $this->graphYStart
        );

        if (!$res) {
            $this->errorMsg = $this->axDrawer->getLastError();
            return false;
        }

        // Рисуем легенду на графике
        $this->drawLegend($handle);

        if ($inFile) {
            \imagepng($handle, $filePath);
        } else {
            \imagepng($handle);
        }

        \imagedestroy($handle);

        return true;
    }
}
