<?php

/**
 * Класс, управляющий отрисовкой текста на графике,
 * используя библиотеку GD
 * 
 * @author Михаил Рыжков <2007mik007@mail.ru>
 */

namespace MIKRI\ChartMgr;

class FontGDDrawMgr
{
    /**
     * Путь к ttf файлу шрифта
     * 
     * @var string
     */
    private $ttfFontFilePath;

    /**
     * Размер шрифта
     * 
     * @var int
     */
    private $ttfFontSize;

    /**
     * Угол поворота текста
     * 
     * @var int
     */
    private $ttfAngle;

    /**
     * Массив из 3-х целочисленных элементов,
     * содержищих цвет текста в формате RGB
     * 
     * @var array
     */
    private $ttfFontColor;

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
        $this->ttfFontFilePath = "";
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
     * Задать путь до ttf файла шрифта
     * 
     * @param string $filePath Путь к tff файлу шрифта
     * 
     * @return bool
     */
    public function setFontFilePath($filePath)
    {
        if (\file_exists($filePath)) {
            $this->ttfFontFilePath = $filePath;
            return true;
        } else {
            $this->errorMsg = "Файл " . $filePath . " не найден в системе";
            return false;
        }
    }

    /**
     * Задаем параметры шрифта
     * 
     * @param int   $size  Размер шрифта, по умолчанию, 10
     * @param array $color Массив из 3-х целочисленных элементов: R, G, B
     * @param int   $angle Угол поворота текста, по умолчанинию, 0 - горизонтально
     * 
     * @return bool
     */
    public function setFontParams($size = 12, $color = array(0, 0, 0), $angle = 0)
    {
        $this->ttfFontSize = intval($size);
        $this->ttfAngle = intval($angle);

        // Проверяем заданыый цвет шрифта
        if (\count($color) <> 3) {
            $this->errorMsg = "Массив color должен состоять из 3-х целых чисел "
                . "от 0 до 255, формирующих цвет в формате RGB";
            return false;
        }

        foreach ($color as &$rgbEl) {
            $rgbEl = intval($rgbEl);
            if ($rgbEl < 0) {
                $rgbEl = 0;
            } else if ($rgbEl > 255) {
                $rgbEl = 255;
            }
        }

        $this->ttfFontColor = $color;
        return true;
    }

    /**
     * Рисует на переданном изображении переданный текст и возвращает
     * массив с данными области его прорисовки, либо false в случае
     * ошибки
     * 
     * @param resource $imgHandle Ресурс изображения от библиотеки GD
     * @param int      $x         Координата оси X левого нижнего угла текста
     * @param int      $y         Координата оси Y левого нижнего угла текста
     * @param string   $text      Текст для отрисовки
     * 
     * @return array
     */
    public function drawText(&$imgHandle, $x, $y, $text)
    {
        if ($imgHandle === false) {
            $this->errorMsg = "Передан пустой ресурс изображения";
            return false;
        } else {
            $fontColor = \imagecolorallocate(
                $imgHandle,
                $this->ttfFontColor[0],
                $this->ttfFontColor[1],
                $this->ttfFontColor[2]
            );
            if ($fontColor === false) {
                $errorInf = error_get_last();
                $this->errorMsg = "Ошибка при формировании цвета: ("
                    . $errorInf['type'] . ") " . $errorInf['message']
                    . " в файле " . $errorInf['file'] . " строка "
                    . $errorInf['line'];
                return false;
            }

            $textArea = \imagettftext(
                $imgHandle,
                $this->ttfFontSize,
                $this->ttfAngle,
                $x,
                $y,
                $fontColor,
                $this->ttfFontFilePath,
                $text
            );

            if ($textArea === false) {
                $errorInf = error_get_last();
                $this->errorMsg = "Ошибка при отрисовке текста: ("
                    . $errorInf['type'] . ") " . $errorInf['message']
                    . " в файле " . $errorInf['file'] . " строка "
                    . $errorInf['line'];
                return false;
            }

            $textArea['x_size'] = $textArea[2] - $textArea[0];
            $textArea['y_size'] = $textArea[3] - $textArea[5];

            return $textArea;
        }
    }

    /**
     * Возвращает массив с данными области прорисовки текста,
     * либо false в случае ошибки
     * 
     * @param int    $x    Координата оси X левого нижнего угла текста
     * @param int    $y    Координата оси Y левого нижнего угла текста
     * @param string $text Текст для отрисовки
     * 
     * @return array
     */
    public function drawTextTest($x, $y, $text)
    {
        $textArea = \imagettfbbox(
            $this->ttfFontSize,
            $this->ttfAngle,
            $this->ttfFontFilePath,
            $text
        );

        if ($textArea === false) {
            $errorInf = error_get_last();
            $this->errorMsg = "Ошибка при отрисовке текста: ("
                . $errorInf['type'] . ") " . $errorInf['message']
                . " в файле " . $errorInf['file'] . " строка "
                . $errorInf['line'];
            return false;
        }

        // Сдвигаем на переданные нам значения x и y,
        // потому что левый нижний угол [0,0]
        for ($i = 0; $i < 7; $i += 2) {
            // Ось X
            $textArea[$i] += $x;
            // Ось Y 
            $textArea[$i + 1] += $y;
        }

        $textArea['x_size'] = $textArea[2] - $textArea[0];
        $textArea['y_size'] = $textArea[3] - $textArea[5];

        return $textArea;
    }
}
